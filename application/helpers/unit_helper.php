<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper unit yayasan (MTs / Madin / TPQ).
 *
 * Satu murid bisa tergabung di lebih dari satu unit, jadi keanggotaan
 * disimpan di master_siswa_unit (bukan kolom tunggal di master_siswa).
 * Murid yang belum punya baris keanggotaan dianggap MTs (akses penuh),
 * supaya data lama tidak terkunci setelah fitur ini dipasang.
 */

if (!function_exists('unit_pilihan')) {
    function unit_pilihan($termasuk_semua = false)
    {
        $CI =& get_instance();
        static $cache = null;

        if ($cache === null) {
            $cache = array();
            if ($CI->db->table_exists('master_unit')) {
                $rows = $CI->db->select('id_unit, nama')
                    ->order_by('id_unit', 'ASC')
                    ->get('master_unit')->result();
                foreach ($rows as $r) {
                    $cache[(int) $r->id_unit] = $r->nama;
                }
            }
            if (!$cache) {
                $cache = array(1 => 'MTs', 2 => 'Madin', 3 => 'TPQ');
            }
        }

        return $termasuk_semua ? (array(0 => 'Semua unit') + $cache) : $cache;
    }
}

if (!function_exists('unit_nama')) {
    function unit_nama($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return 'Semua unit';
        }
        $pilihan = unit_pilihan();
        return isset($pilihan[$id]) ? $pilihan[$id] : '-';
    }
}

if (!function_exists('unit_siswa_ids')) {
    /**
     * Daftar id unit yang diikuti oleh username siswa yang sedang login.
     * $id_tp = 0 berarti semua tahun pelajaran.
     */
    function unit_siswa_ids($username = null, $id_tp = 0)
    {
        $CI =& get_instance();
        if (!isset($CI->ion_auth)) {
            $CI->load->library('ion_auth');
        }
        if ($username === null) {
            $user = $CI->ion_auth->user()->row();
            $username = $user ? $user->username : '';
        }

        static $cache = array();
        $kunci = (string) $username . '|' . (int) $id_tp;
        if (isset($cache[$kunci])) {
            return $cache[$kunci];
        }

        $ids = array();
        if ($username !== '' && $CI->db->table_exists('master_siswa_unit')) {
            $CI->db->select('su.unit_id')
                ->from('master_siswa_unit su')
                ->join('master_siswa s', 's.id_siswa = su.siswa_id')
                ->where('s.username', $username)
                ->where('su.status', 1);
            if ((int) $id_tp > 0) {
                $CI->db->where('su.id_tp', (int) $id_tp);
            }
            foreach ($CI->db->get()->result() as $r) {
                $ids[] = (int) $r->unit_id;
            }
        }

        return $cache[$kunci] = array_values(array_unique($ids));
    }
}

if (!function_exists('unit_siswa_akses_penuh')) {
    /**
     * MTs memakai seluruh fitur siswa. Madin/TPQ hanya SPP + absen.
     * Tanpa data keanggotaan = MTs (aman untuk data lama).
     */
    function unit_siswa_akses_penuh($username = null)
    {
        $ids = unit_siswa_ids($username);
        return empty($ids) || in_array(1, $ids, true);
    }
}

if (!function_exists('unit_id_madin')) {
    /** Id unit Madin dari tabel master_unit (fallback 2 untuk data lama). */
    function unit_id_madin()
    {
        $CI =& get_instance();
        static $id = null;
        if ($id === null) {
            $id = 2;
            if ($CI->db->table_exists('master_unit')) {
                $row = $CI->db->select('id_unit')->where('kode', 'madin')->get('master_unit')->row();
                if ($row) {
                    $id = (int) $row->id_unit;
                }
            }
        }
        return $id;
    }
}

if (!function_exists('unit_id_mts')) {
    /** Id unit MTs dari tabel master_unit (fallback 1 untuk data lama). */
    function unit_id_mts()
    {
        $CI =& get_instance();
        static $id = null;
        if ($id === null) {
            $id = 1;
            if ($CI->db->table_exists('master_unit')) {
                $row = $CI->db->select('id_unit')->where('kode', 'mts')->get('master_unit')->row();
                if ($row) {
                    $id = (int) $row->id_unit;
                }
            }
        }
        return $id;
    }
}

