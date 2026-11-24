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
