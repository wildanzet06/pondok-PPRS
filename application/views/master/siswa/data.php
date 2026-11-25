<style>
    .siswa-tools { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: flex-end; }
    .siswa-tools .btn { display: inline-flex; align-items: center; gap: .35rem; border-radius: 6px; }
    .siswa-search { max-width: 380px; margin-left: auto; }
    .siswa-search .input-group-text { background: #fff; }
    @media (max-width: 575.98px) {
        .siswa-tools { width: 100%; justify-content: stretch; }
        .siswa-tools .btn { flex: 1 1 30%; justify-content: center; font-size: .78rem; padding: .4rem .3rem; }
        .siswa-search { width: 100%; max-width: none; margin-left: 0; }
        .dataTables_filter { text-align: left !important; }
        .dataTables_length { margin-bottom: .5rem; }
    }
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
                    <h6 class="card-title"><?= $subjudul ?></h6>
                    <div class="card-tools siswa-tools">
                        <button type="button" data-toggle="modal" data-target="#createSiswaModal"
                                class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i><span>Tambah Siswa</span>
                        </button>
                        <a href="<?= base_url('datasiswa/add') ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-upload"></i><span>Import</span></a>
                        <a href="<?= base_url('datasiswa/update') ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-database"></i><span>Update Data</span></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small mb-3">
                        Pilih <b>Unit Murid</b> (MTs / Madin / TPQ) langsung saat tambah atau edit siswa.
                        Centang lebih dari satu unit untuk murid dobel &mdash; tetap satu akun.
                        Jilid TPQ juga diisi di form siswa. Halaman Unit Murid tetap tersedia sebagai fallback.
                    </div>
                    <div class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <div class="dataTables_length">
                                    <label>Show
                                        <select id="users_length" aria-controls="users" class="custom-select custom-select-sm form-control form-control-sm">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
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
                        </div>
                        <div class="row">
                            <div class="col-12 mb-2 d-flex flex-row justify-content-between">
                                <!--
                                <button id="hapusterpilih" onclick="bulk_delete()" type="button" class="btn btn-danger" data-toggle="tooltip" title="Hapus Terpilh" disabled="disabled">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                                -->
                                <div class="dropdown">
                                    <button id="dropdown-btn" class="btn btn-danger dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" disabled="disabled">
                                        Aksi
                                    </button>
                                    <div id="dropdown-action" class="dropdown-menu">
                                        <a class="dropdown-item" id="pindah" href="#">Set sebagai PINDAH</a>
                                        <a class="dropdown-item" id="keluar" href="#">Set sebagai KELUAR</a>
                                        <a class="dropdown-item" id="hapus" href="#">HAPUS</a>
                                    </div>
                                </div>
                                <div class="d-flex flex-row align-items-center">
                                    <span>Filter </span>
                                    <select id="users-filter" class="ml-2 form-control form-control-sm">
                                        <option value="1">Aktif</option>
                                        <option value="5">Tanpa Kelas</option>
                                        <option value="3">Pindah</option>
                                        <option value="4">Keluar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <?= form_open('datasiswa/delete', array('id' => 'bulk')); ?>
                                <div class="table-responsive">
                                    <table id="table-siswa" class="w-100 table table-md table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th height="50" class="align-middle text-center p-0">
                                                <input class="select_all" type="checkbox">
                                            </th>
                                            <th class="align-middle text-center p-0">No.</th>
                                            <th class="align-middle">NAMA & KELAS</th>
                                            <th class="align-middle">NIS & NISN</th>
                                            <th class="align-middle text-center p-0">Aksi</th>
                                        </tr>
                                        </thead>
                                        <tbody id="table-body">
                                        </tbody>
                                    </table>
                                </div>
                                <?= form_close() ?>
                            </div>
                            <div class="col-12">
                                <nav aria-label="Page navigation" class="float-right">
                                    <ul class="pagination" id="pagination"></ul>
                                </nav>
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

<?= form_open('', array('id' => 'pager')); ?>
<input type="hidden" id="pager-page" name="page" value="1">
<input type="hidden" id="pager-limit" name="limit" value="10">
<?= form_close() ?>

<script src="<?=base_url()?>/assets/app/js/jquery.twbsPagination.js" type="text/javascript"></script>
<script>
    let currentPage = 1;
    let perPage = 10;
    let $pagination, defaultOpts, query, actionBulk;
    let searchTimer = null;
    let siswaXhr = null;
    const idUnitTpq = <?= (int) unit_id_tpq() ?>;

    function sinkronJilidSiswa() {
        const tpqDicentang = $('#formsiswa input.unit-siswa-checkbox[value="' + idUnitTpq + '"]').is(':checked');
        $('#baris-jilid-siswa').toggle(tpqDicentang);
    }

    $(document).ready(function () {
        ajaxcsrf();
        sinkronJilidSiswa();
        $('#formsiswa input.unit-siswa-checkbox').on('change', sinkronJilidSiswa);
        $pagination = $('#pagination');
        defaultOpts = {
            visiblePages: 5,
            initiateStartPageClick: false,
            onPageClick: function (event, page) {
                console.info(page + ' (from options)');
                clearTimeout(searchTimer);
                currentPage = page;
                loadSiswa();
            }
        };
        $pagination.twbsPagination(defaultOpts);

        $('#users_length').change(function () {
            $('#pager-limit').val($(this).val());
            perPage = $(this).val();
            clearTimeout(searchTimer);
            currentPage = 1;
            loadSiswa();
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

        $(".select_all").on("click", function () {
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
        var dataPost = $('#pager').serialize() + cari + filter;
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
                if (data.pages > 0) {
                    $pagination.removeClass('d-none');
                    $pagination.twbsPagination('destroy');
                    $pagination.twbsPagination($.extend({}, defaultOpts, {
                        startPage: currentPage,
                        totalPages: data.pages,
                    }));
                } else {
                    $pagination.addClass('d-none');
                }
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

        if (data.lists.length > 0) {
            $.each(data.lists, function (idx, siswa) {
                const kls = siswa.nama_kelas != null ? '<span class="badge badge-info">'+siswa.nama_kelas+'</span>' : '';
                const status = siswa.aktif == "0" ? '<span class="badge badge-danger">Nonaktif</span>' : '<span class="badge badge-success">Aktif</span>';
                html += '<tr>' +
                    '   <td class="text-center align-middle">' +
                    '       <input name="checked[]" class="check" value="'+siswa.id_siswa+'" type="checkbox">' +
                    '   </td>' +
                    '   <td class="text-center align-middle">'+ Number((perPage * (currentPage - 1)) + (idx + 1)) +'</td>' +
                    '   <td>' +
                    '       <div class="media d-flex h-100">' +
                    '           <img class="avatar img-circle justify-content-center align-self-center"' +
                    '                src="'+base_url+siswa.foto+'" width="50" height="50" alt="User Image"> ' +
                    '           <div class="media-body ml-2 justify-content-center align-self-center">'+siswa.nama+'<br>' + kls +
                    '               <span class="badge badge-info mr-1">'+siswa.jenis_kelamin+'</span>' + status +
                    '           </div>' +
                    '       </div>' +
                    '   </td>' +
                    '   <td class=" align-middle">' +
                    '       <span class="badge badge-light">'+siswa.nis+'</span><br>' +
                    '       <span class="badge badge-light">'+siswa.nisn+'</span>' +
                    '   </td>' +
                    '   <td class=" align-middle">' +
                    '       <div class="text-center">' +
                    '           <a class="btn btn-xs btn-warning" href="'+base_url+'datasiswa/edit/'+siswa.id_siswa+'">' +
                    '               <i class="fa fa-pencil-alt"></i> Edit' +
                    '           </a>' +
                    '       </div>' +
                    '   </td>' +
                    '</tr>';
            });
        } else {
            var pesan = data.search
                ? 'Tidak ada siswa yang cocok dengan &quot;' + $('<div>').text(data.search).html() + '&quot;.'
                : 'Tidak ada data siswa';
            html += '<tr><td colspan="5" class="text-center align-middle">' + pesan + '</td></tr>';
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
