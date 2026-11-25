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
