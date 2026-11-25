<style>
    .siswa-tools { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: flex-end; }
    .card-header .card-tools.siswa-tools > * { margin: 0; }
    .siswa-tools .btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; border-radius: 6px; white-space: nowrap; }

    .siswa-atas { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .4rem; }
    .siswa-atas .dataTables_length,
    .siswa-atas .dataTables_length label { margin: 0; }
    .siswa-atas .siswa-search { flex: 1 1 240px; max-width: 380px; margin-left: auto; }

    .siswa-filterbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; margin-bottom: .6rem; }
    .siswa-filterbar > .dropdown { flex: 0 0 auto; }
    .siswa-filterbar > .dropdown + .siswa-filter { margin-left: auto; }
    .siswa-filter { display: flex; align-items: center; gap: .4rem; min-width: 0; }
    .siswa-filter > label { margin: 0; white-space: nowrap; font-size: .875rem; color: #495057; }
    .siswa-filter > .form-control { flex: 1 1 auto; width: auto; min-width: 7.5rem; }
    #baris-jilid, #baris-kelas { display: none; }
    #baris-jilid.tpq, #baris-kelas.mts, #baris-kelas.madin { display: flex; }
    #atur-jilid, #atur-kelas, #naik-kelas-massal { flex: 0 0 auto; white-space: nowrap; }

    .siswa-search { max-width: 380px; margin-left: auto; }
    .siswa-search .input-group-text { background: #fff; }

    @media (max-width: 575.98px) {
        .card-header .card-tools.siswa-tools {
            float: none;
            clear: both;
            width: 100%;
            margin-top: 0;
            padding-top: .45rem;
            justify-content: space-between;
        }
        .card-header .card-tools.siswa-tools > .btn {
            flex: 1 1 0;
            min-width: 0;
            white-space: nowrap;
            padding: .4rem .3rem;
            font-size: .78rem;
        }
        .siswa-atas .siswa-search { flex: 1 1 100%; max-width: none; margin-left: 0; }
        #baris-jilid, #baris-kelas { flex: 1 1 100%; }
        #atur-jilid, #atur-kelas, #naik-kelas-massal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 31px;
            padding-left: 0;
            padding-right: 0;
        }
        .dataTables_filter { text-align: left !important; }
        .dataTables_length { margin-bottom: 0; }
    }
    .jilid-group td, .kelas-group td {
        background: #e9ecef;
        color: #495057;
        font-weight: 600;
        padding: .4rem .75rem;
        border-top: 1px solid #dee2e6;
    }
    .jilid-group:first-child td, .kelas-group:first-child td { border-top: 0; }
</style><div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title">Daftar Siswa</h6>
                    <div class="card-tools siswa-tools">
                        <button type="button" data-toggle="modal" data-target="#createSiswaModal"
                                class="btn btn-sm btn-primary" title="Tambah siswa">
                            <i class="fas fa-plus"></i><span>Tambah</span>
                        </button>
                        <a href="<?= base_url('datasiswa/add') ?>" class="btn btn-sm btn-success"
                           title="Import data siswa">
                            <i class="fas fa-upload"></i><span>Import</span></a>
                        <a href="<?= base_url('datasiswa/update') ?>" class="btn btn-sm btn-success"
                           title="Update data siswa">
                            <i class="fas fa-database"></i><span>Update</span></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small mb-3">
                        Pilih <b>Unit Murid</b> (MTs / Madin / TPQ) langsung saat tambah atau edit siswa.
                        Murid yang juga Madin/TPQ tetap tampil di tab <b>MTs</b> dengan badge unit;
                        tab Madin/TPQ hanya berisi murid tanpa unit MTs. Siswa langsung ditempatkan ke
                        kelas yang dipilih di form, lalu otomatis dikelompokkan di halaman ini.
                        Tab <b>MTs</b> dan <b>Madin</b> memakai filter <b>Kelas</b> dan tombol
                        <b>setting</b> untuk mengubah nama/urutan kelas (Madin: Putra 1-3, Putri 1-3).
                        Tab <b>MTs</b> juga punya tombol <b>Naik Kelas</b> untuk memindahkan banyak siswa
                        sekaligus ke kelas berikutnya. Tab <b>TPQ</b> memakai filter <b>Jilid</b> dan setting nama jilid.
                    </div>
                    <ul class="nav nav-tabs unit-tabs mb-3" id="siswaUnitTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link unit-tab-mts active" href="#" data-unit="mts" role="tab"
                               aria-selected="true">
                                <i class="fas <?= unit_ikon('mts'); ?>"></i> MTs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link unit-tab-madin" href="#" data-unit="madin" role="tab"
                               aria-selected="false">
                                <i class="fas <?= unit_ikon('madin'); ?>"></i> Madin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link unit-tab-tpq" href="#" data-unit="tpq" role="tab"
                               aria-selected="false">
                                <i class="fas <?= unit_ikon('tpq'); ?>"></i> TPQ
                            </a>
                        </li>
                    </ul>
                    <div class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="siswa-atas">
                            <div class="siswa-search">
                                <div class="input-group input-group-sm">
                                    <input id="input-search" type="search" class="form-control form-control-sm"
                                           placeholder="Ketik nama / NIS untuk menyaring..." autocomplete="off"
                                           aria-label="Cari nama atau NIS siswa">
                                    <div class="input-group-append d-none" id="search-loading">
                                        <span class="input-group-text"><i class="fas fa-circle-notch fa-spin"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="siswa-filterbar">
                                    <!--
                                    <button id="hapusterpilih" onclick="bulk_delete()" type="button" class="btn btn-danger" data-toggle="tooltip" title="Hapus Terpilh" disabled="disabled">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                    -->
                                    <div class="dropdown">
                                        <button id="dropdown-btn" class="btn btn-sm btn-danger dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" disabled="disabled">
                                            Aksi
                                        </button>
                                        <div id="dropdown-action" class="dropdown-menu">
                                            <a class="dropdown-item" id="pindah" href="#">Set sebagai PINDAH</a>
                                            <a class="dropdown-item" id="keluar" href="#">Set sebagai KELUAR</a>
                                            <a class="dropdown-item" id="hapus" href="#">HAPUS</a>
                                        </div>
                                    </div>
                                    <div class="siswa-filter">
                                        <label for="users-filter">Filter</label>
                                        <select id="users-filter" class="form-control form-control-sm">
                                            <option value="1">Aktif</option>
                                            <option value="5">Tanpa Kelas</option>
                                            <option value="3">Pindah</option>
                                            <option value="4">Keluar</option>
                                        </select>
                                    </div>
                                    <div class="siswa-filter" id="baris-jilid">
                                        <label for="jilid-filter">Jilid</label>
                                        <select id="jilid-filter" class="form-control form-control-sm">
                                            <option value="">Semua Jilid</option>
                                        </select>
                                        <button type="button" id="atur-jilid"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Atur nama jilid TPQ"
                                                aria-label="Atur nama jilid TPQ">
                                            <i class="fas fa-cog"></i><span class="d-none d-sm-inline ml-1">setting</span>
                                        </button>
                                    </div>
                                    <div class="siswa-filter" id="baris-kelas">
                                        <label for="kelas-filter">Kelas</label>
                                        <select id="kelas-filter" class="form-control form-control-sm">
                                            <option value="">Semua Kelas</option>
                                        </select>
                                        <button type="button" id="atur-kelas"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Atur nama dan urutan kelas"
                                                aria-label="Atur nama dan urutan kelas">
                                            <i class="fas fa-cog"></i><span class="d-none d-sm-inline ml-1">setting</span>
                                        </button>
                                        <button type="button" id="naik-kelas-massal"
                                                class="btn btn-sm btn-outline-success d-none"
                                                title="Naikkan kelas siswa MTs secara massal"
                                                aria-label="Naikkan kelas siswa MTs secara massal">
                                            <i class="fas fa-arrow-up"></i><span class="d-none d-sm-inline ml-1">Naik Kelas</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <?= form_open('datasiswa/delete', array('id' => 'bulk')); ?>
                                <div class="table-responsive">
                                    <table id="table-siswa" class="w-100 table table-md table-striped table-bordered table-hover">
                                        <thead id="table-head">
                                        <tr>
                                            <th height="50" class="align-middle text-center p-0">
                                                <input class="select_all" type="checkbox">
                                            </th>
                                            <th class="align-middle text-center p-0">No.</th>
                                            <th class="align-middle">NAMA & UNIT</th>
                                            <th class="align-middle">NIS & NISN</th>
                                            <th class="align-middle text-center">KELAS</th>
                                            <th class="align-middle text-center p-0">Aksi</th>
                                        </tr>
                                        </thead>
                                        <tbody id="table-body">
                                        </tbody>
                                    </table>
                                </div>
                                <?= form_close() ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overlay" id="loading">
                    <div class="spinner-grow"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('', array('id' => 'formsiswa')); ?>
<div class="modal fade" id="createSiswaModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Siswa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    Cukup isi <b>Nama</b> dan <b>Jenis Kelamin</b>. Field lain boleh kosong:
                    NIS, NISN, username, dan password akan dibuat otomatis, akun siswa langsung aktif.
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nama_siswa">Nama Siswa :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input id="nama_siswa" type="text" class="form-control" name="nama_siswa"
                                   placeholder="Nama Siswa" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nis">NIS :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="number" id="nis" class="form-control" name="nis"
                                   placeholder="NIS (opsional, otomatis)">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nisn">NISN :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="number" id="nisn" class="form-control" name="nisn"
                                   placeholder="NISN (opsional, otomatis)">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="jenis_kelamin" class="control-label">Jenis Kelamin :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                            </div>
                            <select class="form-control" id="jenis_kelamin" data-placeholder="Jenis Kelamin"
                                    name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="kelas_awal">Kelas Awal :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                            </div>
                            <?php
                            if ($setting->jenjang == 1) {
                                $opsis ['1'] = '1';
                                $opsis ['2'] = '2';
                                $opsis ['3'] = '3';
                                $opsis ['4'] = '4';
                                $opsis ['5'] = '5';
                                $opsis ['6'] = '6';
                            } elseif ($setting->jenjang == 2) {
                                $opsis ['7'] = '7';
                                $opsis ['8'] = '8';
                                $opsis ['9'] = '9';
                            } else {
                                $opsis ['10'] = '10';
                                $opsis ['11'] = '11';
                                $opsis ['12'] = '12';
                            };
                            ?>
                            <select class="form-control" id="kelas_awal" data-placeholder="Pilih Kelas"
                                    name="kelas_awal">
                                <option value="">Pilih Kelas Awal</option>
                                <?php foreach ($opsis as $kelas) : ?>
                                    <option value="<?= $kelas ?>"><?= $kelas ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="tahun_masuk">Tanggal Diterima :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
								<span class="input-group-text">
									<i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" name="tahun_masuk" id="tahunmasuk" class="form-control"
                                   autocomplete="off" placeholder="Tgl/Tahun Masuk (opsional)">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="username">Username :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input id="username" type="text" class="form-control" name="username"
                                   placeholder="Username (opsional, otomatis)">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="password">Password :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input id="password" class="form-control" name="password"
                                   placeholder="Password (opsional, default 123456)">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label>Unit Murid :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <?php foreach (unit_pilihan() as $id_unit => $nama_unit): ?>
                            <label class="form-check form-check-inline mb-1">
                                <input class="form-check-input unit-siswa-checkbox" type="checkbox"
                                       name="unit[]" value="<?= (int) $id_unit ?>"
                                       <?= (int) $id_unit === 1 ? 'checked' : '' ?>>
                                <span class="form-check-label"><?= html_escape($nama_unit) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <small class="form-text text-muted d-block">
                            Boleh lebih dari satu. MTs memakai seluruh fitur; Madin/TPQ hanya SPP + absen.
                        </small>
                    </div>
                </div>
                <div class="row mb-3" id="baris-kelas-mts-siswa">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="kelas_mts">Kelas MTs :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <select class="form-control" id="kelas_mts" name="kelas_mts">
                            <?php foreach (kelas_daftar('mts') as $k): ?>
                                <option value="<?= html_escape($k['kode']) ?>"><?= html_escape($k['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Siswa langsung masuk kelas ini di Daftar Siswa.</small>
                    </div>
                </div>
                <div class="row mb-3" id="baris-kelas-madin-siswa">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="kelas_madin">Kelas Madin :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <select class="form-control" id="kelas_madin" name="kelas_madin">
                            <option value="">Pilih kelas Madin</option>
                            <?php foreach (kelas_daftar('madin') as $k): ?>
                                <option value="<?= html_escape($k['kode']) ?>"><?= html_escape($k['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Putra 1-3 dan Putri 1-3; kosong = kelas pertama.</small>
                    </div>
                </div>
                <div class="row mb-3" id="baris-jilid-siswa">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="jilid">Jilid TPQ :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <input id="jilid" type="text" class="form-control" name="jilid"
                               placeholder="Misal: 1" autocomplete="off">
                        <small class="form-text text-muted">Isi bila murid terdaftar dalam unit TPQ.</small>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="reset" class="btn bg-warning text-white">
                    <i class="fa fa-sync mr-1"></i> Reset
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="modal fade" id="jilidModal" tabindex="-1" role="dialog" aria-labelledby="jilidModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jilidModalLabel">Atur Nama Jilid TPQ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    <b>Kode</b> adalah nilai yang tersimpan di data siswa (misal <code>1</code>,
                    <code>Juz Amma</code>). <b>Nama tampil</b> dipakai di header pengelompok dan filter.
                </div>
                <table class="table table-sm table-bordered mb-3" id="tabel-jilid">
                    <thead class="thead-light">
                    <tr>
                        <th style="width:35%">Kode</th>
                        <th>Nama tampil</th>
                        <th style="width:100px">Urutan</th>
                        <th style="width:55px"></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-2">
                        <label>Kode baru</label>
                        <input id="jilid-kode-baru" class="form-control form-control-sm" placeholder="Misal: juz_amma">
                    </div>
                    <div class="form-group col-md-5 mb-2">
                        <label>Nama tampil</label>
                        <input id="jilid-nama-baru" class="form-control form-control-sm" placeholder="Misal: Juz Amma">
                    </div>
                    <div class="form-group col-md-1 mb-2">
                        <label>Urutan</label>
                        <input id="jilid-urutan-baru" type="number" class="form-control form-control-sm" value="99">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <button type="button" id="jilid-tambah" class="btn btn-sm btn-secondary btn-block">
                            <i class="fas fa-plus mr-1"></i>Tambah
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" id="jilid-simpan" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kelasModal" tabindex="-1" role="dialog" aria-labelledby="kelasModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kelasModalLabel">Atur Nama Kelas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    <b>Kode</b> adalah nilai yang tersimpan di data siswa. <b>Nama tampil</b> dipakai di
                    header pengelompok dan filter. Menambah kode baru juga membuat kelas baru.
                </div>
                <table class="table table-sm table-bordered mb-3" id="tabel-kelas">
                    <thead class="thead-light">
                    <tr>
                        <th style="width:35%">Kode</th>
                        <th>Nama tampil</th>
                        <th style="width:100px">Urutan</th>
                        <th style="width:55px"></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-2">
                        <label>Kode baru</label>
                        <input id="kelas-kode-baru" class="form-control form-control-sm" placeholder="Misal: 8">
                    </div>
                    <div class="form-group col-md-5 mb-2">
                        <label>Nama tampil</label>
                        <input id="kelas-nama-baru" class="form-control form-control-sm" placeholder="Misal: Kelas 8">
                    </div>
                    <div class="form-group col-md-1 mb-2">
                        <label>Urutan</label>
                        <input id="kelas-urutan-baru" type="number" class="form-control form-control-sm" value="99">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <button type="button" id="kelas-tambah" class="btn btn-sm btn-secondary btn-block">
                            <i class="fas fa-plus mr-1"></i>Tambah
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" id="kelas-simpan" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="naikKelasModal" tabindex="-1" role="dialog" aria-labelledby="naikKelasModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="naikKelasModalLabel">Naik Kelas Massal (MTs)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    Siswa dipindahkan hanya di unit <b>MTs</b>; kelas Madin dan jilid TPQ tidak berubah.
                    Mode <b>otomatis</b> mengikuti urutan pada tombol setting kelas dan akan membuat
                    kelas berikutnya bila kodenya berupa angka (misal <code>7</code> ke <code>8</code>).
                </div>
                <div class="form-row">
                    <div class="form-group col-md-5 mb-2">
                        <label for="naik-dari">Kelas asal</label>
                        <select id="naik-dari" class="form-control form-control-sm"></select>
                    </div>
                    <div class="form-group col-md-5 mb-2">
                        <label for="naik-tujuan">Kelas tujuan</label>
                        <select id="naik-tujuan" class="form-control form-control-sm"></select>
                    </div>
                    <div class="form-group col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" id="naik-hitung" class="btn btn-sm btn-secondary btn-block">
                            <i class="fas fa-sync mr-1"></i>Hitung
                        </button>
                    </div>
                </div>
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="naik-hanya-terpilih">
                    <label class="custom-control-label" for="naik-hanya-terpilih">
                        Hanya siswa yang dicentang <span id="naik-jumlah-terpilih" class="text-muted"></span>
                    </label>
                </div>
                <div id="naik-hasil" class="mb-2">
                    <span class="text-muted">Pilih kelas asal dan tujuan, lalu klik Hitung.</span>
                </div>
                <div id="naik-catatan" class="alert alert-warning small mb-0 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" id="naik-jalankan" class="btn btn-success" disabled>
                    <i class="fas fa-arrow-up mr-1"></i>Naikkan Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<?= form_open('', array('id' => 'pager')); ?>
<input type="hidden" id="pager-page" name="page" value="1">
<input type="hidden" id="pager-limit" name="limit" value="10000">
<?= form_close() ?>

<script>
    let currentPage = 1;
    let unitAktif = 'mts';
    let query, actionBulk;
    let searchTimer = null;
    let siswaXhr = null;
    let jilidAktif = '';
    let jilidDaftar = [];
    let jilidDihapus = [];
    let kelasAktif = '';
    let kelasDaftar = [];
    let kelasDihapus = [];
    let naikRencana = null;
    const idUnitTpq = <?= (int) unit_id_tpq() ?>;
    const idUnitMts = <?= (int) unit_id_mts() ?>;
    const idUnitMadin = <?= (int) unit_id_madin() ?>;

    function sinkronJilidSiswa() {
        const tpqDicentang = $('#formsiswa input.unit-siswa-checkbox[value="' + idUnitTpq + '"]').is(':checked');
        $('#baris-jilid-siswa').toggle(tpqDicentang);
    }

    function sinkronKelasSiswa() {
        const mtsDicentang = $('#formsiswa input.unit-siswa-checkbox[value="' + idUnitMts + '"]').is(':checked');
        const madinDicentang = $('#formsiswa input.unit-siswa-checkbox[value="' + idUnitMadin + '"]').is(':checked');
        $('#baris-kelas-mts-siswa').toggle(mtsDicentang);
        $('#baris-kelas-madin-siswa').toggle(madinDicentang);
    }

    $(document).ready(function () {
        ajaxcsrf();
        renderHeader();
        muatJilid('');
        muatKelas(unitAktif, '');
        sinkronJilidSiswa();
        sinkronKelasSiswa();
        $('#naik-kelas-massal').toggleClass('d-none', unitAktif !== 'mts');
        $('#baris-kelas').toggleClass('mts', unitAktif === 'mts')
            .toggleClass('madin', unitAktif === 'madin');
        $('#formsiswa input.unit-siswa-checkbox').on('change', function () {
            sinkronJilidSiswa();
            sinkronKelasSiswa();
        });

        $('#siswaUnitTabs a').on('click', function (e) {
            e.preventDefault();
            unitAktif = $(this).data('unit') || 'mts';
            $('#siswaUnitTabs a').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
            $('#users-filter option[value="5"]').toggle(unitAktif === 'mts');
            if (unitAktif !== 'mts' && $('#users-filter').val() === '5') {
                $('#users-filter').val('1');
            }
            $('#baris-jilid').toggleClass('tpq', unitAktif === 'tpq');
            $('#baris-kelas').toggleClass('mts', unitAktif === 'mts')
                .toggleClass('madin', unitAktif === 'madin');
            $('#naik-kelas-massal').toggleClass('d-none', unitAktif !== 'mts');
            if (unitAktif === 'tpq') {
                muatJilid(jilidAktif);
            } else {
                kelasAktif = '';
                $('#kelas-filter').val('');
                muatKelas(unitAktif, '');
            }
            renderHeader();
            currentPage = 1;
            loadSiswa();
        });

        $('#jilid-filter').on('change', function () {
            jilidAktif = $(this).val();
            currentPage = 1;
            loadSiswa();
        });

        $('#atur-jilid').on('click', function () {
            jilidDihapus = [];
            muatJilid().done(function () {
                $('#jilidModal').modal('show');
            });
        });

        $('#jilid-tambah').on('click', function () {
            var kode = $.trim($('#jilid-kode-baru').val());
            var nama = $.trim($('#jilid-nama-baru').val());
            var urutan = parseInt($('#jilid-urutan-baru').val(), 10);
            if (kode === '' || nama === '') {
                swal.fire({title: 'Error', text: 'Kode dan nama tampil wajib diisi.', icon: 'error'});
                return;
            }
            var ada = false;
            $.each(jilidDaftar, function (_, item) {
                if (item.kode.toLowerCase() === kode.toLowerCase()) ada = true;
            });
            if (ada) {
                swal.fire({title: 'Error', text: 'Kode itu sudah ada.', icon: 'error'});
                return;
            }
            jilidDaftar.push({kode: kode, nama: nama, urutan: isNaN(urutan) ? 99 : urutan});
            urutkanJilid();
            renderTabelJilid();
            $('#jilid-kode-baru, #jilid-nama-baru').val('');
        });

        $('#tabel-jilid').on('click', '.hapus-jilid', function () {
            var kode = $(this).closest('tr').data('kode');
            jilidDaftar = jilidDaftar.filter(function (item) { return item.kode !== kode; });
            if (jilidDihapus.indexOf(kode) === -1) jilidDihapus.push(kode);
            renderTabelJilid();
        });

        $('#jilid-simpan').on('click', function () {
            var items = [];
            $('#tabel-jilid tbody tr').each(function () {
                var baris = $(this);
                var kode = $.trim(baris.find('.jilid-kode').val());
                var nama = $.trim(baris.find('.jilid-nama').val());
                var urutan = parseInt(baris.find('.jilid-urutan').val(), 10);
                if (kode !== '' && nama !== '') {
                    items.push({kode: kode, nama: nama, urutan: isNaN(urutan) ? 0 : urutan});
                }
            });
            var tombol = $(this).prop('disabled', true);
            $.post(base_url + 'datasiswa/jilid_simpan', {
                items: JSON.stringify(items),
                hapus: JSON.stringify(jilidDihapus)
            }).done(function (res) {
                tombol.prop('disabled', false);
                if (!res.status) {
                    swal.fire({title: 'Gagal', text: res.text || 'Gagal menyimpan.', icon: 'error'});
                    return;
                }
                $('#jilidModal').modal('hide');
                muatJilid(jilidAktif);
                loadSiswa();
                swal.fire({title: 'Tersimpan', text: res.text, icon: 'success', timer: 1500, showConfirmButton: false});
            }).fail(function () {
                tombol.prop('disabled', false);
                swal.fire({title: 'ERROR', text: 'Gagal menyimpan nama jilid.', icon: 'error'});
            });
        });

        $('#kelas-filter').on('change', function () {
            kelasAktif = $(this).val();
            currentPage = 1;
            loadSiswa();
        });

        $('#atur-kelas').on('click', function () {
            kelasDihapus = [];
            muatKelas(unitAktif).done(function () {
                $('#kelasModalLabel').text(unitAktif === 'madin'
                    ? 'Atur Kelas Madin' : 'Atur Kelas MTs');
                $('#kelasModal').modal('show');
            });
        });

        $('#kelas-tambah').on('click', function () {
            var kode = $.trim($('#kelas-kode-baru').val());
            var nama = $.trim($('#kelas-nama-baru').val());
            var urutan = parseInt($('#kelas-urutan-baru').val(), 10);
            if (unitAktif === 'madin' && /^(putra|putri)\s*[123]$/i.test(kode)) {
                swal.fire({title: 'Error', text: 'Enam kelas Madin (Putra/Putri 1-3) tidak bisa dihapus atau diganti.', icon: 'error'});
                return;
            }
            if (kode === '' || nama === '') {
                swal.fire({title: 'Error', text: 'Kode dan nama tampil wajib diisi.', icon: 'error'});
                return;
            }
            var ada = false;
            $.each(kelasDaftar, function (_, item) {
                if (item.kode.toLowerCase() === kode.toLowerCase()) ada = true;
            });
            if (ada) {
                swal.fire({title: 'Error', text: 'Kode itu sudah ada.', icon: 'error'});
                return;
            }
            kelasDaftar.push({kode: kode, nama: nama, urutan: isNaN(urutan) ? 99 : urutan});
            urutkanKelas();
            renderTabelKelas();
            $('#kelas-kode-baru, #kelas-nama-baru').val('');
        });

        $('#tabel-kelas').on('click', '.hapus-kelas', function () {
            var kode = $(this).closest('tr').data('kode');
            if (unitAktif === 'madin' && /^(putra|putri)\s*[123]$/i.test(kode)) {
                swal.fire({title: 'Error', text: 'Enam kelas Madin (Putra/Putri 1-3) tidak bisa dihapus.', icon: 'error'});
                return;
            }
            kelasDaftar = kelasDaftar.filter(function (item) { return item.kode !== kode; });
            if (kelasDihapus.indexOf(kode) === -1) kelasDihapus.push(kode);
            renderTabelKelas();
        });

        $('#kelas-simpan').on('click', function () {
            var items = [];
            $('#tabel-kelas tbody tr').each(function () {
                var baris = $(this);
                var kode = $.trim(baris.find('.kelas-kode').val());
                var nama = $.trim(baris.find('.kelas-nama').val());
                var urutan = parseInt(baris.find('.kelas-urutan').val(), 10);
                if (kode !== '' && nama !== '') {
                    items.push({kode: kode, nama: nama, urutan: isNaN(urutan) ? 0 : urutan});
                }
            });
            var tombol = $(this).prop('disabled', true);
            $.post(base_url + 'datasiswa/kelas_simpan', {
                unit: unitAktif,
                items: JSON.stringify(items),
                hapus: JSON.stringify(kelasDihapus)
            }).done(function (res) {
                tombol.prop('disabled', false);
                if (!res.status) {
                    swal.fire({title: 'Gagal', text: res.text || 'Gagal menyimpan.', icon: 'error'});
                    return;
                }
                $('#kelasModal').modal('hide');
                muatKelas(unitAktif, kelasAktif);
                loadSiswa();
                swal.fire({title: 'Tersimpan', text: res.text, icon: 'success', timer: 1500, showConfirmButton: false});
            }).fail(function () {
                tombol.prop('disabled', false);
                swal.fire({title: 'ERROR', text: 'Gagal menyimpan kelas.', icon: 'error'});
            });
        });

        $('#naik-kelas-massal').on('click', function () {
            if (unitAktif !== 'mts') {
                return;
            }
            muatKelas('mts').done(function () {
                isiPilihanNaikKelas();
                $('#naikKelasModal').modal('show');
                hitungNaikKelas();
            });
        });

        $('#naik-dari, #naik-tujuan').on('change', hitungNaikKelas);
        $('#naik-hitung').on('click', hitungNaikKelas);
        $('#naik-hanya-terpilih').on('change', function () {
            var jumlah = $('#table-siswa tbody tr .check:checked').length;
            $('#naik-jumlah-terpilih').text(jumlah ? '(' + jumlah + ')' : '');
            hitungNaikKelas();
        });

        $('#naik-jalankan').on('click', function () {
            if (!naikRencana || !naikRencana.jumlah) {
                return;
            }
            var tombol = $(this).prop('disabled', true);
            var daftar = naikRencana.ringkasan || [];
            var rincian = $.map(daftar, function (item) {
                return item.dari_nama + ' → ' + item.tujuan_nama + ' (' + item.jumlah + ' siswa)';
            }).join('\n');
            swal.fire({
                title: 'Yakin?',
                text: 'Siswa berikut akan dinaikkan kelas:\n' + rincian,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, naikkan!',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.value) {
                    tombol.prop('disabled', false);
                    return;
                }
                var data = dataNaikKelas(false);
                if (!data) {
                    tombol.prop('disabled', false);
                    swal.fire({title: 'Error', text: 'Tidak ada siswa terpilih.', icon: 'error'});
                    return;
                }
                $.post(base_url + 'datasiswa/naik_kelas_massal', data).done(function (res) {
                    tombol.prop('disabled', false);
                    if (!res.status) {
                        swal.fire({title: 'Gagal', text: res.text || 'Gagal menyimpan.', icon: 'error'});
                        return;
                    }
                    $('#naikKelasModal').modal('hide');
                    $(".select_all, .check").prop('checked', false);
                    $('#hapusterpilih, #dropdown-btn').attr('disabled', 'disabled');
                    muatKelas('mts', kelasAktif);
                    loadSiswa();
                    swal.fire({
                        title: 'Berhasil',
                        text: res.text,
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false
                    });
                }).fail(function () {
                    tombol.prop('disabled', false);
                    swal.fire({title: 'ERROR', text: 'Gagal menghubungi server.', icon: 'error'});
                });
            });
        });

        $('#users-filter').change(function () {
            clearTimeout(searchTimer);
            currentPage = 1;
            loadSiswa();
        });

        // Pencarian langsung: daftar siswa di bawah ikut menyaring saat mengetik,
        // tanpa dropdown saran yang menutupi tabel.
        $('#input-search').on('input', function () {
            var val = $.trim($(this).val());
            query = val === '' ? null : val;
            currentPage = 1;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                loadSiswa({ diam: true });
            }, 300);
        });

        $('#input-search').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimer);
                applySearch();
            } else if (e.key === 'Escape' && this.value !== '') {
                this.value = '';
                query = null;
                currentPage = 1;
                clearTimeout(searchTimer);
                loadSiswa({ diam: true });
            }
        });

        $(document).on("click", ".select_all", function () {
            if (this.checked) {
                $(".check").each(function () {
                    this.checked = true;
                    $(".select_all").prop("checked", true);
                    $('#hapusterpilih').removeAttr('disabled');
                    $('#dropdown-btn').removeAttr('disabled');
                });
            } else {
                $(".check").each(function () {
                    this.checked = false;
                    $(".select_all").prop("checked", false);
                    $('#hapusterpilih').attr('disabled', 'disabled');
                    $('#dropdown-btn').attr('disabled', 'disabled');
                });
            }
        });

        $("#table-siswa tbody").on("click", "tr .check", function () {
            var check = $("#table-siswa tbody tr .check").length;
            var checked = $("#table-siswa tbody tr .check:checked").length;
            if (check === checked) {
                $(".select_all").prop("checked", true);
            } else {
                $(".select_all").prop("checked", false);
            }

            if (checked === 0) {
                $('#hapusterpilih').attr('disabled', 'disabled');
                $('#dropdown-btn').attr('disabled', 'disabled');
            } else {
                $('#hapusterpilih').removeAttr('disabled');
                $('#dropdown-btn').removeAttr('disabled');
            }
        });

        $("#bulk").on("submit", function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //console.log($(this).serialize() + '&aksi=' +actionBulk)
            $.ajax({
                url: $(this).attr("action"),
                data: $(this).serialize() + '&aksi=' +actionBulk,
                type: "POST",
                success: function (respon) {
                    if (respon.status) {
                        $(".select_all").prop("checked", false);
                        $('#hapusterpilih').attr('disabled', 'disabled');
                        $('#dropdown-btn').attr('disabled', 'disabled');
                        console.log('res', respon)
                        swal.fire({
                            title: "Berhasil",
                            text: respon.total + " data berhasil dihapus",
                            icon: "success"
                        });
                        loadSiswa();
                    } else {
                        swal.fire({
                            title: "Gagal",
                            text: "Tidak ada data yang dipilih",
                            icon: "error"
                        });
                    }
                },
                error: function () {
                    swal.fire({
                        title: "Gagal",
                        text: "Ada data yang sedang digunakan",
                        icon: "error"
                    });
                }
            });
        });

        $('#tahunmasuk').datetimepicker({
            icons:
                {
                    next: 'fa fa-angle-right',
                    previous: 'fa fa-angle-left'
                },
            timepicker: false,
            format: 'Y-m-d',
            disabledWeekDays: [0],
            widgetPositioning: {
                horizontal: 'left',
                vertical: 'bottom'
            }
        });

        $('#formsiswa').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if ($('#formsiswa input.unit-siswa-checkbox:checked').length === 0) {
                swal.fire({title: "Error", text: "Pilih minimal satu unit murid.", icon: "error"});
                return;
            }
            console.log($(this).serialize());
            $.ajax({
                url: base_url + "datasiswa/create",
                data: $(this).serialize(),
                dataType: "JSON",
                type: 'POST',
                success: function (response) {
                    console.log("result", response);
                    $('#createSiswaModal').modal('hide').data('bs.modal', null);
                    $('#createSiswaModal').on('hidden', function () {
                        $(this).data('modal', null);  // destroys modal
                    });

                    if (response.insert) {
                        var detail = '';
                        if (response.username) detail += ' Username: ' + response.username;
                        if (response.password_default) detail += ' Password: ' + response.password_default;
                        if (!response.akun_aktif) {
                            detail += ' Akun belum aktif, aktifkan di User Management.';
                        }
                        showSuccessToast(response.text + detail);
                        loadSiswa();
                    } else {
                        showDangerToast(response.text);
                    }
                },
                error: function (xhr, status, error) {
                    $('#createSiswaModal').modal('hide').data('bs.modal', null);
                    $('#createSiswaModal').on('hidden', function () {
                        $(this).data('modal', null);  // destroys modal
                    });
                    showDangerToast("Gagal disimpan");
                    console.log(xhr.responseText);
                }
            })
        });

        $("#dropdown-action a ").click(function () {
            let x = $(this).attr('id');
            //alert(x);
            actionBulk = x;
            if (x === "pindah") {
                bulk_pindah()
            } else if (x === "keluar") {
                bulk_keluar()
            } else if (x === "hapus") {
                bulk_delete()
            }
        });

        loadSiswa();
    });

    function esc(v) {
        return $('<div>').text(v == null ? '' : v).html();
    }

    function escAttr(v) {
        return esc(v).replace(/"/g, '&quot;');
    }

    function urutkanJilid() {
        jilidDaftar.sort(function (a, b) {
            var ua = parseInt(a.urutan, 10) || 0;
            var ub = parseInt(b.urutan, 10) || 0;
            if (ua !== ub) return ua - ub;
            return String(a.kode).localeCompare(String(b.kode), undefined, {numeric: true});
        });
    }

    function renderTabelJilid() {
        var html = '';
        $.each(jilidDaftar, function (_, item) {
            html += '<tr data-kode="' + escAttr(item.kode) + '">' +
                '<td><input class="form-control form-control-sm jilid-kode" value="' + escAttr(item.kode) + '"></td>' +
                '<td><input class="form-control form-control-sm jilid-nama" value="' + escAttr(item.nama) + '"></td>' +
                '<td><input type="number" class="form-control form-control-sm jilid-urutan" value="' +
                (parseInt(item.urutan, 10) || 0) + '"></td>' +
                '<td class="text-center"><button type="button" class="btn btn-xs btn-danger hapus-jilid" ' +
                'title="Hapus nama jilid"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
        });
        if (html === '') {
            html = '<tr><td colspan="4" class="text-center text-muted">Belum ada jilid.</td></tr>';
        }
        $('#tabel-jilid tbody').html(html);
    }

    function muatJilid(pilih) {
        return $.getJSON(base_url + 'datasiswa/jilid_data').done(function (res) {
            jilidDaftar = res.daftar || [];
            urutkanJilid();
            var saat = (pilih !== undefined && pilih !== null) ? pilih : ($('#jilid-filter').val() || '');
            var html = '<option value="">Semua Jilid</option>' +
                '<option value="__tanpa">Tanpa Jilid</option>';
            $.each(jilidDaftar, function (_, item) {
                html += '<option value="' + escAttr(item.kode) + '">' + esc(item.nama) + '</option>';
            });
            $('#jilid-filter').html(html).val(saat);
            if ($('#jilid-filter').val() === null) $('#jilid-filter').val('');
            renderTabelJilid();
        });
    }

    function urutkanKelas() {
        kelasDaftar.sort(function (a, b) {
            var ua = parseInt(a.urutan, 10) || 0;
            var ub = parseInt(b.urutan, 10) || 0;
            if (ua !== ub) return ua - ub;
            return String(a.kode).localeCompare(String(b.kode), undefined, {numeric: true});
        });
    }

    function renderTabelKelas() {
        var html = '';
        $.each(kelasDaftar, function (_, item) {
            html += '<tr data-kode="' + escAttr(item.kode) + '">' +
                '<td><input class="form-control form-control-sm kelas-kode" readonly value="' + escAttr(item.kode) + '"></td>' +
                '<td><input class="form-control form-control-sm kelas-nama" value="' + escAttr(item.nama) + '"></td>' +
                '<td><input type="number" class="form-control form-control-sm kelas-urutan" value="' +
                (parseInt(item.urutan, 10) || 0) + '"></td>' +
                '<td class="text-center"><button type="button" class="btn btn-xs btn-danger hapus-kelas" ' +
                'title="Hapus kelas"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
        });
        if (html === '') {
            html = '<tr><td colspan="4" class="text-center text-muted">Belum ada kelas.</td></tr>';
        }
        $('#tabel-kelas tbody').html(html);
    }

    function muatKelas(unit, pilih) {
        return $.getJSON(base_url + 'datasiswa/kelas_data', {unit: unit}).done(function (res) {
            kelasDaftar = res.daftar || [];
            urutkanKelas();
            var saat = (pilih !== undefined && pilih !== null) ? pilih : ($('#kelas-filter').val() || '');
            var html = '<option value="">Semua Kelas</option>' +
                '<option value="__tanpa">Tanpa Kelas</option>';
            $.each(kelasDaftar, function (_, item) {
                html += '<option value="' + escAttr(item.kode) + '">' + esc(item.nama) + '</option>';
            });
            $('#kelas-filter').html(html).val(saat);
            if ($('#kelas-filter').val() === null) $('#kelas-filter').val('');
            renderTabelKelas();
        });
    }

    function isiPilihanNaikKelas() {
        var dariLama = $('#naik-dari').val();
        var tujuanLama = $('#naik-tujuan').val() || 'auto';
        var htmlDari = '<option value="__semua">Semua kelas</option>' +
            '<option value="__tanpa">Tanpa Kelas</option>';
        var htmlTujuan = '<option value="auto">Naik otomatis ke kelas berikutnya</option>';
        $.each(kelasDaftar, function (_, item) {
            htmlDari += '<option value="' + escAttr(item.kode) + '">' + esc(item.nama) + '</option>';
            htmlTujuan += '<option value="' + escAttr(item.kode) + '">' + esc(item.nama) + '</option>';
        });
        $('#naik-dari').html(htmlDari);
        $('#naik-tujuan').html(htmlTujuan);

        var pilihDari = dariLama || '';
        if (pilihDari === '') {
            $.each(kelasDaftar, function (_, item) {
                if (item.kode === kelasAktif) {
                    pilihDari = item.kode;
                }
            });
        }
        if (pilihDari === '') {
            pilihDari = '__semua';
        }
        if ($('#naik-dari option[value="' + pilihDari + '"]').length === 0) {
            pilihDari = '__semua';
        }
        $('#naik-dari').val(pilihDari);
        if ($('#naik-tujuan option[value="' + tujuanLama + '"]').length === 0) {
            tujuanLama = 'auto';
        }
        $('#naik-tujuan').val(tujuanLama);

        var terpilih = $('#table-siswa tbody tr .check:checked').length;
        $('#naik-jumlah-terpilih').text(terpilih ? '(' + terpilih + ')' : '');
        $('#naik-hanya-terpilih').prop('checked', terpilih > 0);
    }

    function dataNaikKelas(pratinjau) {
        var hanya = $('#naik-hanya-terpilih').is(':checked');
        var terpilih = [];
        if (hanya) {
            $('#table-siswa tbody tr .check:checked').each(function () {
                terpilih.push($(this).val());
            });
            if (!terpilih.length) {
                return null;
            }
        }
        var data = {
            dari: $('#naik-dari').val() || '__semua',
            tujuan: $('#naik-tujuan').val() || 'auto',
            pratinjau: pratinjau ? '1' : '0',
            hanya_terpilih: hanya ? '1' : '0'
        };
        if (terpilih.length) {
            data['terpilih[]'] = terpilih;
        }
        return data;
    }

    function tampilkanRencanaNaik(res) {
        naikRencana = res;
        $('#naik-jalankan').prop('disabled', !res.jumlah);
        var html = '';
        if (res.jumlah > 0 && res.ringkasan && res.ringkasan.length) {
            html = '<div class="list-group">';
            $.each(res.ringkasan, function (_, item) {
                html += '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                    '<span>' + esc(item.dari_nama) +
                    ' <i class="fas fa-arrow-right mx-2"></i> ' + esc(item.tujuan_nama) +
                    (item.baru ? ' <span class="badge badge-info">kelas baru</span>' : '') +
                    '</span><span class="badge badge-primary badge-pill">' + item.jumlah + ' siswa</span></div>';
            });
            html += '</div>';
        } else {
            html = '<div class="alert alert-info mb-0">' +
                esc(res.text || 'Tidak ada siswa yang cocok.') + '</div>';
        }
        $('#naik-hasil').html(html);
        var catatan = res.catatan || [];
        $('#naik-catatan').toggleClass('d-none', catatan.length === 0)
            .html(catatan.length ? esc(catatan.join(' ')) : '');
    }

    function hitungNaikKelas() {
        var data = dataNaikKelas(true);
        if (!data) {
            naikRencana = null;
            $('#naik-jalankan').prop('disabled', true);
            $('#naik-hasil').html('<div class="alert alert-warning mb-0">' +
                'Centang siswa dulu atau matikan pilihan "hanya siswa yang dicentang".</div>');
            $('#naik-catatan').addClass('d-none');
            return;
        }
        var tombol = $('#naik-hitung').prop('disabled', true);
        $('#naik-jalankan').prop('disabled', true);
        $('#naik-hasil').html('<span class="text-muted">' +
            '<i class="fas fa-circle-notch fa-spin mr-1"></i>Menghitung...</span>');
        $.post(base_url + 'datasiswa/naik_kelas_massal', data).done(function (res) {
            tombol.prop('disabled', false);
            if (!res.status) {
                naikRencana = null;
                $('#naik-hasil').html('<div class="alert alert-danger mb-0">' +
                    esc(res.text || 'Gagal menghitung.') + '</div>');
                $('#naik-catatan').addClass('d-none');
                return;
            }
            tampilkanRencanaNaik(res);
        }).fail(function () {
            tombol.prop('disabled', false);
            naikRencana = null;
            $('#naik-jalankan').prop('disabled', true);
            $('#naik-hasil').html('<div class="alert alert-danger mb-0">Gagal menghubungi server.</div>');
        });
    }

    function renderHeader() {
        var head = '<tr>' +
            '<th height="50" class="align-middle text-center p-0"><input class="select_all" type="checkbox"></th>' +
            '<th class="align-middle text-center p-0">No.</th>' +
            '<th class="align-middle">NAMA & UNIT</th>' +
            '<th class="align-middle">NIS & NISN</th>';
        if (unitAktif === 'mts' || unitAktif === 'madin') {
            head += '<th class="align-middle text-center">KELAS</th>';
        } else if (unitAktif === 'tpq') {
            head += '<th class="align-middle text-center">JILID</th>';
        }
        head += '<th class="align-middle text-center p-0">Aksi</th></tr>';
        $('#table-head').html(head);
    }

    function loadSiswa(opsi) {
        var diam = !!(opsi && opsi.diam);
        $(".select_all").prop("checked", false);
        $('#pager-page').val(currentPage);
        if (!diam) {
            $('#loading').removeClass('d-none');
        }
        $('#search-loading').removeClass('d-none');
        var cari = query != null ? '&search=' + encodeURIComponent(query) : '';
        var filter = '&filter=' + $('#users-filter').val();
        var paramJilid = unitAktif === 'tpq'
            ? '&jilid=' + encodeURIComponent(jilidAktif || '') : '';
        var paramKelas = (unitAktif === 'mts' || unitAktif === 'madin')
            ? '&kelas=' + encodeURIComponent(kelasAktif || '') : '';
        var dataPost = $('#pager').serialize() + cari + filter
            + '&unit=' + encodeURIComponent(unitAktif) + paramJilid + paramKelas;
        console.log('post', dataPost);
        if (siswaXhr) {
            siswaXhr.abort();
        }
        var xhr = siswaXhr = $.ajax({
            url: base_url + 'datasiswa/list',
            data: dataPost,
            type: 'POST',
            success: function (data) {
                if (xhr !== siswaXhr) { return; }
                $('#loading').addClass('d-none');
                $('#search-loading').addClass('d-none');
                previewData(data);
            }, error: function (xhrObj, status) {
                if (status === 'abort' || xhr !== siswaXhr) { return; }
                $('#loading').addClass('d-none');
                $('#search-loading').addClass('d-none');
                console.log("error", xhrObj.responseText);
                swal.fire({
                    title: "ERROR",
                    text: "Ada kesalahan",
                    icon: "error"
                });
            }
        });
    }

    function previewData(data) {
        console.log(data);
        var html = '';

        function badgeUnit(label) {
            var warna = label === 'MTs' ? 'badge-primary'
                : (label === 'Madin' ? 'badge-info' : 'badge-warning');
            return '<span class="badge ' + warna + ' mr-1">' + esc(label) + '</span>';
        }

        // Hitung jumlah per kelompok pada halaman ini untuk header pengelompok.
        // MTs/Madin dikelompokkan per kelas; TPQ per jilid.
        var pakaiKelas = unitAktif === 'mts' || unitAktif === 'madin';
        var kunciJilid = function (siswa) {
            return (siswa.jilid === '' || siswa.jilid == null) ? '__tanpa' : String(siswa.jilid);
        };
        var kunciKelas = function (siswa) {
            return (siswa.kelas_kode === '' || siswa.kelas_kode == null)
                ? '__tanpa' : String(siswa.kelas_kode);
        };
        var kunciGrup = function (siswa) {
            return unitAktif === 'tpq' ? kunciJilid(siswa) : kunciKelas(siswa);
        };
        var hitungGrup = {};
        var grupSebelumnya = null;
        var colspanPenuh = 6;
        if (unitAktif === 'tpq' || pakaiKelas) {
            $.each(data.lists, function (_, siswa) {
                var kunci = kunciGrup(siswa);
                hitungGrup[kunci] = (hitungGrup[kunci] || 0) + 1;
            });
        }

        if (data.lists.length > 0) {
            $.each(data.lists, function (idx, siswa) {
                var labels = (siswa.unit_labels && siswa.unit_labels.length)
                    ? siswa.unit_labels : ['MTs'];
                var unitBadge = '';
                $.each(labels, function (_, label) {
                    unitBadge += badgeUnit(label);
                });

                var status = siswa.aktif == "0"
                    ? '<span class="badge badge-danger">Nonaktif</span>'
                    : '<span class="badge badge-success">Aktif</span>';

                var kolomTambahan = '';
                if (pakaiKelas) {
                    var namaKelas = siswa.kelas_nama || siswa.kelas_kode || '';
                    kolomTambahan = '<td class="align-middle text-center">' +
                        (namaKelas !== ''
                            ? '<span class="badge badge-info">' + esc(namaKelas) + '</span>'
                            : '<span class="text-muted">-</span>') + '</td>';
                } else if (unitAktif === 'tpq') {
                    kolomTambahan = '<td class="align-middle text-center">' +
                        (siswa.jilid !== '' && siswa.jilid != null
                            ? esc(siswa.jilid)
                            : '<span class="text-muted">-</span>') + '</td>';
                }

                if (unitAktif === 'tpq' || pakaiKelas) {
                    var kunci = kunciGrup(siswa);
                    if (kunci !== grupSebelumnya) {
                        grupSebelumnya = kunci;
                        var labelGrup;
                        if (kunci === '__tanpa') {
                            labelGrup = pakaiKelas ? 'Tanpa Kelas' : 'Tanpa Jilid';
                        } else if (pakaiKelas) {
                            labelGrup = esc(siswa.kelas_nama || siswa.kelas_kode);
                        } else {
                            var namaJilid = siswa.jilid_nama || '';
                            if (namaJilid === '' || namaJilid === kunci) {
                                labelGrup = /^\d+$/.test(kunci) ? 'Jilid ' + kunci : esc(kunci);
                            } else {
                                labelGrup = esc(namaJilid);
                            }
                        }
                        html += '<tr class="' + (pakaiKelas ? 'kelas-group' : 'jilid-group') +
                            '"><td colspan="' + colspanPenuh + '">' +
                            '<i class="fas fa-layer-group mr-1"></i>' + labelGrup +
                            '<span class="badge badge-light ml-2">' +
                            hitungGrup[kunci] + ' siswa</span>' +
                            '</td></tr>';
                    }
                }

                html += '<tr>' +
                    '   <td class="text-center align-middle">' +
                    '       <input name="checked[]" class="check" value="' + esc(siswa.id_siswa) + '" type="checkbox">' +
                    '   </td>' +
                    '   <td class="text-center align-middle">' +
                    Number(idx + 1) + '</td>' +
                    '   <td>' +
                    '       <div class="media d-flex h-100">' +
                    '           <img class="avatar img-circle justify-content-center align-self-center"' +
                    '                src="' + esc(base_url + siswa.foto) + '" width="50" height="50" alt="User Image"> ' +
                    '           <div class="media-body ml-2 justify-content-center align-self-center">' +
                    esc(siswa.nama) + '<br>' + unitBadge +
                    '               <span class="badge badge-light mr-1">' + esc(siswa.jenis_kelamin) + '</span>' +
                    status +
                    '           </div>' +
                    '       </div>' +
                    '   </td>' +
                    '   <td class="align-middle">' +
                    '       <span class="badge badge-light">' + esc(siswa.nis) + '</span><br>' +
                    '       <span class="badge badge-light">' + esc(siswa.nisn) + '</span>' +
                    '   </td>' +
                    kolomTambahan +
                    '   <td class="align-middle">' +
                    '       <div class="text-center">' +
                    '           <a class="btn btn-xs btn-warning" href="' + esc(base_url + 'datasiswa/edit/' + siswa.id_siswa) + '">' +
                    '               <i class="fa fa-pencil-alt"></i> Edit' +
                    '           </a>' +
                    '       </div>' +
                    '   </td>' +
                    '</tr>';
            });
        } else {
            var pesan = data.search
                ? 'Tidak ada siswa yang cocok dengan &quot;' + esc(data.search) + '&quot;.'
                : 'Tidak ada data siswa di unit ini.';
            var colspan = unitAktif === 'madin' ? 5 : 6;
            html += '<tr><td colspan="' + colspan + '" class="text-center align-middle">' + pesan + '</td></tr>';
        }
        $('#table-body').html(html);
        $(`.avatar`).each(function () {
            $(this).on("error", function () {
                var src = $(this).attr('src').replace('profiles', 'foto_siswa');
                $(this).attr("src", src);
                $(this).on("error", function () {
                    $(this).attr("src", base_url + 'assets/img/siswa.png');
                });
            });
        });
    }

    function applySearch() {
        var val = $.trim($('#input-search').val());
        query = val === '' ? null : val;
        currentPage = 1;
        loadSiswa();
    }

    function bulk_delete() {
        if ($("#table-siswa tbody tr .check:checked").length == 0) {
            swal.fire({
                title: "Gagal",
                text: "Tidak ada data yang dipilih",
                icon: "error"
            });
        } else {
            swal.fire({
                title: "Anda yakin?",
                text: "Data terpilih akan dihapus!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Hapus!"
            }).then(result => {
                if (result.value) {
                    $("#bulk").submit();
                }
            });
        }
    }

    function bulk_pindah() {
        if ($("#table-siswa tbody tr .check:checked").length == 0) {
            swal.fire({
                title: "Gagal",
                text: "Tidak ada data yang dipilih",
                icon: "error"
            });
        } else {
            swal.fire({
                title: "Anda yakin?",
                text: "Data terpilih akan diset sebagai siswa PINDAH",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "YA!"
            }).then(result => {
                if (result.value) {
                    $("#bulk").submit();
                }
            });
        }
    }

    function bulk_keluar() {
        if ($("#table-siswa tbody tr .check:checked").length == 0) {
            swal.fire({
                title: "Gagal",
                text: "Tidak ada data yang dipilih",
                icon: "error"
            });
        } else {
            swal.fire({
                title: "Anda yakin?",
                text: "Data terpilih akan diset sebagai siswa KELUAR",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "YA!"
            }).then(result => {
                if (result.value) {
                    $("#bulk").submit();
                }
            });
        }
    }

</script>