if (!function_exists('siswa_ids_unit_mts')) {
    /**
     * Saring id siswa untuk kelas MTs.
     *
     * Murid yang hanya terdaftar di Madin/TPQ tidak boleh masuk ke kelas MTs.
     * Murid dengan dua keanggotaan (mis. MTs,Madin) tetap boleh masuk kelas MTs.
     * Data lama yang belum punya baris master_siswa_unit dianggap MTs.
     *
     * @param array $ids Daftar id_siswa
     * @return array
     */
    function siswa_ids_unit_mts($ids)
    {
        $CI =& get_instance();
        if (!is_array($ids)) {
            return array();
        }

        $bersih = array();
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $bersih[$id] = $id;
            }
        }
        if (!$bersih || !$CI->db->table_exists('master_siswa_unit')) {
            return array_values($bersih);
        }

        $anggota = array();
        $baris = $CI->db->select('siswa_id, unit_id')
            ->where_in('siswa_id', $bersih)
            ->where('status', 1)
            ->get('master_siswa_unit')->result();
        foreach ($baris as $r) {
            $anggota[(int) $r->siswa_id][] = (int) $r->unit_id;
        }

        $mts = (int) unit_id_mts();
        $hasil = array();
        foreach ($bersih as $id) {
            $unit = isset($anggota[$id]) ? $anggota[$id] : array();
            if (!$unit || in_array($mts, $unit, true)) {
                $hasil[] = $id;
            }
        }
        return $hasil;
    }
}

if (!function_exists('unit_id_tpq')) {
    /** Id unit TPQ dari tabel master_unit (fallback 3 untuk data lama). */
    function unit_id_tpq()
    {
        $CI =& get_instance();
        static $id = null;
        if ($id === null) {
            $id = 3;
            if ($CI->db->table_exists('master_unit')) {
                $row = $CI->db->select('id_unit')->where('kode', 'tpq')->get('master_unit')->row();
                if ($row) {
                    $id = (int) $row->id_unit;
                }
            }
        }
        return $id;
    }
}

if (!function_exists('unit_siswa_unit_ids_by_id')) {
    /** Unit yang dicentang untuk sebuah id_siswa (bukan username). */
    function unit_siswa_unit_ids_by_id($id_siswa)
    {
        $CI =& get_instance();
        $ids = array();
        if ((int) $id_siswa <= 0 || !$CI->db->table_exists('master_siswa_unit')) {
            return $ids;
        }
        $rows = $CI->db->select('unit_id')
            ->where('siswa_id', (int) $id_siswa)
            ->where('status', 1)
            ->get('master_siswa_unit')->result();
        foreach ($rows as $r) {
            $ids[] = (int) $r->unit_id;
        }
        return array_values(array_unique($ids));
    }
}

if (!function_exists('unit_siswa_jilid')) {
    /** Jilid TPQ milik siswa; kosong bila siswa bukan anggota TPQ. */
    function unit_siswa_jilid($id_siswa)
    {
        $CI =& get_instance();
        if ((int) $id_siswa <= 0 || !$CI->db->table_exists('master_siswa_unit')
            || !$CI->db->field_exists('jilid', 'master_siswa_unit')) {
            return '';
        }
        // Selesaikan dulu: query builder CI3 dipakai bersama, memanggil helper
        // lain di tengah rantai where() akan menggabungkan kedua query.
        $id_tpq = unit_id_tpq();
        $row = $CI->db->select('jilid')
            ->where('siswa_id', (int) $id_siswa)
            ->where('unit_id', $id_tpq)
            ->where('status', 1)
            ->get('master_siswa_unit')->row();
        return $row ? trim((string) $row->jilid) : '';
    }
}

