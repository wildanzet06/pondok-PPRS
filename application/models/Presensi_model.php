<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Presensi_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Pastikan tabel tambahan (keuangan/tagihan/presensi) tersedia.
        $this->load->helper('skema');
        skema_tambahan_pastikan($this->db);
    }
    // Kode status => nama
    public $status = array('H' => 'Hadir', 'I' => 'Izin', 'S' => 'Sakit', 'A' => 'Alpa');

    public $daftar_bulan = array(
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );

    // =====================================================================
    // KELAS & SISWA (membaca data yang sudah ada, tidak mengubahnya)
    // =====================================================================

    private function unit_key($unit) {
        $unit = strtolower((string) $unit);
        return in_array($unit, array('mts', 'madin', 'tpq'), true) ? $unit : 'mts';
    }

    private function unit_id($unit) {
        $unit = $this->unit_key($unit);
        if ($unit === 'madin') {
            return function_exists('unit_id_madin') ? (int) unit_id_madin() : 2;
        }
        if ($unit === 'tpq') {
            return function_exists('unit_id_tpq') ? (int) unit_id_tpq() : 3;
        }
        return function_exists('unit_id_mts') ? (int) unit_id_mts() : 1;
    }

    /**
     * Kelas non-formal tidak punya rombel di master_kelas. Untuk Madin/TPQ
     * dipakai "kelas virtual" dengan id negatif agar tidak bentrok dengan
     * id_kelas asli. Format: -(1000 * unit_id + nomor).
     */
    private function virtual_decode($id_kelas) {
        $id = (int) $id_kelas;
        if ($id >= 0) {
            return null;
        }
        $abs   = abs($id);
        $unit  = (int) floor($abs / 1000);
        $slot  = $abs % 1000;
        if ($unit < 1 || $slot < 1 || $slot > 999) {
            return null;
        }
        return array('unit_id' => $unit, 'slot' => $slot);
    }

    /** Semua kelas virtual untuk satu unit (belum disaring berisi siswa). */
    private function virtual_kelas_semua($unit) {
        $unit    = $this->unit_key($unit);
        $id_unit = $this->unit_id($unit);
        $hasil   = array();

        if ($unit === 'madin') {
            $hasil[] = (object) array(
                'id_kelas'   => -(1000 * $id_unit + 1),
                'nama_kelas' => 'Madin · Seluruh santri',
                'unit_id'    => $id_unit,
                'jilid'      => '',
                'virtual'    => true
            );
            return $hasil;
        }

        if ($unit === 'tpq') {
            $daftar  = function_exists('jilid_daftar') ? jilid_daftar() : array();
            $dipakai = array();
            foreach ($daftar as $j) {
                $slot = (int) $j['urutan'];
                if ($slot < 1 || $slot > 998 || isset($dipakai[$slot])) {
                    $slot = 1;
                    while ($slot <= 998 && isset($dipakai[$slot])) {
                        $slot++;
                    }
                    if ($slot > 998) {
                        break;
                    }
                }
                $dipakai[$slot] = true;
                $kode  = (string) $j['kode'];
                $nama  = trim((string) $j['nama']);
                $label = ($nama !== '' && $nama !== $kode) ? $nama : $kode;
                if ($label === '') {
                    $label = 'Jilid ' . $slot;
                }
                $hasil[] = (object) array(
                    'id_kelas'   => -(1000 * $id_unit + $slot),
                    'nama_kelas' => 'TPQ · ' . $label,
                    'unit_id'    => $id_unit,
                    'jilid'      => $kode,
                    'virtual'    => true
                );
            }
            $hasil[] = (object) array(
                'id_kelas'   => -(1000 * $id_unit + 999),
                'nama_kelas' => 'TPQ · Tanpa jilid',
                'unit_id'    => $id_unit,
                'jilid'      => '__tanpa',
                'virtual'    => true
            );
        }

        return $hasil;
    }

    /** Info kelas virtual berdasarkan id (negatif). */
    private function kelas_virtual($id_kelas) {
        if (!$this->virtual_decode($id_kelas)) {
            return null;
        }
        foreach (array('madin', 'tpq') as $unit) {
            foreach ($this->virtual_kelas_semua($unit) as $k) {
                if ((int) $k->id_kelas === (int) $id_kelas) {
                    $k->unit_key = $unit;
                    return $k;
                }
            }
        }
        return null;
    }

    private function roster_virtual($id_kelas, $id_tp) {
        $k = $this->kelas_virtual($id_kelas);
        if (!$k) {
            return array();
        }

        $this->db->select('DISTINCT s.id_siswa, s.nis, s.nama', false)
            ->from('master_siswa_unit su')
            ->join('master_siswa s', 's.id_siswa = su.siswa_id')
            ->where('su.unit_id', (int) $k->unit_id)
            ->where('su.status', 1)
            ->where('(su.id_tp = 0 OR su.id_tp = ' . (int) $id_tp . ')', null, false);

        $jilid = (string) $k->jilid;
        if ($jilid === '__tanpa') {
            $this->db->where("(su.jilid IS NULL OR su.jilid = '')", null, false);
        } elseif ($jilid !== '') {
            $this->db->where('su.jilid', $jilid);
        }

        return $this->db->order_by('s.nama', 'ASC')->get()->result();
    }

    /** Kunci unit (mts/madin/tpq) dari sebuah id kelas, termasuk kelas virtual. */
    public function unit_kelas($id_kelas) {
        $k = $this->kelas_virtual($id_kelas);
        if ($k) {
            return $k->unit_key;
        }
        $row = $this->db->select('unit_id')
            ->get_where('master_kelas', array('id_kelas' => (int) $id_kelas))->row();
        $id_unit = $row ? (int) $row->unit_id : 0;
        if ($id_unit <= 0) {
            $id_unit = $this->unit_id('mts');
        }
        foreach (array('mts', 'madin', 'tpq') as $unit) {
            if ($id_unit === $this->unit_id($unit)) {
                return $unit;
            }
        }
        return 'mts';
    }

    /** Pastikan kelas yang dipilih memang milik unit yang sedang aktif. */
    public function kelas_sesuai_unit($id_kelas, $unit) {
        $unit    = $this->unit_key($unit);
        $id_unit = $this->unit_id($unit);
        $k       = $this->kelas_virtual($id_kelas);
        if ($k) {
            return (int) $k->unit_id === $id_unit;
        }
        $row = $this->db->select('unit_id')
            ->get_where('master_kelas', array('id_kelas' => (int) $id_kelas))->row();
        if (!$row) {
            return false;
        }
        $milik = (int) $row->unit_id;
        if ($milik <= 0) {
            $milik = $this->unit_id('mts'); // data lama sebelum ada kolom unit
        }
        return $milik === $id_unit;
    }

    // Kelas yang punya siswa pada tahun pelajaran + semester aktif
    public function daftar_kelas($id_tp, $id_smt, $unit = 'mts') {
        $id_tp   = (int) $id_tp;
        $id_smt  = (int) $id_smt;
        $unit    = $this->unit_key($unit);
        $id_unit = $this->unit_id($unit);

        $syarat_unit = $unit === 'mts'
            ? '(COALESCE(mk.unit_id, 0) IN (0, ' . $id_unit . '))'
            : 'mk.unit_id = ' . $id_unit;

        $sql = "SELECT DISTINCT mk.id_kelas, mk.nama_kelas, mk.unit_id
                FROM kelas_siswa ks
                JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                WHERE ks.id_tp = ? AND ks.id_smt = ? AND " . $syarat_unit . "
                ORDER BY mk.nama_kelas ASC";
        $hasil = $this->db->query($sql, array($id_tp, $id_smt))->result();

        if ($unit !== 'mts') {
            foreach ($this->virtual_kelas_semua($unit) as $k) {
                if ($this->roster_virtual($k->id_kelas, $id_tp)) {
                    $hasil[] = $k;
                }
            }
        }
        return $hasil;
    }

    public function kelas_valid($id_kelas, $id_tp, $id_smt, $unit = null) {
        $id_kelas = (int) $id_kelas;
        if ($unit !== null && !$this->kelas_sesuai_unit($id_kelas, $unit)) {
            return false;
        }
        if ($this->kelas_virtual($id_kelas)) {
            return count($this->roster_virtual($id_kelas, (int) $id_tp)) > 0;
        }
        $this->db->where('id_kelas', $id_kelas);
        $this->db->where('id_tp', (int) $id_tp);
        $this->db->where('id_smt', (int) $id_smt);
        return $this->db->count_all_results('kelas_siswa') > 0;
    }

    public function nama_kelas($id_kelas) {
        $k = $this->kelas_virtual($id_kelas);
        if ($k) {
            return html_entity_decode(strip_tags($k->nama_kelas), ENT_QUOTES, 'UTF-8');
        }
        $r = $this->db->get_where('master_kelas', array('id_kelas' => (int) $id_kelas))->row();
        return $r ? $r->nama_kelas : '-';
    }

    // Daftar siswa di sebuah kelas (urut nama)
    public function roster($id_kelas, $id_tp, $id_smt, $unit = null) {
        if ($this->kelas_virtual($id_kelas)) {
            return $this->roster_virtual($id_kelas, (int) $id_tp);
        }

        $sql = "SELECT DISTINCT s.id_siswa, s.nis, s.nama
                FROM kelas_siswa ks
                JOIN master_siswa s ON s.id_siswa = ks.id_siswa
                WHERE ks.id_tp = ? AND ks.id_smt = ? AND ks.id_kelas = ?";
        if ($unit !== null) {
            $unit    = $this->unit_key($unit);
            $id_unit = $this->unit_id($unit);
            if ($unit === 'mts') {
                $sql .= " AND (EXISTS (SELECT 1 FROM master_siswa_unit su WHERE su.siswa_id = s.id_siswa"
                      . " AND su.unit_id = " . $id_unit . " AND su.status = 1)"
                      . " OR NOT EXISTS (SELECT 1 FROM master_siswa_unit su2 WHERE su2.siswa_id = s.id_siswa))";
            } else {
                $sql .= " AND EXISTS (SELECT 1 FROM master_siswa_unit su WHERE su.siswa_id = s.id_siswa"
                      . " AND su.unit_id = " . $id_unit . " AND su.status = 1)";
            }
        }
        $sql .= " ORDER BY s.nama ASC";
        return $this->db->query($sql, array((int) $id_tp, (int) $id_smt, (int) $id_kelas))->result();
    }

    public function siswa_by_id($id) {
        return $this->db->select('id_siswa, nis, nama, username')
                        ->get_where('master_siswa', array('id_siswa' => (int) $id))->row();
    }

    public function siswa_by_username($username) {
        return $this->db->select('id_siswa, nis, nama, username')
                        ->get_where('master_siswa', array('username' => (string) $username))->row();
    }

    // =====================================================================
    // MATA PELAJARAN (opsional: dipakai jika tabel master_mapel dikenali)
    // =====================================================================

    public function mapel_tersedia() {
        return $this->db->table_exists('master_mapel')
            && $this->db->field_exists('id_mapel', 'master_mapel')
            && $this->db->field_exists('nama_mapel', 'master_mapel');
    }

    public function daftar_mapel() {
        if (!$this->mapel_tersedia()) return array();
        return $this->db->select('id_mapel, nama_mapel')->order_by('nama_mapel', 'ASC')->get('master_mapel')->result();
    }

    /**
     * Jumlah jam pelajaran per hari untuk sebuah kelas.
     * Diambil dari pengaturan jadwal (kelas_jadwal_kbm.kbm_jml_mapel_hari).
     * Fallback: jam_ke terbesar pada jadwal mapel, lalu $fallback.
     */
    public function jam_maks($id_kelas, $id_tp, $id_smt, $fallback = 15) {
        $fallback = max(1, (int) $fallback);
        $id_kelas = (int) $id_kelas;

        // TPQ tidak memakai jam pelajaran: satu presensi per hari.
        $virtual = $this->kelas_virtual($id_kelas);
        if ($virtual && $virtual->unit_key === 'tpq') {
            return 1;
        }
        if ($id_kelas < 1) return $fallback;

        $kbm = null;
        if ($this->db->table_exists('kelas_jadwal_kbm')) {
            $kbm = $this->db->select('kbm_jml_mapel_hari')
                            ->get_where('kelas_jadwal_kbm', array(
                                'id_kelas' => $id_kelas,
                                'id_tp'    => (int) $id_tp,
                                'id_smt'   => (int) $id_smt
                            ))
                            ->row();
        }

        if ($kbm && (int) $kbm->kbm_jml_mapel_hari > 0) {
            return min($fallback, (int) $kbm->kbm_jml_mapel_hari);
        }

        if ($this->db->table_exists('kelas_jadwal_mapel') && $this->db->field_exists('jam_ke', 'kelas_jadwal_mapel')) {
            $j = $this->db->select_max('jam_ke', 'jam_maks')
                          ->get_where('kelas_jadwal_mapel', array(
                              'id_kelas' => $id_kelas,
                              'id_tp'    => (int) $id_tp,
                              'id_smt'   => (int) $id_smt
                          ))
                          ->row();
            if ($j && (int) $j->jam_maks > 0) {
                return min($fallback, (int) $j->jam_maks);
            }
        }

        return $fallback;
    }

    public function nama_mapel($id) {
        if (!$this->mapel_tersedia()) return null;
        $r = $this->db->select('nama_mapel')->get_where('master_mapel', array('id_mapel' => (int) $id))->row();
        return $r ? $r->nama_mapel : null;
    }

    // =====================================================================
    // SESI & PRESENSI
    // =====================================================================

    public function get_sesi($id_kelas, $tanggal, $jam_ke) {
        return $this->db->get_where('cbt_presensi_sesi', array(
            'id_kelas' => (int) $id_kelas,
            'tanggal'  => $tanggal,
            'jam_ke'   => (int) $jam_ke
        ))->row();
    }

    public function get_sesi_by_id($id) {
        return $this->db->get_where('cbt_presensi_sesi', array('id' => (int) $id))->row();
    }

    // Sesi lain pada kelas + tanggal yang sama (untuk pindah cepat antar jam)
    public function sesi_hari($id_kelas, $tanggal) {
        $this->db->where('id_kelas', (int) $id_kelas);
        $this->db->where('tanggal', $tanggal);
        $this->db->order_by('jam_ke', 'ASC');
        return $this->db->get('cbt_presensi_sesi')->result();
    }

    // Peta [siswa_id] => baris presensi untuk satu sesi
    public function status_sesi($sesi_id) {
        $peta = array();
        foreach ($this->db->get_where('cbt_presensi', array('sesi_id' => (int) $sesi_id))->result() as $r) {
            $peta[$r->siswa_id] = $r;
        }
        return $peta;
    }

    /**
     * Simpan presensi satu sesi (membuat sesi baru atau memperbarui yang ada).
     * $data: array(siswa_id => array('status' => 'H|I|S|A', 'ket' => '...'))
     * Mengembalikan id sesi.
     */
    public function simpan($id_tp, $id_smt, $id_kelas, $tanggal, $jam_ke, $mapel_id, $mapel_nama, $oleh, $data) {
        $this->db->trans_start();

        $sesi = $this->get_sesi($id_kelas, $tanggal, $jam_ke);
        if ($sesi) {
            $sesi_id = (int) $sesi->id;
            $this->db->where('id', $sesi_id);
            $this->db->update('cbt_presensi_sesi', array(
                'mapel_id'    => $mapel_id ? $mapel_id : null,
                'mapel_nama'  => ($mapel_nama !== '') ? $mapel_nama : null,
                'dibuat_oleh' => $oleh
            ));
        } else {
            $this->db->insert('cbt_presensi_sesi', array(
                'id_tp'       => (int) $id_tp,
                'id_smt'      => (int) $id_smt,
                'id_kelas'    => (int) $id_kelas,
                'tanggal'     => $tanggal,
                'jam_ke'      => (int) $jam_ke,
                'mapel_id'    => $mapel_id ? $mapel_id : null,
                'mapel_nama'  => ($mapel_nama !== '') ? $mapel_nama : null,
                'dibuat_oleh' => $oleh
            ));
            $sesi_id = (int) $this->db->insert_id();
        }

        $sql = "INSERT INTO cbt_presensi (sesi_id, siswa_id, status, keterangan)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)";
        foreach ($data as $siswa_id => $d) {
            $this->db->query($sql, array(
                $sesi_id,
                (int) $siswa_id,
                $d['status'],
                ($d['ket'] !== '') ? $d['ket'] : null
            ));
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $sesi_id : 0;
    }

    public function hapus_sesi($id) {
        $id = (int) $id;
        $this->db->trans_start();
        $this->db->where('sesi_id', $id);
        $this->db->delete('cbt_presensi');
        $this->db->where('id', $id);
        $this->db->delete('cbt_presensi_sesi');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // =====================================================================
    // REKAP & RIWAYAT
    // =====================================================================

    // Rekap per siswa untuk satu kelas pada rentang tanggal
    public function rekap($id_kelas, $id_tp, $id_smt, $dari, $sampai, $unit = null) {
        $kolom = "s.id_siswa, s.nis, s.nama,
                  COALESCE(SUM(p.status = 'H'), 0) AS h,
                  COALESCE(SUM(p.status = 'I'), 0) AS i,
                  COALESCE(SUM(p.status = 'S'), 0) AS s_,
                  COALESCE(SUM(p.status = 'A'), 0) AS a";
        $k     = $this->kelas_virtual($id_kelas);
        $param = array();

        if ($k) {
            $syarat = 'su.unit_id = ? AND su.status = 1 AND (su.id_tp = 0 OR su.id_tp = ?)';
            $param  = array((int) $id_kelas, $dari, $sampai, (int) $k->unit_id, (int) $id_tp);
            $jilid  = (string) $k->jilid;
            if ($jilid === '__tanpa') {
                $syarat .= " AND (su.jilid IS NULL OR su.jilid = '')";
            } elseif ($jilid !== '') {
                $syarat .= ' AND su.jilid = ?';
                $param[] = $jilid;
            }
            $sql = "SELECT " . $kolom . "
                    FROM master_siswa_unit su
                    JOIN master_siswa s ON s.id_siswa = su.siswa_id
                    LEFT JOIN cbt_presensi_sesi ps
                           ON ps.id_kelas = ? AND ps.tanggal BETWEEN ? AND ?
                    LEFT JOIN cbt_presensi p
                           ON p.sesi_id = ps.id AND p.siswa_id = s.id_siswa
                    WHERE " . $syarat . "
                    GROUP BY s.id_siswa, s.nis, s.nama
                    ORDER BY s.nama ASC";
        } else {
            $sql = "SELECT " . $kolom . "
                    FROM kelas_siswa ks
                    JOIN master_siswa s ON s.id_siswa = ks.id_siswa
                    LEFT JOIN cbt_presensi_sesi ps
                           ON ps.id_kelas = ks.id_kelas AND ps.tanggal BETWEEN ? AND ?
                    LEFT JOIN cbt_presensi p
                           ON p.sesi_id = ps.id AND p.siswa_id = s.id_siswa
                    WHERE ks.id_tp = ? AND ks.id_smt = ? AND ks.id_kelas = ?";
            if ($unit !== null) {
                $unit    = $this->unit_key($unit);
                $id_unit = $this->unit_id($unit);
                if ($unit === 'mts') {
                    $sql .= " AND (EXISTS (SELECT 1 FROM master_siswa_unit su WHERE su.siswa_id = s.id_siswa"
                          . " AND su.unit_id = " . $id_unit . " AND su.status = 1)"
                          . " OR NOT EXISTS (SELECT 1 FROM master_siswa_unit su2 WHERE su2.siswa_id = s.id_siswa))";
                } else {
                    $sql .= " AND EXISTS (SELECT 1 FROM master_siswa_unit su WHERE su.siswa_id = s.id_siswa"
                          . " AND su.unit_id = " . $id_unit . " AND su.status = 1)";
                }
            }
            $sql .= " GROUP BY s.id_siswa, s.nis, s.nama ORDER BY s.nama ASC";
            $param = array($dari, $sampai, (int) $id_tp, (int) $id_smt, (int) $id_kelas);
        }
        $rows = $this->db->query($sql, $param)->result();

        foreach ($rows as $r) {
            $r->h = (int) $r->h; $r->i = (int) $r->i; $r->s_ = (int) $r->s_; $r->a = (int) $r->a;
            $r->total = $r->h + $r->i + $r->s_ + $r->a;
            $r->persen = $r->total > 0 ? round($r->h / $r->total * 100) : null;
        }
        return $rows;
    }

    // Jumlah sesi pelajaran yang sudah dicatat untuk satu kelas pada rentang tanggal
    public function jumlah_sesi($id_kelas, $dari, $sampai) {
        $this->db->where('id_kelas', (int) $id_kelas);
        $this->db->where('tanggal >=', $dari);
        $this->db->where('tanggal <=', $sampai);
        return $this->db->count_all_results('cbt_presensi_sesi');
    }

    // Riwayat presensi satu siswa
    public function riwayat_siswa($siswa_id, $dari, $sampai) {
        $sql = "SELECT ps.tanggal, ps.jam_ke, ps.mapel_nama, p.status, p.keterangan
                FROM cbt_presensi p
                JOIN cbt_presensi_sesi ps ON ps.id = p.sesi_id
                WHERE p.siswa_id = ? AND ps.tanggal BETWEEN ? AND ?
                ORDER BY ps.tanggal DESC, ps.jam_ke ASC";
        return $this->db->query($sql, array((int) $siswa_id, $dari, $sampai))->result();
    }

    // Hitungan H/I/S/A dari hasil riwayat_siswa()
    public function hitung($riwayat) {
        $n = array('H' => 0, 'I' => 0, 'S' => 0, 'A' => 0);
        foreach ($riwayat as $r) {
            if (isset($n[$r->status])) $n[$r->status]++;
        }
        $total = array_sum($n);
        $n['total']  = $total;
        $n['persen'] = $total > 0 ? round($n['H'] / $total * 100) : null;
        return $n;
    }
}
