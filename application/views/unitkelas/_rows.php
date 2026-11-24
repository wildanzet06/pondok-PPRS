<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Baris tabel Unit Kelas.
 *
 * Dipakai dua kali: saat halaman pertama dibuka dan saat pencarian langsung
 * (AJAX) memperbarui daftar tanpa memuat ulang halaman.
 */
$q       = isset($q) ? (string) $q : '';
$unit_id = isset($unit_id) ? (int) $unit_id : 0;
?>
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
        <td colspan="5" class="text-center text-muted py-3">
            <?php if ($q !== ''): ?>
                Tidak ada kelas yang cocok dengan pencarian &ldquo;<?= html_escape($q); ?>&rdquo;.
            <?php elseif ($unit_id > 0): ?>
                Belum ada kelas pada unit ini.
            <?php else: ?>
                Belum ada kelas.
            <?php endif; ?>
        </td>
    </tr>
<?php endif; ?>
