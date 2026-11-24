<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$ada_filter = ($q !== '' || (int) $unit_id > 0);
?>
<style>
    .unitkelas-cari { width: 280px; max-width: 100%; }
    #tabel-kelas-body.memuat { opacity: .45; }
    #tabel-kelas-body select { min-width: 140px; }
</style>

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

                    <?php /* Tanpa tombol Cari: daftar di bawah langsung menyaring saat mengetik. */ ?>
                    <form method="get" action="<?= site_url('unitkelas'); ?>" id="form-cari-unitkelas"
                          class="form-inline mb-3" role="search">
                        <div class="input-group input-group-sm mr-2 mb-2 unitkelas-cari">
                            <input type="search" name="q" id="cari-kelas" class="form-control"
                                   placeholder="Ketik nama / kode kelas" autocomplete="off"
                                   aria-label="Cari nama atau kode kelas"
                                   value="<?= html_escape($q); ?>">
                            <div class="input-group-append d-none" id="cari-loader">
                                <span class="input-group-text"><i class="fas fa-circle-notch fa-spin"></i></span>
                            </div>
                        </div>
                        <select name="unit_id" id="filter-unit" class="form-control form-control-sm mr-2 mb-2"
                                aria-label="Filter unit">
                            <?php foreach ($unit as $id_unit => $nama_unit): ?>
                                <option value="<?= (int) $id_unit; ?>" <?= $unit_id === (int) $id_unit ? 'selected' : ''; ?>>
                                    <?= html_escape($nama_unit); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= site_url('unitkelas'); ?>" id="reset-cari"
                           class="btn btn-default btn-sm mb-2">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                        <span class="ml-2 mb-2 text-muted small" id="info-jumlah" aria-live="polite">
                            <?php if (empty($kelas)): ?>
                                <?= $ada_filter ? 'Tidak ada hasil' : 'Tidak ada kelas'; ?>
                            <?php else: ?>
                                <?= count($kelas); ?> kelas<?= $ada_filter ? ' ditemukan' : ' terdaftar'; ?>
                            <?php endif; ?>
                        </span>
                    </form>

                    <?= form_open('unitkelas/simpan'); ?>
                    <input type="hidden" name="q" id="simpan-q" value="<?= html_escape($q); ?>">
                    <input type="hidden" name="unit_id" id="simpan-unit" value="<?= (int) $unit_id; ?>">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Kode</th>
                                    <th>Nama Kelas</th>
                                    <th>Unit saat ini</th>
                                    <th style="width:280px;">Ubah unit</th>
                                </tr>
                            </thead>
                            <tbody id="tabel-kelas-body">
                                <?= $this->load->view('unitkelas/_rows', array(
                                    'kelas'   => $kelas,
                                    'q'       => $q,
                                    'unit_id' => $unit_id
                                ), true); ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="aksi-simpan" class="mt-3 <?= empty($kelas) ? 'd-none' : ''; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan semua perubahan
                        </button>
                    </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        'use strict';

        var form = document.getElementById('form-cari-unitkelas');
        if (!form) {
            return;
        }

        var input      = document.getElementById('cari-kelas');
        var unit       = document.getElementById('filter-unit');
        var tbody      = document.getElementById('tabel-kelas-body');
        var info       = document.getElementById('info-jumlah');
        var loader     = document.getElementById('cari-loader');
        var aksi       = document.getElementById('aksi-simpan');
        var qSimpan    = document.getElementById('simpan-q');
        var unitSimpan = document.getElementById('simpan-unit');

        var timer       = null;
        var nomor       = 0;
        var permintaan  = null;

        function nilaiUnit() {
            var angka = parseInt(unit.value, 10);
            return isNaN(angka) ? 0 : angka;
        }

        // Pilihan "Ubah unit" yang belum disimpan jangan hilang hanya karena
        // daftar diperbarui oleh pencarian.
        function ambilPilihan() {
            var hasil = {};
            var pilihan = tbody.querySelectorAll('select[name^="unit["]');
            Array.prototype.forEach.call(pilihan, function (el) {
                var cocok = /^unit\[(\d+)\]$/.exec(el.name);
                if (cocok) {
                    hasil[cocok[1]] = el.value;
                }
            });
            return hasil;
        }

        function pulihkanPilihan(hasil) {
            Object.keys(hasil).forEach(function (id) {
                var el = tbody.querySelector('select[name="unit[' + id + ']"]');
                if (el) {
                    el.value = hasil[id];
                }
            });
        }

        function perbaruiAlamat(q, unitId) {
            if (!window.history || typeof window.history.replaceState !== 'function') {
                return;
            }
            var bagian = [];
            if (q !== '') {
                bagian.push('q=' + encodeURIComponent(q));
            }
            if (unitId > 0) {
                bagian.push('unit_id=' + encodeURIComponent(unitId));
            }
            window.history.replaceState(null, '', form.action + (bagian.length ? '?' + bagian.join('&') : ''));
        }

        function tampilkanJumlah(jumlah, q, unitId) {
            var adaFilter = (q !== '' || unitId > 0);
            var teks;
            if (jumlah > 0) {
                teks = jumlah + ' kelas' + (adaFilter ? ' ditemukan' : ' terdaftar');
            } else {
                teks = adaFilter ? 'Tidak ada hasil' : 'Tidak ada kelas';
            }
            info.textContent = teks;
            info.classList.remove('text-danger');
        }

        function cari() {
            var q       = input.value.trim();
            var unitId  = nilaiUnit();
            var pilihan = ambilPilihan();
            var urut    = ++nomor;

            if (permintaan) {
                permintaan.abort();
            }

            loader.classList.remove('d-none');
            tbody.classList.add('memuat');

            permintaan = $.ajax({
                url: form.action,
                data: { q: q, unit_id: unitId },
                dataType: 'json',
                cache: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            permintaan.done(function (res) {
                if (urut !== nomor || !res) {
                    return;
                }

                tbody.innerHTML = res.html || '';
                pulihkanPilihan(pilihan);
                qSimpan.value = res.q || '';
                unitSimpan.value = parseInt(res.unit_id, 10) || 0;

                if ((parseInt(res.jumlah, 10) || 0) > 0) {
                    aksi.classList.remove('d-none');
                } else {
                    aksi.classList.add('d-none');
                }

                tampilkanJumlah(parseInt(res.jumlah, 10) || 0, res.q || '', parseInt(res.unit_id, 10) || 0);
                perbaruiAlamat(res.q || '', parseInt(res.unit_id, 10) || 0);
            });

            permintaan.fail(function (jqXHR, status) {
                if (status === 'abort' || urut !== nomor) {
                    return;
                }
                info.textContent = 'Gagal memuat daftar kelas. Coba lagi.';
                info.classList.add('text-danger');
            });

            permintaan.always(function () {
                if (urut !== nomor) {
                    return;
                }
                loader.classList.add('d-none');
                tbody.classList.remove('memuat');
            });
        }

        // Tunggu pengguna berhenti mengetik sebentar, baru cari.
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(cari, 300);
        });

        unit.addEventListener('change', function () {
            clearTimeout(timer);
            cari();
        });

        // Enter tidak perlu memuat ulang halaman.
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(timer);
            cari();
        });

        document.getElementById('reset-cari').addEventListener('click', function (e) {
            e.preventDefault();
            clearTimeout(timer);
            input.value = '';
            unit.value = '0';
            cari();
        });
    })();
</script>