if (!function_exists('jilid_daftar')) {
    /**
     * Nama jilid TPQ yang dikenal: dari master_jilid plus kode yang sedang
     * dipakai siswa (meski belum diberi nama). Diurutkan numerik dulu.
     */
    function jilid_daftar()
    {
        $CI =& get_instance();
        $daftar = array();

        if ($CI->db->table_exists('master_jilid')) {
            $rows = $CI->db->select('kode, nama, urutan')
                ->order_by('urutan', 'ASC')->order_by('nama', 'ASC')
                ->get('master_jilid')->result_array();
            foreach ($rows as $r) {
                $kode = trim((string) $r['kode']);
                if ($kode === '') {
                    continue;
                }
                $daftar[$kode] = array(
                    'kode'   => $kode,
                    'nama'   => (string) $r['nama'],
                    'urutan' => (int) $r['urutan']
                );
            }
        }

        if ($CI->db->table_exists('master_siswa_unit')
            && $CI->db->field_exists('jilid', 'master_siswa_unit')) {
            $id_tpq = unit_id_tpq();
            $rows = $CI->db->distinct()->select('jilid')
                ->where('unit_id', $id_tpq)->where('status', 1)
                ->where('jilid IS NOT NULL', null, false)
                ->where("jilid <> ''", null, false)
                ->get('master_siswa_unit')->result_array();
            foreach ($rows as $r) {
                $kode = trim((string) $r['jilid']);
                if ($kode !== '' && !isset($daftar[$kode])) {
                    $daftar[$kode] = array(
                        'kode'   => $kode,
                        'nama'   => $kode,
                        'urutan' => 900
                    );
                }
            }
        }

        $hasil = array_values($daftar);
        usort($hasil, function ($a, $b) {
            if ((int) $a['urutan'] !== (int) $b['urutan']) {
                return (int) $a['urutan'] < (int) $b['urutan'] ? -1 : 1;
            }
            return strnatcasecmp($a['kode'], $b['kode']);
        });
        foreach ($hasil as &$item) {
            if ((int) $item['urutan'] === 0) {
                $item['urutan'] = 0;
            }
        }
        unset($item);
        return $hasil;
    }
}

if (!function_exists('jilid_nama')) {
    /** Nama tampilan sebuah kode jilid; kode itu sendiri bila belum diberi nama. */
    function jilid_nama($kode)
    {
        $kode = trim((string) $kode);
        if ($kode === '') {
            return '';
        }
        static $peta = null;
        if ($peta === null) {
            $peta = array();
            foreach (jilid_daftar() as $item) {
                $peta[$item['kode']] = $item['nama'];
            }
        }
        return isset($peta[$kode]) ? $peta[$kode] : $kode;
    }
}

if (!function_exists('jilid_sql_urutan')) {
    /** Ekspresi ORDER BY: angka numerik dulu, lalu urutan nama khusus. */
    function jilid_sql_urutan($kolom = 'jilid')
    {
        $CI =& get_instance();
        $kolom = '`' . str_replace('`', '', (string) $kolom) . '`';
        $awal = "CASE WHEN $kolom REGEXP '^[0-9]+$' THEN CAST($kolom AS UNSIGNED) ELSE ";
        if ($CI->db->table_exists('master_jilid')) {
            return $awal
                . "1000 + COALESCE((SELECT mj.urutan FROM master_jilid mj WHERE mj.kode = $kolom LIMIT 1), 999) END";
        }
        return $awal . '999 END';
    }
}

