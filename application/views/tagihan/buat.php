<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h1 class="mb-0"><i class="fas fa-plus-circle text-primary mr-1"></i> Buat Tagihan</h1>
                    <small class="text-muted">Buat tagihan massal untuk satu kelas</small>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="page-aksi">
                        <a href="<?= site_url('tagihan'); ?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Daftar Tagihan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($jenis)): ?>
                <div class="alert alert-warning">
                    Belum ada jenis tagihan yang aktif.
                    <a href="<?= site_url('tagihan/jenis'); ?>">Buat jenis tagihan dulu</a>.
                </div>
            <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Buat tagihan secara massal</h3>
                        </div>
                        <?= form_open('tagihan/buat_proses', array('id' => 'formBuat')); ?>
                        <div class="card-body">

                            <div class="form-group">
                                <label>Jenis tagihan</label>
                                <select name="jenis_id" id="jenis_id" class="form-control" required>
                                    <?php foreach ($jenis as $j): ?>
                                        <option value="<?= (int) $j->id; ?>"
                                                data-tipe="<?= $j->tipe; ?>"
                                                data-unit="<?= (int) $j->unit_id; ?>"
                                                data-nominal="<?= (int) $j->nominal_default; ?>"
                                                data-seikhlas="<?= !empty($j->seikhlasnya) ? 1 : 0; ?>">
                                            <?= html_escape($j->nama); ?>
                                            (<?= $j->tipe == 'bulanan' ? 'bulanan' : 'sekali bayar'; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Unit sasaran</label>
                                <select name="unit_id" id="unit_id" class="form-control">
                                    <?php foreach ($unit as $id_unit => $nama_unit): ?>
                                        <option value="<?= (int) $id_unit; ?>"><?= html_escape($nama_unit); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Murid yang terdaftar di unit inilah yang akan mendapat tagihan. MTs boleh memakai
                                     sasaran "aktif di kelas"; Madin/TPQ tidak pakai rombel sehingga otomatis
                                     memakai semua murid di unitnya. "Semua unit" mengabaikan filter unit.</small>
                            </div>

                            <div id="grup_periode" class="form-row">
                                <div class="form-group col-md-7">
                                    <label>Bulan</label>
                                    <select name="bulan" id="bulan" class="form-control">
                                        <?php foreach ($daftar_bulan as $i => $b): ?>
                                            <option value="<?= $b; ?>" <?= ((int) date('n') == $i + 1) ? 'selected' : ''; ?>><?= $b; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-5">
                                    <label>Tahun</label>
                                    <input type="number" name="tahun" id="tahun" class="form-control"
                                           min="2000" max="2100" value="<?= date('Y'); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nominal per siswa (Rp)</label>
                                <input type="number" name="nominal" id="nominal" min="1" class="form-control" required>
                                <small class="text-muted" id="info-nominal">
                                    Diisi otomatis dari nominal bawaan jenis tagihan. Jika SPP naik, ubah di sini
                                    (atau di menu Jenis Tagihan). Tagihan lama tidak ikut berubah.
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Sasaran siswa</label>
                                <div>
                                    <?php if ($aktif_tersedia): ?>
                                        <label class="font-weight-normal mr-3">
                                            <input type="radio" name="target" value="aktif" checked>
                                            Siswa aktif di kelas (TP/Smt aktif) &mdash; <span id="hit-aktif"><?= (int) $total_aktif; ?></span> siswa
                                        </label><br>
                                    <?php endif; ?>
                                    <label class="font-weight-normal">
                                        <input type="radio" name="target" value="semua" <?= $aktif_tersedia ? '' : 'checked'; ?>>
                                        Semua siswa di unit ini &mdash; <span id="hit-semua"><?= (int) $total_siswa; ?></span> siswa
                                    </label>
                                </div>
                                <?php if (!$aktif_tersedia): ?>
                                    <small class="text-muted">Pilihan "siswa aktif" tidak tersedia (tabel kelas_siswa tidak dikenali).</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group" id="preview-sasaran-wrap">
                                 <label class="mb-1">
                                     Pratinjau siswa sasaran
                                     <span class="badge badge-primary" id="jumlah-sasaran">0</span>
                                     <small class="text-muted ml-1" id="ket-sasaran">Memuat...</small>
                                 </label>
                                 <div id="daftar-sasaran" class="border rounded p-2 bg-light"
                                      style="max-height:230px; overflow:auto; font-size:.9rem;">
                                     <span class="text-muted">Memuat daftar siswa...</span>
                                 </div>
                             </div>

                             <?php if ($aktif_tersedia && !empty($daftar_kelas)): ?>
                            <div class="form-group" id="grup_kelas">
                                <label>Kelas <small class="text-muted">(hanya berlaku untuk "siswa aktif")</small></label>
                                <select name="id_kelas" id="id_kelas" class="form-control">
                                    <option value="0">Semua kelas</option>
                                    <?php foreach ($daftar_kelas as $k): ?>
                                        <option value="<?= (int) $k->id_kelas; ?>" data-unit="<?= (int) $k->unit_id; ?>">
                                            <?= html_escape($k->nama_kelas); ?> · <?= html_escape(unit_nama($k->unit_id)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Keterangan <small class="text-muted">(opsional, mis. "Seragam batik")</small></label>
                                <input type="text" name="keterangan" maxlength="255" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Jatuh tempo <small class="text-muted">(opsional)</small></label>
                                <input type="date" name="jatuh_tempo" class="form-control">
                            </div>

                            <div class="callout callout-info mb-0">
                                <small>
                                    <b>Keringanan siswa</b> (menu Keringanan) diterapkan otomatis sebagai diskon.
                                    Siswa yang <b>sudah punya tagihan yang sama</b> dilewati, jadi aman jika tombol ditekan dua kali.
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Buat Tagihan
                            </button>
                        </div>
                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
    (function () {
        var jenis = document.getElementById('jenis_id');
        var grup  = document.getElementById('grup_periode');
        var nom   = document.getElementById('nominal');
        var info  = document.getElementById('info-nominal');
        var form  = document.getElementById('formBuat');
        var unit  = document.getElementById('unit_id');
        var kelas = document.getElementById('id_kelas');
        var daftar = document.getElementById('daftar-sasaran');
        var jumlah = document.getElementById('jumlah-sasaran');
        var keterangan = document.getElementById('ket-sasaran');
        if (!jenis) return;

        var idUnitMts = <?= (int) $unit_mts; ?>;
        var xhrSasaran = null;

        function esc(v) {
            return $('<div>').text(v == null ? '' : v).html();
        }

        // Madin/TPQ tidak punya rombel, jadi pilihan "aktif di kelas" dimatikan.
        function aturTarget(bisaAktif) {
            var radioAktif = form.querySelector('input[name="target"][value="aktif"]');
            var radioSemua = form.querySelector('input[name="target"][value="semua"]');
            if (radioAktif) {
                radioAktif.disabled = !bisaAktif;
                var label = radioAktif.closest('label');
                if (label) label.style.opacity = bisaAktif ? '1' : '.55';
                if (!bisaAktif && radioAktif.checked && radioSemua) radioSemua.checked = true;
            }
            return radioAktif && radioAktif.checked && !radioAktif.disabled ? 'aktif' : 'semua';
        }

        function muatSasaran() {
            if (!unit || !daftar) return;
            var unitId = unit.value;
            var radioAktif = form.querySelector('input[name="target"][value="aktif"]');
            var bisaAktif = !!radioAktif && (unitId === '0' || unitId === String(idUnitMts));
            var target = aturTarget(bisaAktif);
            var idKelas = kelas ? kelas.value : '0';

            if (keterangan) keterangan.textContent = 'Memuat...';
            if (xhrSasaran) xhrSasaran.abort();
            var xhr = xhrSasaran = $.getJSON('<?= site_url('tagihan/siswa_target_ajax') ?>', {
                unit_id: unitId,
                target: target,
                id_kelas: idKelas
            }).done(function (res) {
                if (xhr !== xhrSasaran) return;
                $('#hit-aktif').text(res.total_aktif);
                $('#hit-semua').text(res.total_semua);
                if (jumlah) jumlah.textContent = res.total;
                if (keterangan) keterangan.textContent = res.target === 'aktif'
                    ? 'siswa aktif di kelas'
                    : 'semua siswa di unit ' + res.unit_nama;

                var html = '';
                if (!res.siswa.length) {
                    html = '<div class="text-muted">Belum ada siswa yang cocok dengan unit ini.</div>';
                } else {
                    $.each(res.siswa, function (i, s) {
                        html += '<div class="d-flex justify-content-between border-bottom py-1">' +
                            '<span>' + (i + 1) + '. <b>' + esc(s.nama) + '</b>' +
                            '<br><small class="text-muted">' + esc(s.nis) +
                            (s.kelas ? ' &middot; ' + esc(s.kelas) : '') + '</small></span>' +
                            '</div>';
                    });
                    if (res.total > res.siswa.length) {
                        html += '<div class="text-muted mt-1 small">Menampilkan ' + res.siswa.length +
                            ' dari ' + res.total + ' siswa.</div>';
                    }
                }
                daftar.innerHTML = html;
            }).fail(function () {
                if (xhr !== xhrSasaran) return;
                daftar.innerHTML = '<div class="text-danger small">Gagal memuat daftar siswa.</div>';
            });
        }

        function sesuaikanKelas() {
            if (!kelas || !unit) return;
            var dipilih = unit.value;
            var adaTerpilih = false;
            for (var i = 0; i < kelas.options.length; i++) {
                var o = kelas.options[i];
                if (o.value === '0') { o.hidden = false; continue; }
                var unitKelas = o.getAttribute('data-unit') || '0';
                o.hidden = dipilih !== '0' && unitKelas !== dipilih;
                if (o.selected && !o.hidden) adaTerpilih = true;
            }
            if (!adaTerpilih && kelas.value !== '0') kelas.value = '0';
        }

        function sesuaikan() {
            var opt = jenis.options[jenis.selectedIndex];
            var seikhlas = opt.getAttribute('data-seikhlas') === '1';
            grup.style.display = (opt.getAttribute('data-tipe') === 'bulanan') ? '' : 'none';
            nom.value = seikhlas ? 0 : (opt.getAttribute('data-nominal') || '');
            nom.disabled = seikhlas;
            nom.required = !seikhlas;
            if (info) {
                info.textContent = seikhlas
                    ? 'Jenis ini seikhlasnya: nominal diisi wali santri saat pembayaran. Biarkan 0 di sini.'
                    : 'Diisi otomatis dari nominal bawaan jenis tagihan. Jika SPP naik, ubah di sini '
                      + '(atau di menu Jenis Tagihan). Tagihan lama tidak ikut berubah.';
            }
            if (unit && opt.getAttribute('data-unit') !== null) {
                unit.value = opt.getAttribute('data-unit');
            }
            sesuaikanKelas();
            muatSasaran();
        }

        jenis.addEventListener('change', sesuaikan);
        if (unit) unit.addEventListener('change', function () { sesuaikanKelas(); muatSasaran(); });
        if (kelas) kelas.addEventListener('change', muatSasaran);
        Array.prototype.forEach.call(form.querySelectorAll('input[name="target"]'), function (radio) {
            radio.addEventListener('change', muatSasaran);
        });
        sesuaikan();

        form.addEventListener('submit', function (e) {
            var opt  = jenis.options[jenis.selectedIndex];
            var teks = opt.text.replace(/\s+/g, ' ').trim();
            if (!confirm('Buat tagihan "' + teks + '" sekarang?')) {
                e.preventDefault();
            }
        });
    })();
</script>
