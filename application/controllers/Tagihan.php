<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tagihan extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'text', 'wa'));
        $this->load->library(array('ion_auth', 'session'));

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        if (!$this->ion_auth->is_admin()) {
            show_error('Akses Ditolak', 403, 'Akses Ditolak');
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Tagihan_model');
    }

    // =====================================================================
    // HELPER PRIVATE
    // =====================================================================
    private function _admin_data($title) {
        $user = $this->ion_auth->user()->row();

        $data['title']      = $title;
        $data['judul']      = $title;
        $data['user']       = $user;
        $data['setting']    = $this->dashboard->getSetting();
        $data['profile']    = $this->dashboard->getProfileAdmin($user->id);
        $data['tp']         = $this->dashboard->getTahun();
        $data['tp_active']  = $this->dashboard->getTahunActive();
        $data['smt']        = $this->dashboard->getSemester();
        $data['smt_active'] = $this->dashboard->getSemesterActive();

        return $data;
    }

    private function _render_admin($view, $data) {
        // Sidebar & navbar sudah di-include di dalam _header.php
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    // Aksi yang mengubah data wajib lewat POST
    private function _harus_post($kembali = 'tagihan') {
        if ($this->input->method() !== 'post') {
            redirect($kembali);
            exit;
        }
    }

    // =====================================================================
    // DAFTAR TAGIHAN
    // =====================================================================
    public function index() {
        $filter = array(
            'jenis_id' => $this->input->get('jenis_id'),
            'bulan'    => $this->input->get('bulan'),
            'tahun'    => $this->input->get('tahun'),
            'status'   => $this->input->get('status'),
            'unit_id'  => $this->input->get('unit_id')
        );

        // Tab unit memakai id yang sama dengan master_unit, bukan id hardcoded.
        $unit_tab = array(
            (int) unit_id_mts()   => 'MTs',
            (int) unit_id_madin() => 'Madin',
            (int) unit_id_tpq()   => 'TPQ'
        );
        $unit_diminta = (int) $filter['unit_id'];
        $unit_aktif = isset($unit_tab[$unit_diminta]) ? $unit_diminta : (int) unit_id_mts();
        $filter['unit_id'] = $unit_aktif;

        $data = $this->_admin_data('Daftar Tagihan');
        $data['filter']       = $filter;
        $data['unit']         = unit_pilihan(true);
        $data['unit_tab']     = $unit_tab;
        $data['unit_aktif']   = $unit_aktif;
        $data['jenis']        = $this->Tagihan_model->jenis_all();
        $this->load->model('Pembayaran_model');
        $setting_spp = $this->Pembayaran_model->setting_spp();
        $data['rekening']     = $this->Pembayaran_model->rekening_all();
        $data['instruksi_wa'] = ($setting_spp && !empty($setting_spp->instruksi_transfer)) ? $setting_spp->instruksi_transfer : '';
        $data['tagihan']      = $this->Tagihan_model->list_tagihan($filter);
        $data['daftar_bulan'] = $this->Tagihan_model->daftar_bulan;

        $this->_render_admin('tagihan/index', $data);
    }

    public function hapus($id) {
        $this->_harus_post();

        if ($this->Tagihan_model->hapus_tagihan($id)) {
            $this->session->set_flashdata('success', 'Tagihan dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Tagihan tidak dapat dihapus karena sudah ada pembayaran yang tercatat.');
        }
        redirect('tagihan');
    }

    // Ubah diskon satu tagihan (misalnya siswa baru diberi keringanan)
    public function ubah_diskon($id) {
        $this->_harus_post();

        $diskon = (int) preg_replace('/\D/', '', (string) $this->input->post('diskon'));
        if ($this->Tagihan_model->ubah_diskon($id, $diskon)) {
            $this->session->set_flashdata('success', 'Diskon tagihan diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Tagihan tidak ditemukan.');
        }

        // Kembali ke halaman dengan filter yang sama (hanya kunci yang dikenal)
        parse_str((string) $this->input->post('qs'), $qs);
        $aman = array();
        foreach (array('jenis_id', 'bulan', 'tahun', 'status', 'unit_id') as $k) {
            if (!empty($qs[$k])) $aman[$k] = $qs[$k];
        }
        redirect('tagihan' . ($aman ? '?' . http_build_query($aman) : ''));
    }

    // Simpan no. HP/WhatsApp wali dari halaman tagihan, supaya tombol WA
    // bisa mengirim pengingat langsung ke nomor yang benar.
    public function simpan_hp($siswa_id) {
        $this->_harus_post('tagihan');

        $siswa_id = (int) $siswa_id;
        $hp = preg_replace('/\D+/', '', (string) $this->input->post('hp', true));

        if ($siswa_id <= 0 || strlen($hp) < 8 || strlen($hp) > 15) {
            $this->session->set_flashdata('error', 'No. HP/WhatsApp wali tidak valid.');
        } else {
            $ada = $this->db->where('id_siswa', $siswa_id)->count_all_results('master_siswa') > 0;
            if (!$ada) {
                $this->session->set_flashdata('error', 'Data siswa tidak ditemukan.');
            } else {
                $this->db->where('id_siswa', $siswa_id)->update('master_siswa', array('hp' => $hp));
                $this->session->set_flashdata('success', 'No. HP wali diperbarui. Tombol WA sekarang mengirim ke nomor ini.');
            }
        }

        parse_str((string) $this->input->post('qs'), $qs);
        $aman = array();
        foreach (array('jenis_id', 'bulan', 'tahun', 'status', 'unit_id') as $k) {
            if (!empty($qs[$k])) $aman[$k] = $qs[$k];
        }
        redirect('tagihan' . ($aman ? '?' . http_build_query($aman) : ''));
    }

    // =====================================================================
    // JENIS TAGIHAN
    // =====================================================================
    public function jenis() {
        $edit_id = (int) $this->input->get('edit');

        $data = $this->_admin_data('Jenis Tagihan');
        $data['unit']         = unit_pilihan(true);
        $data['daftar_jenis'] = $this->Tagihan_model->jenis_all();
        $data['edit']         = $edit_id ? $this->Tagihan_model->jenis_by_id($edit_id) : null;

        $this->_render_admin('tagihan/jenis', $data);
    }

    public function jenis_simpan() {
        $this->_harus_post('tagihan/jenis');

        $user = $this->ion_auth->user()->row();
        $id      = (int) $this->input->post('id');
        $kode    = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string) $this->input->post('kode')));
        $nama    = trim((string) $this->input->post('nama'));
        $tipe    = $this->input->post('tipe');
        $nominal = (int) preg_replace('/\D/', '', (string) $this->input->post('nominal_default'));
        $ket     = trim((string) $this->input->post('keterangan'));
        $aktif   = $this->input->post('aktif') ? 1 : 0;
        $cicil   = $this->input->post('boleh_cicil') ? 1 : 0;
        $maks    = (int) $this->input->post('maks_cicilan');
        if ($maks < 0 || $maks > 36) {
            $maks = 0;
        }
        $unit    = (int) $this->input->post('unit_id');
        if (!array_key_exists($unit, unit_pilihan())) {
            $unit = 0; // 0 = berlaku untuk semua unit
        }
        $seikhlasnya = $this->input->post('seikhlasnya') ? 1 : 0;

        if (!in_array($tipe, array('bulanan', 'sekali'))) {
            $tipe = 'sekali';
        }
        // Tagihan bulanan (SPP) tidak dicicil
        if ($tipe == 'bulanan') {
            $cicil = 0;
        }
        // Tanpa izin cicilan, batas cicilan tidak relevan
        if (!$cicil) {
            $maks = 0;
        }
        // Seikhlasnya: nominal ditentukan wali santri, jadi tidak memakai
        // nominal bawaan maupun cicilan.
        if ($seikhlasnya) {
            $nominal = 0;
            $cicil   = 0;
            $maks    = 0;
        }

        $balik = 'tagihan/jenis' . ($id ? '?edit=' . $id : '');

        if ($kode === '' || $nama === '') {
            $this->session->set_flashdata('error', 'Kode dan nama wajib diisi.');
            redirect($balik);
            return;
        }
        if ($this->Tagihan_model->kode_dipakai($kode, $id)) {
            $this->session->set_flashdata('error', 'Kode "' . $kode . '" sudah dipakai jenis lain.');
            redirect($balik);
            return;
        }

        // Jejak audit: created by hanya diisi saat insert, last edited saat update.
        $audit = $id
            ? array(
                'diubah_oleh'     => $user ? $user->username : null,
                'diubah_oleh_nama' => $user ? nama_lengkap_user($user) : null
            )
            : array(
                'dibuat_oleh'     => $user ? $user->username : null,
                'dibuat_oleh_nama' => $user ? nama_lengkap_user($user) : null
            );

        $this->Tagihan_model->jenis_simpan(array_merge(array(
            'kode'            => $kode,
            'nama'            => mb_substr($nama, 0, 100),
            'tipe'            => $tipe,
            'nominal_default' => $nominal,
            'boleh_cicil'     => $cicil,
            'maks_cicilan'    => $maks,
            'seikhlasnya'     => $seikhlasnya,
            'unit_id'         => $unit,
            'keterangan'      => ($ket !== '') ? mb_substr($ket, 0, 255) : null,
            'aktif'           => $aktif
        ), $audit), $id);

        $this->session->set_flashdata('success', 'Jenis tagihan disimpan.');
        redirect('tagihan/jenis');
    }

    public function jenis_hapus($id) {
        $this->_harus_post('tagihan/jenis');

        $hasil = $this->Tagihan_model->jenis_hapus($id);
        if ($hasil == 'dihapus') {
            $this->session->set_flashdata('success', 'Jenis tagihan dihapus.');
        } else {
            $this->session->set_flashdata('success', 'Jenis tagihan sudah pernah dipakai, jadi dinonaktifkan (tidak dihapus).');
        }
        redirect('tagihan/jenis');
    }

    // =====================================================================
    // KERINGANAN SISWA
    // =====================================================================
    public function keringanan() {
        $edit_id = (int) $this->input->get('edit');

        $data = $this->_admin_data('Keringanan Siswa');
        $data['daftar']  = $this->Tagihan_model->keringanan_all();
        $data['siswa']   = $this->Tagihan_model->siswa_all();
        $data['jenis']   = $this->Tagihan_model->jenis_all();
        $data['edit']    = $edit_id ? $this->Tagihan_model->keringanan_by_id($edit_id) : null;

        $this->_render_admin('tagihan/keringanan', $data);
    }

    public function keringanan_simpan() {
        $this->_harus_post('tagihan/keringanan');

        $user = $this->ion_auth->user()->row();
        $id       = (int) $this->input->post('id');
        $siswa_id = (int) $this->input->post('siswa_id');
        $jenis_id = (int) $this->input->post('jenis_id'); // 0 = semua jenis
        $tipe     = $this->input->post('tipe');
        $nilai    = (int) preg_replace('/\D/', '', (string) $this->input->post('nilai'));
        $ket      = trim((string) $this->input->post('keterangan'));
        $aktif    = $this->input->post('aktif') ? 1 : 0;

        if (!in_array($tipe, array('persen', 'nominal'))) {
            $tipe = 'persen';
        }

        $balik = 'tagihan/keringanan' . ($id ? '?edit=' . $id : '');

        if (!$siswa_id) {
            $this->session->set_flashdata('error', 'Pilih siswa terlebih dahulu.');
            redirect($balik);
            return;
        }
        if ($nilai <= 0 || ($tipe == 'persen' && $nilai > 100)) {
            $this->session->set_flashdata('error', $tipe == 'persen'
                ? 'Persentase harus antara 1 sampai 100 (100 = gratis).'
                : 'Nominal potongan harus lebih dari 0.');
            redirect($balik);
            return;
        }

        $audit = $id
            ? array(
                'diubah_oleh'      => $user ? $user->username : null,
                'diubah_oleh_nama' => $user ? nama_lengkap_user($user) : null
            )
            : array(
                'dibuat_oleh'      => $user ? $user->username : null,
                'dibuat_oleh_nama' => $user ? nama_lengkap_user($user) : null
            );

        $this->Tagihan_model->keringanan_simpan(array_merge(array(
            'siswa_id'   => $siswa_id,
            'jenis_id'   => $jenis_id ? $jenis_id : null,
            'tipe'       => $tipe,
            'nilai'      => $nilai,
            'keterangan' => ($ket !== '') ? mb_substr($ket, 0, 255) : null,
            'aktif'      => $aktif
        ), $audit), $id);

        $this->session->set_flashdata('success',
            'Keringanan disimpan. Berlaku untuk tagihan yang dibuat setelah ini. '
            . 'Untuk tagihan yang sudah ada, ubah diskonnya di Daftar Tagihan.');
        redirect('tagihan/keringanan');
    }

    public function keringanan_hapus($id) {
        $this->_harus_post('tagihan/keringanan');

        $this->Tagihan_model->keringanan_hapus($id);
        $this->session->set_flashdata('success', 'Keringanan dihapus (tagihan yang sudah dibuat tidak berubah).');
        redirect('tagihan/keringanan');
    }

    // =====================================================================
    // BUAT TAGIHAN MASSAL
    // =====================================================================
    public function buat() {
        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $data = $this->_admin_data('Buat Tagihan');
        $data['jenis']          = $this->Tagihan_model->jenis_all(true);
        $data['unit']           = unit_pilihan(true);
        $data['daftar_bulan']   = $this->Tagihan_model->daftar_bulan;
        $data['total_siswa']    = $this->Tagihan_model->count_siswa();
        $data['aktif_tersedia'] = $this->Tagihan_model->siswa_aktif_tersedia() && $tp && $smt;
        $data['total_aktif']    = $data['aktif_tersedia']
            ? $this->Tagihan_model->count_siswa_aktif($tp->id_tp, $smt->id_smt) : 0;
        $data['daftar_kelas']   = $data['aktif_tersedia']
            ? $this->Tagihan_model->daftar_kelas_aktif($tp->id_tp, $smt->id_smt) : array();
        $data['unit_mts']       = (int) unit_id_mts();

        $this->_render_admin('tagihan/buat', $data);
    }

    // Pratinjau sasaran siswa sesuai unit yang dipilih. Memakai fungsi yang
    // sama dengan buat_proses(), jadi daftar yang tampil = daftar yang dibuat.
    public function siswa_target_ajax() {
        $unit_id = (int) $this->input->get('unit_id');
        if (!array_key_exists($unit_id, unit_pilihan())) {
            $unit_id = 0;
        }
        $target = $this->input->get('target') === 'aktif' ? 'aktif' : 'semua';
        $id_kelas = (int) $this->input->get('id_kelas');

        // Madin/TPQ tidak memakai rombel, jadi sasaran "aktif di kelas" tidak relevan.
        $mts = (int) unit_id_mts();
        $bisa_aktif = ($unit_id === 0 || $unit_id === $mts) && $this->Tagihan_model->siswa_aktif_tersedia();
        if (!$bisa_aktif) {
            $target = 'semua';
        }

        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $id_tp  = $tp ? (int) $tp->id_tp : 0;
        $id_smt = $smt ? (int) $smt->id_smt : 0;

        $ids_semua = $this->Tagihan_model->id_siswa_target('semua', 0, $id_tp, $id_smt, $unit_id);
        $ids_aktif = $bisa_aktif
            ? $this->Tagihan_model->id_siswa_target('aktif', $id_kelas, $id_tp, $id_smt, $unit_id)
            : array();
        $ids = $target === 'aktif' ? $ids_aktif : $ids_semua;

        $siswa = array();
        if ($ids) {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $tempat = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT s.id_siswa, s.nis, s.nama
                    FROM master_siswa s
                    WHERE s.id_siswa IN ($tempat)
                    ORDER BY s.nama ASC";
            foreach ($this->db->query($sql, $ids)->result() as $r) {
                $siswa[] = array(
                    'id_siswa' => (int) $r->id_siswa,
                    'nis'      => $r->nis,
                    'nama'     => $r->nama,
                    'kelas'    => ''
                );
            }

            if ($this->Tagihan_model->siswa_aktif_tersedia() && $id_tp > 0 && $id_smt > 0) {
                $sql_kelas = "SELECT ks.id_siswa, GROUP_CONCAT(DISTINCT mk.nama_kelas ORDER BY mk.nama_kelas SEPARATOR ', ') AS kelas
                              FROM kelas_siswa ks
                              JOIN master_kelas mk ON mk.id_kelas = ks.id_kelas
                              WHERE ks.id_siswa IN ($tempat) AND ks.id_tp = ? AND ks.id_smt = ?
                              GROUP BY ks.id_siswa";
                $bind = array_merge($ids, array($id_tp, $id_smt));
                $kelas = array();
                foreach ($this->db->query($sql_kelas, $bind)->result() as $r) {
                    $kelas[(int) $r->id_siswa] = $r->kelas;
                }
                foreach ($siswa as &$s) {
                    $s['kelas'] = isset($kelas[$s['id_siswa']]) ? $kelas[$s['id_siswa']] : '';
                }
                unset($s);
            }
        }

        $hasil = array(
            'unit_id'     => $unit_id,
            'unit_nama'   => unit_nama($unit_id),
            'target'      => $target,
            'bisa_aktif'  => $bisa_aktif,
            'total'       => count($ids),
            'total_semua' => count($ids_semua),
            'total_aktif' => count($ids_aktif),
            'siswa'       => array_slice($siswa, 0, 300)
        );

        $this->output->set_content_type('application/json')->set_output(json_encode($hasil));
    }

    public function buat_proses() {
        $this->_harus_post('tagihan/buat');

        $jenis = $this->Tagihan_model->jenis_by_id((int) $this->input->post('jenis_id'));
        if (!$jenis || !$jenis->aktif) {
            $this->session->set_flashdata('error', 'Jenis tagihan tidak valid.');
            redirect('tagihan/buat');
            return;
        }

        $seikhlasnya = !empty($jenis->seikhlasnya);
        $bulan    = $this->input->post('bulan');
        $tahun    = (int) $this->input->post('tahun');
        $nominal  = $seikhlasnya ? 0 : (int) preg_replace('/\D/', '', (string) $this->input->post('nominal'));
        $ket      = trim((string) $this->input->post('keterangan'));
        $tempo    = trim((string) $this->input->post('jatuh_tempo'));
        $id_kelas = (int) $this->input->post('id_kelas');
        $unit_id  = (int) $this->input->post('unit_id');
        if (!array_key_exists($unit_id, unit_pilihan())) {
            $unit_id = 0;
        }
        // Madin/TPQ tidak punya rombel; paksa sasaran ke semua siswa di unit itu.
        $target = $this->input->post('target') == 'aktif' ? 'aktif' : 'semua';
        if ($unit_id !== 0 && $unit_id !== (int) unit_id_mts()) {
            $target = 'semua';
        }

        if (!$seikhlasnya && $nominal <= 0) {
            $this->session->set_flashdata('error', 'Nominal harus lebih dari 0.');
            redirect('tagihan/buat');
            return;
        }
        if ($jenis->tipe == 'bulanan') {
            if (!in_array($bulan, $this->Tagihan_model->daftar_bulan) || $tahun < 2000 || $tahun > 2100) {
                $this->session->set_flashdata('error', 'Bulan atau tahun tidak valid.');
                redirect('tagihan/buat');
                return;
            }
        }
        if ($tempo !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $tempo);
            if (!$d || $d->format('Y-m-d') !== $tempo) {
                $this->session->set_flashdata('error', 'Format jatuh tempo tidak valid.');
                redirect('tagihan/buat');
                return;
            }
        }

        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $ids = $this->Tagihan_model->id_siswa_target(
            $target, $id_kelas,
            $tp ? $tp->id_tp : 0, $smt ? $smt->id_smt : 0,
            $unit_id
        );
        if (empty($ids)) {
            $this->session->set_flashdata('error', 'Tidak ada siswa yang cocok dengan pilihan sasaran.');
            redirect('tagihan/buat');
            return;
        }

        $hasil = $this->Tagihan_model->buat_massal($jenis, $bulan, $tahun, $nominal, $tempo, mb_substr($ket, 0, 255), $ids);

        $pesan = 'Selesai: ' . $hasil['dibuat'] . ' tagihan dibuat';
        if ($hasil['keringanan'] > 0) {
            $pesan .= ' (' . $hasil['keringanan'] . ' berkeringanan, ' . $hasil['gratis'] . ' gratis)';
        }
        $pesan .= ', ' . $hasil['dilewati'] . ' dilewati karena sudah ada.';
        $this->session->set_flashdata('success', $pesan);

        $qs = array('jenis_id' => $jenis->id);
        if ($jenis->tipe == 'bulanan') {
            $qs['bulan'] = $bulan;
            $qs['tahun'] = $tahun;
        }
        redirect('tagihan?' . http_build_query($qs));
    }
}