if (!function_exists('unit_siswa_simpan')) {
    /**
     * Tulis ulang keanggotaan unit sebuah siswa secara idempoten.
     *
     * $pilihan boleh array checkbox (unit[]) atau string "MTs,Madin" dari Excel.
     * Tanpa pilihan yang valid, siswa tetap dianggap MTs (akses penuh).
     * $jilid = null berarti jilid lama dipertahankan; '' berarti dihapus.
     */
    function unit_siswa_simpan($id_siswa, $pilihan, $jilid = null)
    {
        $CI =& get_instance();
        $id_siswa = (int) $id_siswa;
        if ($id_siswa <= 0 || !$CI->db->table_exists('master_siswa_unit')) {
            return false;
        }

        $mentah = array();
        if (is_array($pilihan)) {
            foreach ($pilihan as $item) {
                foreach (explode(',', (string) $item) as $bagian) {
                    $mentah[] = trim($bagian);
                }
            }
        } elseif ($pilihan !== null && $pilihan !== '') {
            $mentah = explode(',', (string) $pilihan);
        }

        $valid = array_keys(unit_pilihan());
        $units = array();
        foreach ($mentah as $item) {
            $item = trim($item);
            if ($item === '' || $item === '0') {
                continue;
            }
            // Terima both "2" dan nama/kode unit ("Madin", "madin", "MTs").
            $id_unit = (int) $item;
            if (!$id_unit) {
                foreach ($valid as $uid) {
                    if (strcasecmp($item, (string) unit_pilihan()[$uid]) === 0
                        || strcasecmp($item, unit_kode($uid)) === 0) {
                        $id_unit = (int) $uid;
                        break;
                    }
                }
            }
            if (in_array($id_unit, $valid, true)) {
                $units[] = $id_unit;
            }
        }
        $units = array_values(array_unique($units));
        if (!$units) {
            $units = array(1);
        }

        $ada_jilid = $CI->db->field_exists('jilid', 'master_siswa_unit');
        $tpq = unit_id_tpq();
        if ($jilid === null && $ada_jilid) {
            $jilid = unit_siswa_jilid($id_siswa);
        }
        $jilid = $ada_jilid ? trim((string) $jilid) : '';
        if ($jilid === '') {
            $jilid = null;
        }

        $CI->db->trans_start();
        $CI->db->delete('master_siswa_unit', array('siswa_id' => $id_siswa));
        foreach ($units as $id_unit) {
            $baris = array(
                'siswa_id' => $id_siswa,
                'unit_id'  => $id_unit,
                'id_tp'    => 0,
                'status'   => 1
            );
            if ($ada_jilid && $id_unit === $tpq && $jilid !== null) {
                $baris['jilid'] = $jilid;
            }
            $CI->db->insert('master_siswa_unit', $baris);
        }
        $CI->db->trans_complete();
        return $CI->db->trans_status() !== false;
    }
}

if (!function_exists('unit_kode')) {
    function unit_kode($id_unit)
    {
        $CI =& get_instance();
        static $kode = array();
        if (!$kode && $CI->db->table_exists('master_unit')) {
            foreach ($CI->db->select('id_unit, kode')->get('master_unit')->result() as $r) {
                $kode[(int) $r->id_unit] = strtolower((string) $r->kode);
            }
        }
        $id_unit = (int) $id_unit;
        return isset($kode[$id_unit]) ? $kode[$id_unit] : (string) $id_unit;
    }
}

if (!function_exists('unit_ikon')) {
    /** Ikon Font Awesome untuk kode unit (mts/madin/tpq), dipakai di tab unit. */
    function unit_ikon($kode)
    {
        $peta = array(
            'mts'   => 'fa-school',
            'madin' => 'fa-book-open',
            'tpq'   => 'fa-quran'
        );
        $kode = strtolower(trim((string) $kode));
        return isset($peta[$kode]) ? $peta[$kode] : 'fa-users';
    }
}

if (!function_exists('unit_siswa_naik_jilid')) {
    /** Naik jilid TPQ: hanya mengubah jilid, keanggotaan unit tetap utuh. */
    function unit_siswa_naik_jilid($id_siswa)
    {
        $CI =& get_instance();
        $id_siswa = (int) $id_siswa;
        if ($id_siswa <= 0 || !$CI->db->table_exists('master_siswa_unit')
            || !$CI->db->field_exists('jilid', 'master_siswa_unit')) {
            return false;
        }
        $sekarang = unit_siswa_jilid($id_siswa);

        // Ikuti urutan nama jilid yang sudah diatur (1..6, Juz Amma, dll.).
        $baru = false;
        $daftar = function_exists('jilid_daftar') ? jilid_daftar() : array();
        foreach ($daftar as $i => $item) {
            if ($item['kode'] === $sekarang && isset($daftar[$i + 1])) {
                $baru = $daftar[$i + 1]['kode'];
                break;
            }
        }
        if ($baru === false) {
            $angka = 0;
            if (preg_match('/(\d+)/', $sekarang, $m)) {
                $angka = (int) $m[1];
            }
            $baru = (string) ($angka + 1);
        }
        $id_tpq = unit_id_tpq();

        $CI->db->where('siswa_id', $id_siswa)
            ->where('unit_id', $id_tpq)
            ->update('master_siswa_unit', array('jilid' => $baru));
        return $CI->db->affected_rows() > 0 ? $baru : false;
    }
}

