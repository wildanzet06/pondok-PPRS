<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengelolaan keanggotaan unit yayasan (MTs / Madin / TPQ) untuk murid.
 * Satu murid bisa mencentang lebih dari satu unit, misalnya MTs + Madin.
 */
class Unitsiswa extends CI_Controller {

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
        $user = $this->ion_auth->user()->row();

        $data = array(
            'title'      => 'Unit Murid',
            'judul'      => 'Unit Murid',
            'user'       => $user,
            'setting'    => $this->dashboard->getSetting(),
            'profile'    => $this->dashboard->getProfileAdmin($user->id),
            'tp'         => $this->dashboard->getTahun(),
            'tp_active'  => $this->dashboard->getTahunActive(),
            'smt'        => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive()
        );

        $q = trim((string) $this->input->get('q'));
        $this->db->select('id_siswa, nis, nama, username');
        $this->db->from('master_siswa');
        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('nama', $q);
            $this->db->or_like('nis', $q);
            $this->db->or_like('username', $q);
            $this->db->group_end();
        }
        $data['siswa'] = $this->db->order_by('nama', 'ASC')->limit(200)->get()->result();
        $data['q']     = $q;
        $data['unit']  = unit_pilihan();

        $data['keanggotaan'] = array();
        foreach ($this->db->select('siswa_id, unit_id')
                     ->where('status', 1)
                     ->get('master_siswa_unit')->result() as $r) {
            $data['keanggotaan'][(int) $r->siswa_id][(int) $r->unit_id] = true;
        }

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('unitsiswa/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function simpan() {
        if ($this->input->method() !== 'post') {
            redirect('unitsiswa');
            return;
        }

        $pilihan = $this->input->post('unit');
        $pilihan = is_array($pilihan) ? $pilihan : array();
        $valid_unit = array_keys(unit_pilihan());

        $diubah = 0;
        $sukses = true;
        if ($pilihan) {
            $ids = array_map('intval', array_keys($pilihan));
            $ada = array();
            foreach ($this->db->select('id_siswa')->where_in('id_siswa', $ids)
                         ->get('master_siswa')->result() as $r) {
                $ada[] = (int) $r->id_siswa;
            }

            $this->db->trans_start();
            foreach ($ada as $id_siswa) {
                $units = array_map('intval', (array) $pilihan[$id_siswa]);
                $units = array_values(array_unique(array_intersect($units, $valid_unit)));
                $units = array_values(array_filter($units, function ($u) { return $u > 0; }));

                $this->db->delete('master_siswa_unit', array('siswa_id' => $id_siswa));
                foreach ($units as $unit_id) {
                    $this->db->insert('master_siswa_unit', array(
                        'siswa_id' => $id_siswa,
                        'unit_id'  => $unit_id,
                        'id_tp'    => 0,
                        'status'   => 1
                    ));
                }
                $diubah++;
            }
            $this->db->trans_complete();
            $sukses = $this->db->trans_status() !== false;
        }

        $this->session->set_flashdata(
            $sukses ? 'success' : 'error',
            $sukses
                ? 'Unit murid diperbarui (' . $diubah . ' murid).'
                : 'Gagal menyimpan unit murid.'
        );

        $q = trim((string) $this->input->post('q'));
        redirect('unitsiswa' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }
}
