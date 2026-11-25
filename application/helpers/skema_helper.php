<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penjaga skema tabel tambahan (keuangan, tagihan, presensi).
 *
 * Tabel-tabel ini tidak termasuk skema dasar GarudaCBT, jadi bisa hilang bila
 * database dipulihkan dari dump lama atau tabelnya terhapus. Dulu hal itu
 * membuat halaman keuangan menampilkan "Table ... doesn't exist".
 *
 * Fungsi di sini memastikan tabelnya ada sebelum dipakai (idempoten):
 * cepat bila semua sudah ada (1 query), dan membuat/melengkapi bila kurang.
 */

if (!function_exists('skema_tambahan_pastikan')) {
    function skema_tambahan_pastikan($db)
    {
        static $selesai = false;
        if ($selesai) {
            return true;
        }
        $selesai = true;

        $tabel = array(
            'master_unit', 'master_siswa_unit', 'master_jilid', 'master_kelas_label',
            'cbt_pembayaran_spp', 'cbt_setting_spp', 'cbt_jenis_tagihan', 'cbt_tagihan',
            'cbt_keringanan', 'cbt_password_diganti', 'cbt_presensi_sesi', 'cbt_presensi', 'cbt_setting_poin',
            'cbt_rekening', 'cbt_bacaan'
        );

        $in  = "'" . implode("','", $tabel) . "'";
        $row = $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name IN ($in)"
        )->row();

        $lengkap = $row && (int) $row->c === count($tabel);

        // Ada yang kurang: buat ulang. Jangan tampilkan halaman error database
        // bila satu perintah gagal (mis. hak akses), cukup lanjut.
        $debug = $db->db_debug;
        $db->db_debug = false;

        if (!$lengkap) {
        $ddl = array(
            "CREATE TABLE IF NOT EXISTS master_unit (
                id_unit          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
                kode             VARCHAR(20)  NOT NULL,
                nama             VARCHAR(100) NOT NULL,
                aktif            TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (id_unit),
                UNIQUE KEY uk_unit_kode (kode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS master_siswa_unit (
                id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
                siswa_id         INT          NOT NULL,
                unit_id          TINYINT UNSIGNED NOT NULL,
                id_tp            INT          NOT NULL DEFAULT 0,
                status           TINYINT(1)   NOT NULL DEFAULT 1,
                jilid            VARCHAR(20)  DEFAULT NULL,
                created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_siswa_unit_tp (siswa_id, unit_id, id_tp),
                KEY idx_unit (unit_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS master_jilid (
                id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
                kode     VARCHAR(50)  NOT NULL,
                nama     VARCHAR(100) NOT NULL,
                urutan   INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY uk_jilid_kode (kode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS master_kelas_label (
                id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
                unit_id  TINYINT UNSIGNED NOT NULL,
                kode     VARCHAR(50)  NOT NULL,
                nama     VARCHAR(100) NOT NULL,
                urutan   INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY uk_kelas_label (unit_id, kode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            "CREATE TABLE IF NOT EXISTS cbt_pembayaran_spp (
                id                 INT          NOT NULL AUTO_INCREMENT,
                siswa_id           INT          NOT NULL,
                bulan              VARCHAR(20)  NOT NULL,
                tahun              INT          NOT NULL,
                jumlah_bayar       DECIMAL(15,2) NOT NULL,
                metode_pembayaran  ENUM('transfer_manual','midtrans','tunai') NOT NULL,
                status_pembayaran  ENUM('pending','success','failed') DEFAULT 'pending',
                tanggal_bayar      DATETIME     DEFAULT CURRENT_TIMESTAMP,
                bukti_transfer     VARCHAR(255) DEFAULT NULL,
                order_id           VARCHAR(100) DEFAULT NULL,
                transaction_id     VARCHAR(100) DEFAULT NULL,
                created_at         TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                tagihan_id         INT UNSIGNED DEFAULT NULL,
                catatan_admin      VARCHAR(255) DEFAULT NULL,
                diverifikasi_oleh  VARCHAR(100) DEFAULT NULL,
                diverifikasi_pada  DATETIME     DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_tagihan (tagihan_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_setting_spp (
                id           INT           NOT NULL AUTO_INCREMENT,
                nominal_spp  DECIMAL(15,2) NOT NULL,
                keterangan   VARCHAR(255)  DEFAULT NULL,
                instruksi_transfer TEXT    NULL,
                updated_at   TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_jenis_tagihan (
                id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
                kode             VARCHAR(20)  NOT NULL,
                nama             VARCHAR(100) NOT NULL,
                tipe             ENUM('bulanan','sekali') NOT NULL DEFAULT 'sekali',
                nominal_default  INT UNSIGNED NOT NULL DEFAULT 0,
                boleh_cicil      TINYINT(1)   NOT NULL DEFAULT 0,
                maks_cicilan     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                seikhlasnya      TINYINT(1)   NOT NULL DEFAULT 0,
                unit_id          TINYINT UNSIGNED NOT NULL DEFAULT 0,
                keterangan       VARCHAR(255) NULL,
                dibuat_oleh      VARCHAR(100) NULL,
                dibuat_oleh_nama VARCHAR(100) NULL,
                diubah_oleh      VARCHAR(100) NULL,
                diubah_oleh_nama VARCHAR(100) NULL,
                aktif            TINYINT(1)   NOT NULL DEFAULT 1,
                created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_kode (kode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_tagihan (
                id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                siswa_id       INT          NOT NULL,
                jenis_id       INT UNSIGNED NOT NULL,
                periode_bulan  VARCHAR(20)  NULL,
                periode_tahun  SMALLINT     NULL,
                keterangan     VARCHAR(255) NULL,
                nominal        INT UNSIGNED NOT NULL,
                diskon         INT UNSIGNED NOT NULL DEFAULT 0,
                unit_id        TINYINT UNSIGNED NOT NULL DEFAULT 0,
                jatuh_tempo    DATE         NULL,
                created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_tagihan (siswa_id, jenis_id, periode_bulan, periode_tahun),
                KEY idx_jenis (jenis_id),
                KEY idx_siswa (siswa_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_keringanan (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                siswa_id    INT          NOT NULL,
                jenis_id    INT UNSIGNED NULL,
                tipe        ENUM('persen','nominal') NOT NULL DEFAULT 'persen',
                nilai       INT UNSIGNED NOT NULL,
                keterangan  VARCHAR(255) NULL,
                dibuat_oleh      VARCHAR(100) NULL,
                dibuat_oleh_nama VARCHAR(100) NULL,
                diubah_oleh      VARCHAR(100) NULL,
                diubah_oleh_nama VARCHAR(100) NULL,
                aktif       TINYINT(1)   NOT NULL DEFAULT 1,
                created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_siswa (siswa_id),
                KEY idx_jenis (jenis_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_password_diganti (
                username      VARCHAR(100) NOT NULL,
                diganti_pada  INT UNSIGNED NOT NULL,
                PRIMARY KEY (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_presensi_sesi (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_tp        INT          NOT NULL,
                id_smt       INT          NOT NULL,
                id_kelas     INT          NOT NULL,
                tanggal      DATE         NOT NULL,
                jam_ke       TINYINT UNSIGNED NOT NULL,
                mapel_id     INT          NULL,
                mapel_nama   VARCHAR(100) NULL,
                dibuat_oleh  VARCHAR(100) NULL,
                dibuat_oleh_nama VARCHAR(100) NULL,
                diubah_oleh  VARCHAR(100) NULL,
                diubah_oleh_nama VARCHAR(100) NULL,
                created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_sesi (id_kelas, tanggal, jam_ke),
                KEY idx_tanggal (tanggal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_presensi (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                sesi_id     INT UNSIGNED NOT NULL,
                siswa_id    INT          NOT NULL,
                status      CHAR(1)      NOT NULL,
                keterangan  VARCHAR(255) NULL,
                updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_presensi (sesi_id, siswa_id),
                KEY idx_siswa (siswa_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_setting_poin (
                id         INT NOT NULL AUTO_INCREMENT,
                sp1        INT NOT NULL DEFAULT 25,
                sp2        INT NOT NULL DEFAULT 50,
                sp3        INT NOT NULL DEFAULT 75,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS cbt_rekening (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                bank       VARCHAR(50)  NOT NULL,
                nomor      VARCHAR(50)  NOT NULL,
                atas_nama  VARCHAR(100) NOT NULL,
                urut       INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        foreach ($ddl as $sql) {
            $db->query($sql);
        }
        }

        // Tabel label yang dibuat MySQL 8 memakai collation 0900; samakan dengan
        // master_kelas (general_ci) agar JOIN per kode tidak error.
        $kol_label = $db->query(
            "SELECT TABLE_COLLATION AS c FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = 'master_kelas_label' LIMIT 1"
        )->row();
        if ($kol_label && stripos((string) $kol_label->c, 'general_ci') === false) {
            $db->query('ALTER TABLE master_kelas_label CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        }

        // Kolom yang mungkin belum ada pada pemasangan versi lama.
        $kolom = array(
            array('cbt_pembayaran_spp', 'tagihan_id',        'ALTER TABLE cbt_pembayaran_spp ADD COLUMN tagihan_id INT UNSIGNED DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'catatan_admin',     'ALTER TABLE cbt_pembayaran_spp ADD COLUMN catatan_admin VARCHAR(255) DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'diverifikasi_oleh', 'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'diverifikasi_oleh_nama', 'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_pembayaran_spp', 'diverifikasi_pada', 'ALTER TABLE cbt_pembayaran_spp ADD COLUMN diverifikasi_pada DATETIME DEFAULT NULL'),
            array('cbt_kas',           'dibuat_oleh_nama',  'ALTER TABLE cbt_kas ADD COLUMN dibuat_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_setting_spp',    'instruksi_transfer', 'ALTER TABLE cbt_setting_spp ADD COLUMN instruksi_transfer TEXT NULL'),
            array('cbt_setting_spp',    'kunci_kwitansi',    'ALTER TABLE cbt_setting_spp ADD COLUMN kunci_kwitansi VARCHAR(64) NULL'),
            array('cbt_jenis_tagihan',  'boleh_cicil',       'ALTER TABLE cbt_jenis_tagihan ADD COLUMN boleh_cicil TINYINT(1) NOT NULL DEFAULT 0'),
            array('cbt_jenis_tagihan',  'maks_cicilan',      'ALTER TABLE cbt_jenis_tagihan ADD COLUMN maks_cicilan TINYINT UNSIGNED NOT NULL DEFAULT 0'),
            array('cbt_jenis_tagihan',  'seikhlasnya',       'ALTER TABLE cbt_jenis_tagihan ADD COLUMN seikhlasnya TINYINT(1) NOT NULL DEFAULT 0'),
            array('master_kelas',       'unit_id',           'ALTER TABLE master_kelas ADD COLUMN unit_id TINYINT UNSIGNED NOT NULL DEFAULT 1'),
            array('kelas_siswa',        'unit_id',           'ALTER TABLE kelas_siswa ADD COLUMN unit_id TINYINT UNSIGNED NOT NULL DEFAULT 1'),
            array('cbt_jenis_tagihan',  'unit_id',           'ALTER TABLE cbt_jenis_tagihan ADD COLUMN unit_id TINYINT UNSIGNED NOT NULL DEFAULT 0'),
            array('cbt_jenis_tagihan',  'dibuat_oleh',       'ALTER TABLE cbt_jenis_tagihan ADD COLUMN dibuat_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_jenis_tagihan',  'dibuat_oleh_nama',  'ALTER TABLE cbt_jenis_tagihan ADD COLUMN dibuat_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_jenis_tagihan',  'diubah_oleh',       'ALTER TABLE cbt_jenis_tagihan ADD COLUMN diubah_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_jenis_tagihan',  'diubah_oleh_nama',  'ALTER TABLE cbt_jenis_tagihan ADD COLUMN diubah_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_keringanan',     'dibuat_oleh',       'ALTER TABLE cbt_keringanan ADD COLUMN dibuat_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_keringanan',     'dibuat_oleh_nama',  'ALTER TABLE cbt_keringanan ADD COLUMN dibuat_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_keringanan',     'diubah_oleh',       'ALTER TABLE cbt_keringanan ADD COLUMN diubah_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_keringanan',     'diubah_oleh_nama',  'ALTER TABLE cbt_keringanan ADD COLUMN diubah_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_presensi_sesi',  'dibuat_oleh_nama',  'ALTER TABLE cbt_presensi_sesi ADD COLUMN dibuat_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_presensi_sesi',  'diubah_oleh',       'ALTER TABLE cbt_presensi_sesi ADD COLUMN diubah_oleh VARCHAR(100) DEFAULT NULL'),
            array('cbt_presensi_sesi',  'diubah_oleh_nama',  'ALTER TABLE cbt_presensi_sesi ADD COLUMN diubah_oleh_nama VARCHAR(100) DEFAULT NULL'),
            array('cbt_tagihan',        'unit_id',           'ALTER TABLE cbt_tagihan ADD COLUMN unit_id TINYINT UNSIGNED NOT NULL DEFAULT 0'),
            array('master_siswa_unit',  'jilid',             'ALTER TABLE master_siswa_unit ADD COLUMN jilid VARCHAR(20) DEFAULT NULL')
        );

        foreach ($kolom as $k) {
            if (!$db->field_exists($k[1], $k[0])) {
                $db->query($k[2]);
            }
        }

        // Import Excel lama tidak mengisi NIK/kewarganegaraan. Beri default
        // agar INSERT tidak gagal pada MySQL dengan STRICT_TRANS_TABLES.
        foreach (array(
            array('nik', "ALTER TABLE master_siswa MODIFY COLUMN nik VARCHAR(30) NOT NULL DEFAULT ''"),
            array('warga_negara', "ALTER TABLE master_siswa MODIFY COLUMN warga_negara VARCHAR(20) NOT NULL DEFAULT ''")
        ) as $field) {
            $col = $db->query("SHOW COLUMNS FROM master_siswa LIKE '" . $field[0] . "'")->row();
            if ($col && $col->Default === null) {
                $db->query($field[1]);
            }
        }

        // Data lama kelas_siswa punya PRIMARY key tanpa AUTO_INCREMENT, sehingga
        // INSERT baru gagal dengan "Duplicate entry '0'". Perbaiki sekali.
        $pk_kelas = $db->query("SHOW COLUMNS FROM kelas_siswa LIKE 'id_kelas_siswa'")->row();
        if ($pk_kelas && stripos((string) $pk_kelas->Extra, 'auto_increment') === false) {
            $db->query('ALTER TABLE kelas_siswa MODIFY COLUMN id_kelas_siswa INT NOT NULL AUTO_INCREMENT');
        }

        // Pastikan nilai 'tunai' tersedia pada metode pembayaran.
        $metode = $db->query("SHOW COLUMNS FROM cbt_pembayaran_spp LIKE 'metode_pembayaran'")->row();
        if ($metode && stripos((string) $metode->Type, "'tunai'") === false) {
            $db->query("ALTER TABLE cbt_pembayaran_spp
                        MODIFY COLUMN metode_pembayaran ENUM('transfer_manual','midtrans','tunai') NOT NULL");
        }

        // Unit yayasan: id 1 dipakai sebagai nilai default (MTs).
        $db->query("INSERT IGNORE INTO master_unit (id_unit, kode, nama, aktif) VALUES
                    (1, 'mts', 'MTs', 1), (2, 'madin', 'Madin', 1), (3, 'tpq', 'TPQ', 1)");

        // Nama tampilan jilid TPQ. Bisa diubah/ditambah dari modal di halaman Siswa.
        $db->query("INSERT IGNORE INTO master_jilid (kode, nama, urutan) VALUES
                    ('1', 'Jilid 1', 1), ('2', 'Jilid 2', 2), ('3', 'Jilid 3', 3),
                    ('4', 'Jilid 4', 4), ('5', 'Jilid 5', 5), ('6', 'Jilid 6', 6)");

        // Enam kelas Madin: Putra 1-3 dan Putri 1-3. Dibuat sebagai kelas
        // nyata supaya pengelompokan di /datasiswa memakai master_kelas,
        // tetapi presensi Madin tetap memakai kelas virtual "Seluruh santri".
        $id_madin = (int) $db->query("SELECT id_unit FROM master_unit WHERE kode = 'madin' LIMIT 1")->row()->id_unit;
        if ($id_madin <= 0) {
            $id_madin = 2;
        }
        foreach (array('Putra 1', 'Putra 2', 'Putra 3', 'Putri 1', 'Putri 2', 'Putri 3') as $i => $nama_kelas) {
            $urutan = $i + 1;
            $db->query("INSERT INTO master_kelas
                        (id_tp, id_smt, nama_kelas, kode_kelas, level_id, unit_id, set_siswa)
                        SELECT COALESCE((SELECT id_tp FROM master_tp WHERE active = 1 LIMIT 1),
                                        (SELECT MIN(id_tp) FROM master_tp), 1),
                               COALESCE((SELECT id_smt FROM master_smt WHERE active = 1 LIMIT 1),
                                        (SELECT MIN(id_smt) FROM master_smt), 1),
                               " . $db->escape($nama_kelas) . ", " . $db->escape($nama_kelas) . ",
                               1, " . $id_madin . ", '0'
                        FROM DUAL
                        WHERE NOT EXISTS (SELECT 1 FROM master_kelas
                                          WHERE unit_id = " . $id_madin . "
                                            AND kode_kelas = " . $db->escape($nama_kelas) . ")");
            $db->query("INSERT IGNORE INTO master_kelas_label (unit_id, kode, nama, urutan)
                        VALUES (" . $id_madin . ", " . $db->escape($nama_kelas) . ", "
                        . $db->escape($nama_kelas) . ", " . $urutan . ")");
        }

        // Tagihan lama dibuat sebelum jenisnya diberi unit, sehingga unit_id-nya
        // masih 0 dan tidak muncul saat daftar tagihan difilter per unit.
        $db->query("UPDATE cbt_tagihan t JOIN cbt_jenis_tagihan j ON j.id = t.jenis_id
                       SET t.unit_id = j.unit_id
                     WHERE t.unit_id = 0 AND j.unit_id > 0");

        // Isi awal (sama seperti docs/database_gabungan.sql), hanya bila kosong.
        $db->query("INSERT INTO cbt_setting_poin (sp1, sp2, sp3)
                    SELECT 25, 50, 75 FROM DUAL
                    WHERE NOT EXISTS (SELECT 1 FROM cbt_setting_poin)");
        $db->query("INSERT INTO cbt_setting_spp (nominal_spp, keterangan)
                    SELECT 500000, 'SPP Bulanan Default' FROM DUAL
                    WHERE NOT EXISTS (SELECT 1 FROM cbt_setting_spp)");
        $db->query("INSERT IGNORE INTO cbt_jenis_tagihan (kode, nama, tipe, nominal_default, aktif)
                    VALUES ('SPP', 'SPP Bulanan', 'bulanan', 500000, 1)");
        $db->query("UPDATE cbt_jenis_tagihan SET nominal_default = 500000
                    WHERE kode = 'SPP' AND nominal_default = 0");

        // SPP TPQ bersifat sukarela: wali santri mengisi nominalnya sendiri.
        $unit_tpq = $db->query("SELECT id_unit FROM master_unit WHERE kode = 'tpq' LIMIT 1")->row();
        $id_tpq   = $unit_tpq ? (int) $unit_tpq->id_unit : 3;
        $db->query("INSERT IGNORE INTO cbt_jenis_tagihan
                    (kode, nama, tipe, nominal_default, aktif, unit_id, seikhlasnya, keterangan)
                    VALUES ('SPP_TPQ', 'SPP TPQ (Seikhlasnya)', 'bulanan', 0, 1, $id_tpq, 1,
                            'Nominal diisi wali santri saat pembayaran')");

        // Bacaan/stimulus bersama untuk beberapa soal.
        if (!$db->table_exists('cbt_bacaan')) {
            $db->query("CREATE TABLE IF NOT EXISTS `cbt_bacaan` (
                `id_bacaan` INT NOT NULL AUTO_INCREMENT,
                `judul` VARCHAR(255) NOT NULL,
                `isi` LONGTEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_bacaan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if ($db->table_exists('cbt_soal') && !$db->field_exists('id_bacaan', 'cbt_soal')) {
            $db->query("ALTER TABLE `cbt_soal` ADD COLUMN `id_bacaan` INT NULL");
        }

        // Siswa lama/hasil import yang belum memiliki kelas langsung ditempatkan
        // ke kelas pertama sesuai unitnya, supaya tampil tergelompok di /datasiswa
        // tanpa harus membuka menu Kelas/Rombel.
        if (function_exists('siswa_kelas_pastikan')) {
            $tp_aktif = $db->query("SELECT id_tp FROM master_tp WHERE active = 1 LIMIT 1")->row();
            $smt_aktif = $db->query("SELECT id_smt FROM master_smt WHERE active = 1 LIMIT 1")->row();
            if ($tp_aktif && $smt_aktif) {
                $id_tp = (int) $tp_aktif->id_tp;
                $id_smt = (int) $smt_aktif->id_smt;
                $perlu = $db->query(
                    "SELECT a.id_siswa FROM master_siswa a
                     JOIN buku_induk u ON u.id_siswa = a.id_siswa AND u.status = 1
                     WHERE (
                       (EXISTS (SELECT 1 FROM master_siswa_unit su
                                 WHERE su.siswa_id = a.id_siswa AND su.unit_id = 1 AND su.status = 1)
                        OR NOT EXISTS (SELECT 1 FROM master_siswa_unit su WHERE su.siswa_id = a.id_siswa))
                       AND NOT EXISTS (SELECT 1 FROM kelas_siswa ks
                                       WHERE ks.id_siswa = a.id_siswa AND ks.unit_id = 1
                                         AND ks.id_tp = $id_tp AND ks.id_smt = $id_smt)
                     ) OR (
                       EXISTS (SELECT 1 FROM master_siswa_unit su
                               WHERE su.siswa_id = a.id_siswa AND su.unit_id = 2 AND su.status = 1)
                       AND NOT EXISTS (SELECT 1 FROM kelas_siswa ks
                                       WHERE ks.id_siswa = a.id_siswa AND ks.unit_id = 2
                                         AND ks.id_tp = $id_tp AND ks.id_smt = $id_smt)
                     ) LIMIT 1"
                )->row();
                if ($perlu) {
                    $semua = $db->query(
                        "SELECT a.id_siswa FROM master_siswa a
                         JOIN buku_induk u ON u.id_siswa = a.id_siswa AND u.status = 1"
                    )->result_array();
                    foreach ($semua as $s) {
                        siswa_kelas_pastikan((int) $s['id_siswa'], $id_tp, $id_smt);
                    }
                }
            }
        }

        $db->db_debug = $debug;
        return true;
    }
}

if (!function_exists('siswa_import_normalisasi')) {
    /**
     * Lengkapi field wajib yang kosong pada import Excel siswa.
     *
     * File template boleh hanya berisi No, Nama, dan Kelas Awal. NIS, NISN,
     * username, dan password akan dibuat otomatis agar validasi dan INSERT
     * tetap lolos. Nilai yang sudah diisi pengguna tidak diubah.
     *
     * @param array $rows Baris hasil parser Excel (key = indeks kolom 1-based)
     * @param object $db  CodeIgniter DB instance
     * @return array
     */
    function siswa_import_normalisasi($rows, $db)
    {
        if (!is_array($rows)) {
            return $rows;
        }

        $used = array('nis' => array(), 'nisn' => array(), 'username' => array());
        $existing = $db->select('nis, nisn, username')->get('master_siswa');
        foreach ($existing->result_array() as $r) {
            foreach ($used as $k => $dummy) {
                $v = strtolower(trim((string) ($r[$k] ?? '')));
                if ($v !== '') {
                    $used[$k][$v] = true;
                }
            }
        }

        $out = array();
        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }

            $no = (int) ($row[1] ?? ($i + 1));
            if ($no <= 0) {
                $no = $i + 1;
            }
            $nama = trim((string) ($row[4] ?? ''));

            // NIS: 8 digit, diawali 10000000 + nomor urut Excel.
            $nis = trim((string) ($row[3] ?? ''));
            if ($nis === '') {
                $candidate = 10000000 + $no;
                while (isset($used['nis'][(string) $candidate])) {
                    $candidate++;
                }
                $nis = (string) $candidate;
            }
            $used['nis'][strtolower($nis)] = true;
            $row[3] = $nis;

            // NISN: 10 digit, prefix 991... agar tidak bentrok dengan data lama.
            $nisn = trim((string) ($row[2] ?? ''));
            if ($nisn === '') {
                $candidate = 9910000000 + $no;
                while (isset($used['nisn'][(string) $candidate])) {
                    $candidate++;
                }
                $nisn = (string) $candidate;
            }
            $used['nisn'][strtolower($nisn)] = true;
            $row[2] = $nisn;

            // Username: slug nama + nomor urut, dijamin unik.
            $username = trim((string) ($row[6] ?? ''));
            if ($username === '') {
                $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($nama));
                $base = trim($base, '-');
                if ($base === '') {
                    $base = 'siswa';
                }
                $base = substr($base, 0, 35);
                $candidate = $base . '-' . $no;
                while (isset($used['username'][strtolower($candidate)])) {
                    $no++;
                    $candidate = $base . '-' . $no;
                }
                $username = $candidate;
            }
            $used['username'][strtolower($username)] = true;
            $row[6] = $username;

            // Password default; pengguna bisa mengubahnya setelah import.
            $password = trim((string) ($row[7] ?? ''));
            if ($password === '') {
                $password = '123456';
            }
            $row[7] = $password;

            // Field lain yang bertipe NOT NULL / sering dibiarkan kosong.
            if (trim((string) ($row[5] ?? '')) === '') {
                $row[5] = 'L';
            }
            if (trim((string) ($row[8] ?? '')) === '') {
                $row[8] = '7';
            }
            if (trim((string) ($row[14] ?? '')) === '') {
                $row[14] = '0';
            }
            if (trim((string) ($row[16] ?? '')) === '') {
                $row[16] = '1';
            }
            if (trim((string) ($row[25] ?? '')) === '') {
                $row[25] = '0';
            }

            $out[$i] = $row;
        }

        return $out;
    }
}

if (!function_exists('siswa_form_defaults')) {
    /**
     * Nilai default untuk form tambah siswa manual.
     *
     * Form hanya mewajibkan nama dan jenis kelamin. Kolom NOT NULL lain
     * (NIS, NISN, username, password, kelas awal, NIK, warga negara) diisi
     * otomatis bila kosong atau bentrok dengan data lama.
     *
     * @param array  $post Data POSTAlreadyXSSClean dari controller
     * @param object $db   CodeIgniter DB instance
     * @return array
     */
    function siswa_form_defaults($post, $db)
    {
        $post = is_array($post) ? $post : array();
        $ambil = function ($key) use ($post) {
            return trim((string) (isset($post[$key]) ? $post[$key] : ''));
        };

        $nama = $ambil('nama_siswa');
        $jenis_kelamin = strtoupper($ambil('jenis_kelamin'));
        if (!in_array($jenis_kelamin, array('L', 'P'), true)) {
            $jenis_kelamin = '';
        }

        $used = array('nis' => array(), 'nisn' => array(), 'username' => array());
        $max_nis = 10000000;
        $max_nisn = 9910000000;

        $existing = $db->select('nis, nisn, username')->get('master_siswa')->result_array();
        foreach ($existing as $r) {
            foreach ($used as $k => $dummy) {
                $v = strtolower(trim((string) (isset($r[$k]) ? $r[$k] : '')));
                if ($v !== '') {
                    $used[$k][$v] = true;
                }
            }
            $nis_lama = trim((string) (isset($r['nis']) ? $r['nis'] : ''));
            if (ctype_digit($nis_lama) && (int) $nis_lama > $max_nis) {
                $max_nis = (int) $nis_lama;
            }
            $nisn_lama = trim((string) (isset($r['nisn']) ? $r['nisn'] : ''));
            if (ctype_digit($nisn_lama) && (float) $nisn_lama > $max_nisn) {
                $max_nisn = (float) $nisn_lama;
            }
        }

        // Username juga harus unik terhadap akun login (admin/guru/siswa).
        $existing_users = $db->select('username')->get('users')->result_array();
        foreach ($existing_users as $r) {
            $v = strtolower(trim((string) (isset($r['username']) ? $r['username'] : '')));
            if ($v !== '') {
                $used['username'][$v] = true;
            }
        }

        $nis = $ambil('nis');
        if ($nis === '' || !ctype_digit($nis) || isset($used['nis'][$nis])) {
            do {
                $max_nis++;
                $nis = (string) $max_nis;
            } while (isset($used['nis'][$nis]));
        }
        $used['nis'][$nis] = true;

        $nisn = $ambil('nisn');
        if ($nisn === '' || !ctype_digit($nisn) || isset($used['nisn'][$nisn])) {
            do {
                $max_nisn++;
                $nisn = (string) $max_nisn;
            } while (isset($used['nisn'][$nisn]));
        }
        $used['nisn'][$nisn] = true;

        $username = $ambil('username');
        if ($username === '' || isset($used['username'][strtolower($username)])) {
            $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($nama));
            $base = trim((string) $base, '-');
            if ($base === '') {
                $base = 'siswa';
            }
            $tail = '-' . $nis;
            $candidate = substr($base, 0, max(1, 50 - strlen($tail))) . $tail;
            $i = 2;
            while (isset($used['username'][strtolower($candidate)])) {
                $tail = '-' . $nis . '-' . $i;
                $candidate = substr($base, 0, max(1, 50 - strlen($tail))) . $tail;
                $i++;
            }
            $username = $candidate;
        }
        $used['username'][strtolower($username)] = true;

        $password = $ambil('password');
        if ($password === '' || strlen($password) < 6) {
            $password = '123456';
        }

        $kelas_awal = $ambil('kelas_awal');
        if ($kelas_awal === '' || !ctype_digit($kelas_awal)) {
            $kelas_awal = '7';
        }

        return array(
            'nama' => $nama,
            'jenis_kelamin' => $jenis_kelamin,
            'nis' => $nis,
            'nisn' => $nisn,
            'username' => $username,
            'password' => $password,
            'kelas_awal' => $kelas_awal,
            'tahun_masuk' => $ambil('tahun_masuk'),
            'nik' => $ambil('nik'),
            'warga_negara' => $ambil('warga_negara'),
            'foto' => 'assets/img/siswa.png'
        );
    }
}

if (!function_exists('siswa_akun_aktifkan')) {
    /**
     * Pastikan siswa punya akun login aktif (group siswa / id 3).
     * Dipakai saat tambah siswa manual agar user tidak perlu aktifasi lagi.
     *
     * @param object|array $siswa Baris master_siswa (username, password, nis, nama)
     * @param object|null  $CI    CodeIgniter instance
     * @return bool
     */
    function siswa_akun_aktifkan($siswa, $CI = null)
    {
        $CI = $CI ?: get_instance();
        $ambil = function ($key) use ($siswa) {
            if (is_object($siswa)) {
                return isset($siswa->$key) ? trim((string) $siswa->$key) : '';
            }
            return is_array($siswa) && isset($siswa[$key]) ? trim((string) $siswa[$key]) : '';
        };

        $username = $ambil('username');
        if ($username === '') {
            return false;
        }
        if (!isset($CI->ion_auth)) {
            $CI->load->library('ion_auth');
        }

        // Akun sudah ada: cukup pastikan aktif dan tetap di group siswa.
        $user = $CI->db->get_where('users', array('username' => $username))->row();
        if ($user) {
            $CI->db->update('users', array('active' => 1), array('id' => $user->id));
            $relasi = $CI->db->get_where('users_groups', array(
                'user_id' => $user->id,
                'group_id' => 3
            ))->row();
            if (!$relasi) {
                $CI->db->insert('users_groups', array('user_id' => $user->id, 'group_id' => 3));
            }
            return true;
        }

        $password = $ambil('password');
        if ($password === '') {
            return false;
        }

        $nis = $ambil('nis');
        $email = ($nis !== '' ? $nis : $username) . '@siswa.com';
        $lama = $CI->db->get_where('users', array('email' => $email))->row();
        if ($lama && !$CI->ion_auth->delete_user($lama->id)) {
            return false;
        }

        $nama = preg_split('/\s+/', trim($ambil('nama')));
        $first_name = isset($nama[0]) ? $nama[0] : '';
        $last_name = count($nama) > 1 ? end($nama) : '';

        $reg = $CI->ion_auth->register(
            $username,
            $password,
            $email,
            array('first_name' => $first_name, 'last_name' => $last_name),
            array('3')
        );
        if ($reg === false) {
            return false;
        }

        // Berlapis: manual_activation di config sudah FALSE, ini jamin active = 1.
        $CI->db->update('users', array('active' => 1), array('username' => $username));
        return true;
    }
}