if (!function_exists('kelas_unit_id')) {
    /** Id master_unit untuk kode unit (mts/madin/tpq). */
    function kelas_unit_id($unit)
    {
        $unit = strtolower(trim((string) $unit));
        if ($unit === 'madin') {
            return function_exists('unit_id_madin') ? (int) unit_id_madin() : 2;
        }
        if ($unit === 'tpq') {
            return function_exists('unit_id_tpq') ? (int) unit_id_tpq() : 3;
        }
        return function_exists('unit_id_mts') ? (int) unit_id_mts() : 1;
    }
}

if (!function_exists('kelas_daftar')) {
    /**
     * Daftar kelas formal (MTs) / kelas Madin yang dipakai sebagai pengelompok
     * di /datasiswa. Nama tampil dan urutan bisa diatur lewat master_kelas_label.
     */
    function kelas_daftar($unit = 'mts')
    {
        $CI =& get_instance();
        $id_unit = kelas_unit_id($unit);
        $ada_label = $CI->db->table_exists('master_kelas_label');

        $sql = "SELECT k.id_kelas,
                       COALESCE(NULLIF(k.kode_kelas, ''), k.nama_kelas) AS kode,
                       k.nama_kelas,
                       " . ($ada_label ? "l.nama AS label, COALESCE(l.urutan, 999) AS urutan" : "NULL AS label, 999 AS urutan") . "
                FROM master_kelas k
                " . ($ada_label
                    ? "LEFT JOIN master_kelas_label l
                          ON l.unit_id = k.unit_id
                         AND l.kode = COALESCE(NULLIF(k.kode_kelas, ''), k.nama_kelas)"
                    : "") . "
                WHERE k.unit_id = " . (int) $id_unit . "
                ORDER BY urutan ASC, kode ASC";
        $baris = $CI->db->query($sql)->result_array();

        $hasil = array();
        foreach ($baris as $r) {
            $kode = trim((string) $r['kode']);
            if ($kode === '') {
                continue;
            }
            $hasil[] = array(
                'kode'     => $kode,
                'nama'     => ($r['label'] !== null && trim((string) $r['label']) !== '')
                    ? (string) $r['label'] : (string) $r['nama_kelas'],
                'urutan'   => (int) $r['urutan'],
                'id_kelas' => (int) $r['id_kelas']
            );
        }
        return $hasil;
    }
}

if (!function_exists('kelas_nama')) {
    /** Nama tampil sebuah kode kelas; kode itu sendiri bila belum diberi nama. */
    function kelas_nama($kode, $unit = 'mts')
    {
        $kode = trim((string) $kode);
        if ($kode === '') {
            return '';
        }
        static $peta = array();
        $kunci = strtolower((string) $unit);
        if (!isset($peta[$kunci])) {
            $peta[$kunci] = array();
            foreach (kelas_daftar($kunci) as $item) {
                $peta[$kunci][$item['kode']] = $item['nama'];
            }
        }
        return isset($peta[$kunci][$kode]) ? $peta[$kunci][$kode] : $kode;
    }
}

if (!function_exists('kelas_sql_urutan')) {
    /** Ekspresi ORDER BY untuk mengelompokkan kelas sesuai urutan setting. */
    function kelas_sql_urutan($unit = 'mts', $alias = 'f')
    {
        $CI =& get_instance();
        $id_unit = (int) kelas_unit_id($unit);
        $alias = preg_replace('/[^A-Za-z0-9_]/', '', (string) $alias);
        if ($alias === '') {
            $alias = 'f';
        }
        $kode = "COALESCE(NULLIF($alias.kode_kelas, ''), $alias.nama_kelas)";
        $awal = "(CASE WHEN $kode IS NULL OR $kode = '' THEN 9999 ELSE ";
        if ($CI->db->table_exists('master_kelas_label')) {
            return $awal . "COALESCE((SELECT l.urutan FROM master_kelas_label l
                        WHERE l.unit_id = " . $id_unit . " AND l.kode = $kode LIMIT 1), 999) END)";
        }
        return $awal . "CASE WHEN $kode REGEXP '^[0-9]+$' THEN CAST($kode AS UNSIGNED) ELSE 999 END END)";
    }
}

