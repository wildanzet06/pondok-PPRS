<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tombol tampil/sembunyi fitur yang disembunyikan.
 * Hanya admin yang bisa mengubahnya dan statusnya berlaku untuk sesi ini saja.
 */
class Fitur extends CI_Controller {

    public function toggle()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_404();
        }

        $aktif = $this->session->userdata('tampilkan_fitur_tersembunyi') === '1';
        $this->session->set_userdata('tampilkan_fitur_tersembunyi', $aktif ? '0' : '1');

        $referer = (string) $this->input->server('HTTP_REFERER');
        if ($referer === '' || strpos($referer, site_url()) !== 0) {
            $referer = site_url('dashboard');
        }

        redirect($referer);
    }
}
