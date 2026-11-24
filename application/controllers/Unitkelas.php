<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Menentukan unit yayasan (MTs / Madin / TPQ) untuk tiap kelas/rombel.
 * Satu kelas hanya masuk satu unit; keanggotaan murid tetap diatur di Unit Murid.
 */
class Unitkelas extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->helper(array('form', 'url', 'skema'));
        $this->load->library(array('ion_auth', 'session'));

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin()) {
            show_error('Akses Ditolak', 403, 'Akses Ditolak');
        }

        skema_tambahan_pastikan($this->db);
        $this->load->model('Dashboard_model', 'dashboard');
    }

    public function index() {
        $q       = trim((string) $this->input->get('q'));
        $unit_id = (int) $this->input->get('unit_id');
        $kelas   = $this->_cari_kelas($q, $unit_id);

        // Pencarian langsung: kolom pencarian mengirim permintaan setiap kali
        // pengguna berhenti mengetik, dan hanya daftar kelas yang diganti.
        // Halaman tidak dimuat ulang sehingga fokus di kolom pencarian tetap.
        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'html'    => $this->load->view('unitkelas/_rows', array(
                        'kelas'   => $kelas,
                        'q'       => $q,
                        'unit_id' => $unit_id
                    ), true),
                    'jumlah'  => count($kelas),
                    'q'       => $q,
                    'unit_id' => $unit_id
                )));
            return;
        }

        $user = $this->ion_auth->user()->row();

        $data = array(
            'title'      => 'Unit Kelas',
            'judul'      => 'Unit Kelas',
            'user'       => $user,
            'setting'    => $this->dashboard->getSetting(),
            'profile'    => $this->dashboard->getProfileAdmin($user->id),
            'tp'         => $this->dashboard->getTahun(),
            'tp_active'  => $this->dashboard->getTahunActive(),
            'smt'        => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive(),
            'kelas'      => $kelas,
            'q'          => $q,
            'unit_id'    => $unit_id,
            'unit'       => unit_pilihan(true)
        );

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('unitkelas/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    /**
     * Daftar kelas sesuai kata kunci (nama/kode kelas) dan unit terpilih.
     */
    private function _cari_kelas($q, $unit_id) {
        $this->db->select('id_kelas, kode_kelas, nama_kelas, unit_id, id_tp, id_smt');
        $this->db->from('master_kelas');
        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('nama_kelas', $q);
            $this->db->or_like('kode_kelas', $q);
            $this->db->group_end();
        }
        if ($unit_id > 0) {
            $this->db->where('unit_id', $unit_id);
        }
        return $this->db->order_by('id_tp', 'DESC')->order_by('id_smt', 'DESC')
                        ->order_by('nama_kelas', 'ASC')->limit(300)->get()->result();
    }

    public function simpan() {
        if ($this->input->method() !== 'post') {
            redirect('unitkelas');
            return;
        }

        $pilihan = $this->input->post('unit');
        $pilihan = is_array($pilihan) ? $pilihan : array();
        $valid   = array_keys(unit_pilihan());

        $sukses = true;
        $diubah = 0;
        if ($pilihan) {
            $this->db->trans_start();
            foreach ($pilihan as $id_kelas => $unit_id) {
                $id_kelas = (int) $id_kelas;
                $unit_id  = (int) $unit_id;
                if ($id_kelas <= 0 || !in_array($unit_id, $valid, true) || $unit_id <= 0) {
                    continue;
                }
                $ada = $this->db->where('id_kelas', $id_kelas)->count_all_results('master_kelas');
                if (!$ada) {
                    continue;
                }
                $this->db->where('id_kelas', $id_kelas)->update('master_kelas', array('unit_id' => $unit_id));
                $this->db->where('id_kelas', $id_kelas)->update('kelas_siswa', array('unit_id' => $unit_id));
                $diubah++;
            }
            $this->db->trans_complete();
            $sukses = $this->db->trans_status() !== false;
        }

        $this->session->set_flashdata(
            $sukses ? 'success' : 'error',
            $sukses
                ? 'Unit kelas diperbarui (' . $diubah . ' kelas).'
                : 'Gagal menyimpan unit kelas.'
        );

        $query = array();
        if (trim((string) $this->input->post('q')) !== '') {
            $query['q'] = trim((string) $this->input->post('q'));
        }
        if ((int) $this->input->post('unit_id') > 0) {
            $query['unit_id'] = (int) $this->input->post('unit_id');
        }
        redirect('unitkelas' . ($query ? '?' . http_build_query($query) : ''));
    }
}