if (!function_exists('siswa_kelas_kode')) {
    /** Kode kelas (MTs/Madin) yang sedang dipakai seorang siswa. */
    function siswa_kelas_kode($id_siswa, $unit = 'mts')
    {
        $CI =& get_instance();
        $row = $CI->db->query(
            "SELECT COALESCE(NULLIF(k.kode_kelas, ''), k.nama_kelas) AS kode
             FROM kelas_siswa ks
             JOIN master_kelas k ON k.id_kelas = ks.id_kelas
             WHERE ks.id_siswa = ? AND k.unit_id = ?
             ORDER BY ks.id_kelas_siswa DESC LIMIT 1",
            array((int) $id_siswa, (int) kelas_unit_id($unit))
        )->row();
        return $row ? trim((string) $row->kode) : '';
    }
}

if (!function_exists('siswa_kelas_simpan')) {
    /**
     * Tempatkan siswa langsung ke kelas unit yang dicentang saat tambah/edit.
     * Satu siswa hanya punya satu kelas per unit. Tanpa pilihan, dipakai kelas
     * pertama yang tersedia agar siswa langsung tampil tergelompok di /datasiswa.
     */
    function siswa_kelas_simpan($id_siswa, $pilihan_unit, $id_tp = 0, $id_smt = 0)
    {
        $CI =& get_instance();
        $id_siswa = (int) $id_siswa;
        if ($id_siswa <= 0) {
            return false;
        }

        $mentah = array();
        if (is_array($pilihan_unit)) {
            foreach ($pilihan_unit as $item) {
                foreach (explode(',', (string) $item) as $bagian) {
                    $mentah[] = trim($bagian);
                }
            }
        } elseif ($pilihan_unit !== null && $pilihan_unit !== '') {
            $mentah = explode(',', (string) $pilihan_unit);
        }

        $valid = array_keys(unit_pilihan());
        $units = array();
        foreach ($mentah as $item) {
            $item = trim($item);
            $id_unit = (int) $item;
            if (!$id_unit) {
                foreach ($valid as $uid) {
                    if (strcasecmp($item, (string) unit_nama($uid)) === 0
                        || strcasecmp($item, unit_kode($uid)) === 0) {
                        $id_unit = (int) $uid;
                        break;
                    }
                }
            }
            if (in_array($id_unit, $valid, true)) {
                $units[$id_unit] = $id_unit;
            }
        }
        if (!$units) {
            $units = unit_siswa_unit_ids_by_id($id_siswa);
        }
        if (!$units) {
            $units = array((int) unit_id_mts());
        }

        if ((int) $id_tp <= 0 || (int) $id_smt <= 0) {
            if (!isset($CI->dashboard)) {
                $CI->load->model('Dashboard_model', 'dashboard');
            }
            $tp = $CI->dashboard->getTahunActive();
            $smt = $CI->dashboard->getSemesterActive();
            $id_tp = $tp ? (int) $tp->id_tp : 0;
            $id_smt = $smt ? (int) $smt->id_smt : 0;
        }
        if ((int) $id_tp <= 0 || (int) $id_smt <= 0) {
            return false;
        }

        $berhasil = true;
        foreach (array(unit_id_mts(), unit_id_madin()) as $id_unit) {
            if (!isset($units[$id_unit])) {
                continue;
            }
            $kunci = unit_kode($id_unit) === 'madin' ? 'kelas_madin' : 'kelas_mts';
            $kode = trim((string) $CI->input->post($kunci, true));
            $daftar = kelas_daftar(unit_kode($id_unit));

            $id_kelas = 0;
            $jk = null;
            if ($kode === '' && (int) $id_unit === (int) unit_id_madin()) {
                $jk = $CI->db->select('jenis_kelamin')
                    ->where('id_siswa', $id_siswa)
                    ->get('master_siswa')->row();
            }
            $perempuan = $jk && in_array(
                strtoupper(trim((string) $jk->jenis_kelamin)),
                array('P', '2', 'PEREMPUAN'),
                true
            );
            foreach ($daftar as $item) {
                if ($kode !== '' && $item['kode'] === $kode) {
                    $id_kelas = (int) $item['id_kelas'];
                    break;
                }
                if ($kode === '' && $id_kelas === 0) {
                    $nama = strtolower((string) $item['nama']);
                    $sesuai = ($jk === null)
                        || ($perempuan && strpos($nama, 'putri') !== false)
                        || (!$perempuan && strpos($nama, 'putra') !== false);
                    if ($sesuai) {
                        $id_kelas = (int) $item['id_kelas'];
                    }
                }
            }
            if ($id_kelas <= 0) {
                continue;
            }

            $CI->db->where('id_siswa', $id_siswa)
                ->where('id_tp', (int) $id_tp)
                ->where('id_smt', (int) $id_smt)
                ->where('unit_id', (int) $id_unit)
                ->delete('kelas_siswa');
            $simpan = $CI->db->insert('kelas_siswa', array(
                'id_tp'    => (int) $id_tp,
                'id_smt'   => (int) $id_smt,
                'id_siswa' => $id_siswa,
                'id_kelas' => $id_kelas,
                'unit_id'  => (int) $id_unit
            ));
            if (!$simpan) {
                $berhasil = false;
            }
        }
        return $berhasil;
    }
}

