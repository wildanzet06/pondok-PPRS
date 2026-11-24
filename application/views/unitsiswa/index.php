<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="content-wrapper bg-white">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Unit Murid</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Keanggotaan unit</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Centang unit yang diikuti murid. Murid <b>MTs</b> memakai seluruh fitur;
                        <b>Madin</b> dan <b>TPQ</b> hanya mendapat menu <b>Bayar SPP</b> dan <b>Absensi</b>.
                        Murid MTs yang juga Madin cukup dicentang keduanya &mdash; tetap satu akun.
                        Murid tanpa centangan dianggap MTs.
                    </p>

                    <form method="get" action="<?= site_url('unitsiswa'); ?>" class="form-inline mb-3">
                        <input type="text" name="q" class="form-control form-control-sm mr-2"
                               placeholder="Cari nama / NIS / username" value="<?= html_escape($q); ?>">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="<?= site_url('unitsiswa'); ?>" class="btn btn-default btn-sm">Reset</a>
                    </form>

                    <?= form_open('unitsiswa/simpan'); ?>
                    <input type="hidden" name="q" value="<?= html_escape($q); ?>">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th style="width:280px;">Unit</th>
                                    <th>Akses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($siswa as $s): ?>
                                    <?php
                                    $punya = isset($keanggotaan[(int) $s->id_siswa])
                                        ? $keanggotaan[(int) $s->id_siswa] : array();
                                    $penuh = empty($punya) || isset($punya[1]);
                                    ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= html_escape($s->nis); ?></td>
                                        <td><?= html_escape($s->nama); ?></td>
                                        <td><?= html_escape($s->username); ?></td>
                                        <td>
                                            <input type="hidden" name="unit[<?= (int) $s->id_siswa; ?>][]" value="0">
                                            <?php foreach ($unit as $id_unit => $nama_unit): ?>
                                                <label class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="unit[<?= (int) $s->id_siswa; ?>][]"
                                                           value="<?= (int) $id_unit; ?>"
                                                           <?= isset($punya[(int) $id_unit]) ? 'checked' : ''; ?>>
                                                    <span class="form-check-label"><?= html_escape($nama_unit); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $penuh ? 'badge-primary' : 'badge-warning'; ?>">
                                                <?= $penuh ? 'Penuh (MTs)' : 'Terbatas (SPP + absen)'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($siswa)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Belum ada murid. Tambahkan murid di menu Data Siswa dulu.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($siswa)): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan semua perubahan
                        </button>
                    <?php endif; ?>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </section>
</div>
