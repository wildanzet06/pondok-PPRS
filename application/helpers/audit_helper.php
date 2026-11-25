<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper jejak audit (siapa yang menginput pembayaran/kas).
 *
 * Data lama menyimpan username di kolom dibuat_oleh/diverifikasi_oleh.
 * Helper ini menerjemahkan username itu ke nama lengkap dari tabel users,
 * dengan fallback ke username bila akunnya sudah dihapus.
 */

if (!function_exists('nama_lengkap_user')) {
    function nama_lengkap_user($user)
    {
        if (is_string($user) || is_numeric($user)) {
            $username = trim((string) $user);
            if ($username === '') {
                return '-';
            }

            static $cache = array();
            if (array_key_exists($username, $cache)) {
                return $cache[$username];
            }

            $CI =& get_instance();
            $row = $CI->db->select('username, first_name, last_name')
                ->where('username', $username)
                ->get('users')
                ->row();

            if (!$row) {
                return $cache[$username] = $username;
            }

            $depan = trim((string) $row->first_name);
            $belakang = trim((string) $row->last_name);
            $nama = ($depan !== '' && $depan === $belakang)
                ? $depan
                : trim($depan . ' ' . $belakang);
            return $cache[$username] = ($nama !== '' ? $nama : (string) $row->username);
        }

        if (is_object($user)) {
            $depan = isset($user->first_name) ? trim((string) $user->first_name) : '';
            $belakang = isset($user->last_name) ? trim((string) $user->last_name) : '';
            $username = isset($user->username) ? trim((string) $user->username) : '';
        } elseif (is_array($user)) {
            $depan = isset($user['first_name']) ? trim((string) $user['first_name']) : '';
            $belakang = isset($user['last_name']) ? trim((string) $user['last_name']) : '';
            $username = isset($user['username']) ? trim((string) $user['username']) : '';
        } else {
            return '-';
        }

        $nama = ($depan !== '' && $depan === $belakang)
            ? $depan
            : trim($depan . ' ' . $belakang);
        if ($nama === '') {
            $nama = $username;
        }

        return $nama !== '' ? $nama : '-';
    }
}

if (!function_exists('nama_penginput')) {
    /** Nama yang ditampilkan: pakai nama yang disimpan saat transaksi, atau terjemahkan username lama. */
    function nama_penginput($username, $nama_tersimpan = null)
    {
        $tersimpan = trim((string) $nama_tersimpan);
        if ($tersimpan !== '' && $tersimpan !== '-') {
            return $tersimpan;
        }
        return nama_lengkap_user($username);
    }
}

if (!function_exists('badge_penginput')) {
    /** Badge kecil di bawah nominal untuk menunjukkan siapa yang menginput. */
    function badge_penginput($username, $nama_tersimpan = null, $label = 'Dicatat oleh')
    {
        $nama = nama_penginput($username, $nama_tersimpan);
        if ($nama === '' || $nama === '-') {
            return '';
        }

        $judul = html_escape($label . ': ' . $nama);
        return '<div class="mt-1"><span class="badge badge-light border text-dark font-weight-normal" '
            . 'title="' . $judul . '"><i class="fas fa-user-circle mr-1"></i>'
            . html_escape($nama) . '</span></div>';
    }
}

if (!function_exists('badges_penginput')) {
    /** Beberapa badge dari daftar username/nama yang sudah digabung (pipe). */
    function badges_penginput($daftar, $label = 'Diisi oleh')
    {
        if (is_string($daftar)) {
            $daftar = explode('|', $daftar);
        }
        $nama = array();
        foreach ((array) $daftar as $item) {
            $item = nama_lengkap_user($item);
            if ($item !== '' && $item !== '-' && !in_array($item, $nama, true)) {
                $nama[] = $item;
            }
        }
        if (!$nama) {
            return '<span class="text-muted">-</span>';
        }

        $html = '';
        foreach ($nama as $satu) {
            $html .= badge_penginput($satu, $satu, $label);
        }
        return $html;
    }
}