if (!function_exists('siswa_kelas_pastikan')) {
    /**
     * Pastikan siswa hasil import/data lama punya kelas MTs/Madin sehingga
     * langsung tampil tergelompok di /datasiswa. Tidak mengubah kelas yang
     * sudah dipilih. Dipakai juga saat migrasi skema.
     */
    function siswa_kelas_pastikan($id_siswa, $id_tp = 0, $id_smt = 0)
    {
        $CI =& get_instance();
        $id_siswa = (int) $id_siswa;
        if ($id_siswa <= 0) {
            return false;
        }

        if ((int) $id_tp <= 0 || (int) $id_smt <= 0) {
            if (!isset($CI->dashboard)) {
                $CI->load->model('Dashboard_model', 'dashboard');
            }
            $tp = $CI->dashboard->getTahunActive();
            $smt = $CI->dashboard->getSemesterActive();
            $id_tp = $tp ? (int) $tp->id_tp : 0;
            $id_smt = $smt ? (int) $smt->id_smt : 0;
        }
        if ((int) $id_tp <= 0 || (int) $id_smt <= 0) {
            return false;
        }

        $unit_rows = $CI->db->select('unit_id')
            ->where('siswa_id', $id_siswa)
            ->where('status', 1)
            ->get('master_siswa_unit')->result_array();
        $units = array();
        foreach ($unit_rows as $row) {
            $units[(int) $row['unit_id']] = (int) $row['unit_id'];
        }
        if (!$units) {
            $units = array((int) unit_id_mts());
        }

        foreach (array(unit_id_mts(), unit_id_madin()) as $id_unit) {
            if (!isset($units[(int) $id_unit])) {
                continue;
            }
            $ada = $CI->db->where('id_siswa', $id_siswa)
                ->where('unit_id', (int) $id_unit)
                ->where('id_tp', (int) $id_tp)
                ->where('id_smt', (int) $id_smt)
                ->count_all_results('kelas_siswa');
            if ($ada > 0) {
                continue;
            }
            $daftar = kelas_daftar(unit_kode((int) $id_unit));
            if (!$daftar) {
                continue;
            }
            $pilih = $daftar[0]['id_kelas'];
            if ((int) $id_unit === (int) unit_id_madin()) {
                $jk = $CI->db->select('jenis_kelamin')
                    ->where('id_siswa', $id_siswa)
                    ->get('master_siswa')->row();
                $perempuan = $jk && in_array(strtoupper(trim((string) $jk->jenis_kelamin)), array('P', '2', 'PEREMPUAN'), true);
                foreach ($daftar as $item) {
                    $nama = strtolower((string) $item['nama']);
                    if ($perempuan && strpos($nama, 'putri') !== false) {
                        $pilih = $item['id_kelas'];
                        break;
                    }
                    if (!$perempuan && strpos($nama, 'putra') !== false) {
                        $pilih = $item['id_kelas'];
                        break;
                    }
                }
            }
            $CI->db->insert('kelas_siswa', array(
                'id_tp'    => (int) $id_tp,
                'id_smt'   => (int) $id_smt,
                'id_siswa' => $id_siswa,
                'id_kelas' => (int) $pilih,
                'unit_id'  => (int) $id_unit
            ));
        }
        return true;
    }
}
