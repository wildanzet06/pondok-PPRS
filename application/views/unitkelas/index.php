<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="content-wrapper bg-white">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Unit Kelas</h1>
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
                    <h3 class="card-title mb-0">Penempatan kelas per unit</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Pilih unit untuk tiap kelas. Data ini dipakai sebagai informasi dan
                        disinkronkan ke keanggotaan siswa di kelas tersebut. Penentuan hak
                        akses serta target tagihan tetap berasal dari <b>Unit Murid</b>.
                    </p>

                    <form method="get" action="<?= site_url('unitkelas'); ?>" class="form-inline mb-3">
                        <input type="text" name="q" class="form-control form-control-sm mr-2"
                               placeholder="Cari nama / kode kelas" value="<?= html_escape($q); ?>">
                        <select name="unit_id" class="form-control form-control-sm mr-2">
                            <?php foreach ($unit as $id_unit => $nama_unit): ?>
                                <option value="<?= (int) $id_unit; ?>" <?= $unit_id === (int) $id_unit ? 'selected' : ''; ?>>
                                    <?= html_escape($nama_unit); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="<?= site_url('unitkelas'); ?>" class="btn btn-default btn-sm">Reset</a>
                    </form>

                    <?= form_open('unitkelas/simpan'); ?>
                    <input type="hidden" name="q" value="<?= html_escape($q); ?>">
                    <input type="hidden" name="unit_id" value="<?= (int) $unit_id; ?>">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Kode</th>
                                    <th>Nama Kelas</th>
                                    <th>Unit saat ini</th>
                                    <th style="width:280px;">Ubah unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($kelas as $k): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= html_escape($k->kode_kelas); ?></td>
                                        <td><?= html_escape($k->nama_kelas); ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?= html_escape(unit_nama($k->unit_id)); ?></span>
                                        </td>
                                        <td>
                                            <select name="unit[<?= (int) $k->id_kelas; ?>]" class="form-control form-control-sm">
                                                <?php foreach (unit_pilihan() as $id_unit => $nama_unit): ?>
                                                    <option value="<?= (int) $id_unit; ?>"
                                                        <?= (int) $k->unit_id === (int) $id_unit ? 'selected' : ''; ?>>
                                                        <?= html_escape($nama_unit); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($kelas)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada kelas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($kelas)): ?>
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
