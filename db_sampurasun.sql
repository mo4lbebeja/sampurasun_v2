
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for activity_logs
-- ----------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `usulan_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'contoh: usulan.submit, approval.approve, pengadaan.start',
  `subject_type` varchar(100) DEFAULT NULL COMMENT 'Eloquent model class',
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'payload before/after atau metadata' CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_usulan_id_created_at_index` (`usulan_id`,`created_at`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_action_index` (`action`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `activity_logs_usulan_id_foreign` FOREIGN KEY (`usulan_id`) REFERENCES `usulan_pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of activity_logs
-- ----------------------------
BEGIN;
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (1, 1, 1, 'usulan.submit', 'UsulanPengadaan', 1, 'Usulan USL/2026/05/001 diajukan: Usulan Alat Kedokteran Umum', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:42:02', '2026-05-15 11:42:02');
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (2, 1, 1, 'approval.approve', 'UsulanPengadaan', 1, 'Usulan USL/2026/05/001 disetujui oleh Admin Sistem', '{\"catatan\":null}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:42:08', '2026-05-15 11:42:08');
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (3, 1, 1, 'pengadaan.start', 'Pengadaan', 1, 'Pengadaan PGD/2026/05/001 dimulai untuk usulan USL/2026/05/001 (Paket: Pengadaan Incobator)', '{\"metode\":\"e_purchasing\",\"nama_paket\":\"Pengadaan Incobator\",\"jumlah_item\":1}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:42:40', '2026-05-15 11:42:40');
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (4, 1, 1, 'pengadaan.kontrak', 'Pengadaan', 1, 'Kontrak Peng/01/2026 disimpan untuk pengadaan PGD/2026/05/001', '{\"no_kontrak\":\"Peng\\/01\\/2026\",\"nilai_kontrak\":\"86000000.00\",\"penyedia_id\":\"1\"}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:43:15', '2026-05-15 11:43:15');
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (5, 1, 1, 'pengadaan.start', 'Pengadaan', 2, 'Pengadaan PGD/2026/05/002 dimulai untuk usulan USL/2026/05/001 (Paket: Pengadaan Infus Syring)', '{\"metode\":\"pengadaan_langsung\",\"nama_paket\":\"Pengadaan Infus Syring\",\"jumlah_item\":2}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:44:28', '2026-05-15 11:44:28');
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (6, 1, 1, 'pengadaan.kontrak', 'Pengadaan', 2, 'Kontrak dsd/02/2026 disimpan untuk pengadaan PGD/2026/05/002', '{\"no_kontrak\":\"dsd\\/02\\/2026\",\"nilai_kontrak\":\"180000000.00\",\"penyedia_id\":\"1\"}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:44:52', '2026-05-15 11:44:52');
INSERT INTO `activity_logs` (`id`, `user_id`, `usulan_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (7, 1, 1, 'dokumen.complete', 'Pengadaan', 1, 'Dokumen UPBJ pengadaan PGD/2026/05/001 lengkap, siap pembayaran', '{\"no_bast\":\"027\\/1\\/BAST\\/V\\/2026\"}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 11:46:16', '2026-05-15 11:46:16');
COMMIT;

-- ----------------------------
-- Table structure for anggaran
-- ----------------------------
DROP TABLE IF EXISTS `anggaran`;
CREATE TABLE `anggaran` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sub_kegiatan_id` bigint(20) unsigned DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `kode_rekening` varchar(50) NOT NULL,
  `nama_rekening` varchar(200) NOT NULL,
  `uraian` text DEFAULT NULL,
  `pagu` decimal(18,2) NOT NULL DEFAULT 0.00,
  `terpakai` decimal(18,2) NOT NULL DEFAULT 0.00,
  `sisa` decimal(18,2) GENERATED ALWAYS AS (`pagu` - `terpakai`) STORED,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `anggaran_tahun_kode_rekening_unique` (`tahun`,`kode_rekening`),
  KEY `anggaran_sub_kegiatan_id_foreign` (`sub_kegiatan_id`),
  CONSTRAINT `anggaran_sub_kegiatan_id_foreign` FOREIGN KEY (`sub_kegiatan_id`) REFERENCES `sub_kegiatan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of anggaran
-- ----------------------------
BEGIN;
INSERT INTO `anggaran` (`id`, `sub_kegiatan_id`, `tahun`, `kode_rekening`, `nama_rekening`, `uraian`, `pagu`, `terpakai`, `is_active`, `created_at`, `updated_at`) VALUES (1, 1, 2026, '5.1.02.01.01.024', 'Belanja Modal Peralatan dan Mesin', 'Belanja kebutuhan peralatan dan mesin kantor', 400000000.00, 0.00, 1, '2026-05-13 16:32:32', '2026-05-15 11:40:21');
INSERT INTO `anggaran` (`id`, `sub_kegiatan_id`, `tahun`, `kode_rekening`, `nama_rekening`, `uraian`, `pagu`, `terpakai`, `is_active`, `created_at`, `updated_at`) VALUES (2, 2, 2027, '5.1.02.01.01.024', 'Belanja Modal Peralatan dan Mesin', 'Belanja kebutuhan peralatan dan mesin kantor', 500000000.00, 0.00, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `anggaran` (`id`, `sub_kegiatan_id`, `tahun`, `kode_rekening`, `nama_rekening`, `uraian`, `pagu`, `terpakai`, `is_active`, `created_at`, `updated_at`) VALUES (3, 3, 2026, '02.03.4', 'Alat Kedokteran Umum', 'Alat Kesehatan', 2000000000.00, 266000000.00, 1, '2026-05-14 18:12:05', '2026-05-15 11:44:52');
INSERT INTO `anggaran` (`id`, `sub_kegiatan_id`, `tahun`, `kode_rekening`, `nama_rekening`, `uraian`, `pagu`, `terpakai`, `is_active`, `created_at`, `updated_at`) VALUES (4, 3, 2026, '93.904.33', 'Alat Kedokteran Anak', 'Alkes Anak', 4000000000.00, 0.00, 1, '2026-05-14 18:12:30', '2026-05-14 18:12:30');
COMMIT;

-- ----------------------------
-- Table structure for app_settings
-- ----------------------------
DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE `app_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of app_settings
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for approvals
-- ----------------------------
DROP TABLE IF EXISTS `approvals`;
CREATE TABLE `approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usulan_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned NOT NULL,
  `keputusan` enum('disetujui','ditolak','revisi') NOT NULL,
  `catatan` text DEFAULT NULL COMMENT 'alasan/notes terutama saat ditolak atau minta revisi',
  `tanggal_keputusan` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approvals_approver_id_foreign` (`approver_id`),
  KEY `approvals_usulan_id_tanggal_keputusan_index` (`usulan_id`,`tanggal_keputusan`),
  CONSTRAINT `approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `approvals_usulan_id_foreign` FOREIGN KEY (`usulan_id`) REFERENCES `usulan_pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of approvals
-- ----------------------------
BEGIN;
INSERT INTO `approvals` (`id`, `usulan_id`, `approver_id`, `keputusan`, `catatan`, `tanggal_keputusan`, `created_at`, `updated_at`) VALUES (1, 1, 1, 'disetujui', NULL, '2026-05-15 11:42:08', '2026-05-15 11:42:08', '2026-05-15 11:42:08');
COMMIT;

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cache
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cache_locks
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for document_sequences
-- ----------------------------
DROP TABLE IF EXISTS `document_sequences`;
CREATE TABLE `document_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `last_number` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_sequences_type_year_month_unique` (`type`,`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of document_sequences
-- ----------------------------
BEGIN;
INSERT INTO `document_sequences` (`id`, `type`, `year`, `month`, `last_number`, `created_at`, `updated_at`) VALUES (1, 'usulan', 2026, 5, 1, '2026-05-15 11:42:02', '2026-05-15 11:42:02');
INSERT INTO `document_sequences` (`id`, `type`, `year`, `month`, `last_number`, `created_at`, `updated_at`) VALUES (2, 'pengadaan', 2026, 5, 2, '2026-05-15 11:42:40', '2026-05-15 11:44:28');
COMMIT;

-- ----------------------------
-- Table structure for dokumen_pengadaan
-- ----------------------------
DROP TABLE IF EXISTS `dokumen_pengadaan`;
CREATE TABLE `dokumen_pengadaan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pengadaan_id` bigint(20) unsigned NOT NULL,
  `jenis` varchar(255) NOT NULL,
  `nama_dokumen` varchar(255) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `kode_surat` varchar(255) NOT NULL DEFAULT '027',
  `nomor_urut` int(10) unsigned NOT NULL,
  `kode_dokumen` varchar(30) NOT NULL,
  `bulan_romawi` varchar(10) NOT NULL,
  `tahun` year(4) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dokumen_pengadaan_tahun_nomor_urut_unique` (`tahun`,`nomor_urut`),
  UNIQUE KEY `dokumen_pengadaan_pengadaan_id_jenis_unique` (`pengadaan_id`,`jenis`),
  UNIQUE KEY `dokumen_pengadaan_nomor_unique` (`nomor`),
  KEY `dokumen_pengadaan_created_by_foreign` (`created_by`),
  CONSTRAINT `dokumen_pengadaan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dokumen_pengadaan_pengadaan_id_foreign` FOREIGN KEY (`pengadaan_id`) REFERENCES `pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dokumen_pengadaan
-- ----------------------------
BEGIN;
INSERT INTO `dokumen_pengadaan` (`id`, `pengadaan_id`, `jenis`, `nama_dokumen`, `nomor`, `kode_surat`, `nomor_urut`, `kode_dokumen`, `bulan_romawi`, `tahun`, `tanggal`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES (1, 1, 'bast', 'Berita Acara Serah Terima', '027/1/BAST/V/2026', '027', 1, 'BAST', 'V', 2026, '2026-05-15', NULL, 1, '2026-05-15 11:45:48', '2026-05-15 11:45:48');
INSERT INTO `dokumen_pengadaan` (`id`, `pengadaan_id`, `jenis`, `nama_dokumen`, `nomor`, `kode_surat`, `nomor_urut`, `kode_dokumen`, `bulan_romawi`, `tahun`, `tanggal`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES (2, 1, 'bapmhp', 'Berita Acara Pemeriksaan Mutu Hasil Pekerjaan', '027/2/BAPMHP/V/2026', '027', 2, 'BAPMHP', 'V', 2026, '2026-05-15', NULL, 1, '2026-05-15 11:45:48', '2026-05-15 11:45:48');
INSERT INTO `dokumen_pengadaan` (`id`, `pengadaan_id`, `jenis`, `nama_dokumen`, `nomor`, `kode_surat`, `nomor_urut`, `kode_dokumen`, `bulan_romawi`, `tahun`, `tanggal`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES (3, 1, 'baprhp', 'Berita Acara Penerimaan Hasil Pekerjaan', '027/3/BAPRHP/V/2026', '027', 3, 'BAPRHP', 'V', 2026, '2026-05-15', NULL, 1, '2026-05-15 11:45:48', '2026-05-15 11:45:48');
INSERT INTO `dokumen_pengadaan` (`id`, `pengadaan_id`, `jenis`, `nama_dokumen`, `nomor`, `kode_surat`, `nomor_urut`, `kode_dokumen`, `bulan_romawi`, `tahun`, `tanggal`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES (4, 1, 'bapp', 'Berita Acara Persetujuan Pembayaran', '027/4/BAPP/V/2026', '027', 4, 'BAPP', 'V', 2026, '2026-05-15', NULL, 1, '2026-05-15 11:45:48', '2026-05-15 11:45:48');
COMMIT;

-- ----------------------------
-- Table structure for dokumen_upbj
-- ----------------------------
DROP TABLE IF EXISTS `dokumen_upbj`;
CREATE TABLE `dokumen_upbj` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pengadaan_id` bigint(20) unsigned NOT NULL,
  `petugas_id` bigint(20) unsigned NOT NULL,
  `no_bast` varchar(100) DEFAULT NULL COMMENT 'Berita Acara Serah Terima',
  `tanggal_bast` date DEFAULT NULL,
  `file_bast` varchar(255) DEFAULT NULL,
  `file_invoice` varchar(255) DEFAULT NULL,
  `file_faktur_pajak` varchar(255) DEFAULT NULL,
  `file_kuitansi` varchar(255) DEFAULT NULL,
  `file_spp` varchar(255) DEFAULT NULL COMMENT 'Surat Permintaan Pembayaran',
  `file_lainnya` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'array of file paths untuk dokumen tambahan' CHECK (json_valid(`file_lainnya`)),
  `is_complete` tinyint(1) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dokumen_upbj_pengadaan_id_unique` (`pengadaan_id`),
  KEY `dokumen_upbj_petugas_id_foreign` (`petugas_id`),
  CONSTRAINT `dokumen_upbj_pengadaan_id_foreign` FOREIGN KEY (`pengadaan_id`) REFERENCES `pengadaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dokumen_upbj_petugas_id_foreign` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dokumen_upbj
-- ----------------------------
BEGIN;
INSERT INTO `dokumen_upbj` (`id`, `pengadaan_id`, `petugas_id`, `no_bast`, `tanggal_bast`, `file_bast`, `file_invoice`, `file_faktur_pajak`, `file_kuitansi`, `file_spp`, `file_lainnya`, `is_complete`, `keterangan`, `completed_at`, `created_at`, `updated_at`) VALUES (1, 1, 1, '027/1/BAST/V/2026', '2026-05-30', 'dokumen-upbj/bast/oQmIqXtB9noOYODgCqwP1hXOfLsPKGmsIbuXJ5LT.pdf', 'dokumen-upbj/invoice/yi9uad7k6M3vzM2IKPD3mG2rnC7z22hyLOyiGb9k.pdf', 'dokumen-upbj/faktur_pajak/YDeLFpmxJdL7HtlmWnpOvR8bH6jN71HLp4Rc7Sfm.pdf', 'dokumen-upbj/kuitansi/dIyyTZ5OiaDEEER1BH7m6A9RHTsi4Hyl77NDeIVP.pdf', 'dokumen-upbj/spp/dOQVBLEPbECW1zofswOx3DPPj0eKy33utn6I2kXc.pdf', NULL, 1, 'scsdsd', '2026-05-15 11:46:16', '2026-05-15 11:45:16', '2026-05-15 11:46:16');
INSERT INTO `dokumen_upbj` (`id`, `pengadaan_id`, `petugas_id`, `no_bast`, `tanggal_bast`, `file_bast`, `file_invoice`, `file_faktur_pajak`, `file_kuitansi`, `file_spp`, `file_lainnya`, `is_complete`, `keterangan`, `completed_at`, `created_at`, `updated_at`) VALUES (2, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-05-15 13:03:54', '2026-05-15 13:03:54');
COMMIT;

-- ----------------------------
-- Table structure for dpa_anggaran
-- ----------------------------
DROP TABLE IF EXISTS `dpa_anggaran`;
CREATE TABLE `dpa_anggaran` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tahun_anggaran` year(4) NOT NULL,
  `no_dpa` varchar(255) NOT NULL,
  `tanggal_dpa` date DEFAULT NULL,
  `nama_dpa` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dpa_anggaran_tahun_anggaran_no_dpa_unique` (`tahun_anggaran`,`no_dpa`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dpa_anggaran
-- ----------------------------
BEGIN;
INSERT INTO `dpa_anggaran` (`id`, `tahun_anggaran`, `no_dpa`, `tanggal_dpa`, `nama_dpa`, `keterangan`, `is_active`, `created_at`, `updated_at`) VALUES (1, 2026, 'DPA-2026', '2026-01-01', 'DPA Tahun Anggaran 2026', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `dpa_anggaran` (`id`, `tahun_anggaran`, `no_dpa`, `tanggal_dpa`, `nama_dpa`, `keterangan`, `is_active`, `created_at`, `updated_at`) VALUES (2, 2027, 'DPA-2027', '2027-01-01', 'DPA Tahun Anggaran 2027', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
COMMIT;

-- ----------------------------
-- Table structure for evaluasi
-- ----------------------------
DROP TABLE IF EXISTS `evaluasi`;
CREATE TABLE `evaluasi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pengadaan_id` bigint(20) unsigned NOT NULL,
  `evaluator_id` bigint(20) unsigned NOT NULL,
  `tanggal_evaluasi` date NOT NULL,
  `nilai_kinerja_penyedia` tinyint(4) NOT NULL COMMENT 'skala 1-5',
  `ketepatan_waktu` tinyint(4) NOT NULL COMMENT 'skala 1-5',
  `kesesuaian_spesifikasi` tinyint(4) NOT NULL COMMENT 'skala 1-5',
  `kualitas_barang` tinyint(4) NOT NULL COMMENT 'skala 1-5',
  `nilai_rata_rata` decimal(4,2) DEFAULT NULL,
  `rekomendasi` enum('sangat_baik','baik','cukup','kurang','tidak_direkomendasikan') NOT NULL,
  `catatan_evaluasi` text DEFAULT NULL,
  `rekomendasi_perbaikan` text DEFAULT NULL,
  `file_laporan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluasi_pengadaan_id_unique` (`pengadaan_id`),
  KEY `evaluasi_evaluator_id_foreign` (`evaluator_id`),
  KEY `evaluasi_tanggal_evaluasi_index` (`tanggal_evaluasi`),
  CONSTRAINT `evaluasi_evaluator_id_foreign` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `evaluasi_pengadaan_id_foreign` FOREIGN KEY (`pengadaan_id`) REFERENCES `pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of evaluasi
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of job_batches
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of jobs
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for kategori_barang
-- ----------------------------
DROP TABLE IF EXISTS `kategori_barang`;
CREATE TABLE `kategori_barang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kategori_barang_kode_unique` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of kategori_barang
-- ----------------------------
BEGIN;
INSERT INTO `kategori_barang` (`id`, `kode`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'KAT-01', 'ATK & Cetakan', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `kategori_barang` (`id`, `kode`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'KAT-02', 'Komputer & Elektronik', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `kategori_barang` (`id`, `kode`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES (3, 'KAT-03', 'Mebel & Furniture', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `kategori_barang` (`id`, `kode`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES (4, 'KAT-04', 'Kendaraan', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `kategori_barang` (`id`, `kode`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES (5, 'KAT-05', 'Peralatan Kantor', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `kategori_barang` (`id`, `kode`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES (6, 'KAT-06', 'Bahan Habis Pakai', NULL, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
COMMIT;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of migrations
-- ----------------------------
BEGIN;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '2026_01_01_000001_create_roles_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2026_01_01_000002_create_unit_kerja_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2026_01_01_000003_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2026_01_01_000004_create_kategori_barang_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2026_01_01_000005_create_anggaran_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2026_01_01_000006_create_penyedia_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9, '2026_01_01_000007_create_usulan_pengadaan_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10, '2026_01_01_000008_create_usulan_items_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11, '2026_01_01_000009_create_approvals_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12, '2026_01_01_000010_create_pengadaan_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13, '2026_01_01_000011_create_dokumen_upbj_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14, '2026_01_01_000012_create_pembayaran_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15, '2026_01_01_000013_create_evaluasi_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16, '2026_01_01_000014_create_activity_logs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2026_05_08_155058_create_dokumen_pengadaans_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2026_05_09_110104_add_pejabat_penandatangan_id_to_pengadaan_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2026_05_09_110153_create_sub_kegiatans_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2026_05_09_110230_add_sub_kegiatan_id_to_anggaran_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2026_05_09_144928_add_alamat_to_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2026_05_09_191946_add_kpa_penandatangan_id_to_pengadaan_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2026_05_10_072959_create_dpa_anggaran_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2026_05_10_073027_add_dpa_anggaran_id_to_sub_kegiatan_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2026_05_11_172024_create_document_sequences_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26, '2026_05_12_100649_add_two_factor_columns_to_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2026_05_13_180433_create_notifications_table', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2026_05_14_000001_modify_pengadaan_add_paket_columns', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30, '2026_05_14_000002_create_pengadaan_item_assignments_table', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31, '2026_05_15_000001_add_harga_kontrak_to_pengadaan_item_assignments', 3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32, '2026_05_15_130607_create_app_settings_table', 4);
COMMIT;

-- ----------------------------
-- Table structure for notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of notifications
-- ----------------------------
BEGIN;
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('3d3fee03-ad52-46c6-8fc8-aada1d4c7e00', 'App\\Notifications\\UsulanDisetujuiNotification', 'App\\Models\\User', 4, '{\"type\":\"approval.approve\",\"usulan_id\":1,\"no_usulan\":\"USL\\/2026\\/05\\/001\",\"judul\":\"Usulan Alat Kedokteran Umum\",\"url\":\"\\/usulan\\/1\",\"message\":\"Usulan USL\\/2026\\/05\\/001 disetujui, siap diproses pengadaan.\"}', NULL, '2026-05-15 11:42:08', '2026-05-15 11:42:08');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('5be0f06b-d89c-453b-8ccd-af7c73f77e5b', 'App\\Notifications\\PengadaanKontrakNotification', 'App\\Models\\User', 5, '{\"type\":\"pengadaan.kontrak\",\"pengadaan_id\":2,\"no_pengadaan\":\"PGD\\/2026\\/05\\/002\",\"judul\":\"Usulan Alat Kedokteran Umum\",\"url\":\"\\/dokumen\\/2\",\"message\":\"Kontrak PGD\\/2026\\/05\\/002 siap, mohon lengkapi dokumen UPBJ.\"}', NULL, '2026-05-15 11:44:52', '2026-05-15 11:44:52');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('6b17d3c5-9884-45fb-be4b-567cd11213ec', 'App\\Notifications\\PengadaanKontrakNotification', 'App\\Models\\User', 5, '{\"type\":\"pengadaan.kontrak\",\"pengadaan_id\":1,\"no_pengadaan\":\"PGD\\/2026\\/05\\/001\",\"judul\":\"Usulan Alat Kedokteran Umum\",\"url\":\"\\/dokumen\\/1\",\"message\":\"Kontrak PGD\\/2026\\/05\\/001 siap, mohon lengkapi dokumen UPBJ.\"}', NULL, '2026-05-15 11:43:15', '2026-05-15 11:43:15');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('b7ae5602-8fe5-440b-83ef-3c03917b9014', 'App\\Notifications\\DokumenLengkapNotification', 'App\\Models\\User', 6, '{\"type\":\"dokumen.complete\",\"pengadaan_id\":1,\"no_pengadaan\":\"PGD\\/2026\\/05\\/001\",\"judul\":\"Usulan Alat Kedokteran Umum\",\"url\":\"\\/pembayaran\\/1\",\"message\":\"Dokumen PGD\\/2026\\/05\\/001 lengkap, siap diproses pembayaran.\"}', NULL, '2026-05-15 11:46:16', '2026-05-15 11:46:16');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('d05d9754-2ebc-4999-bac3-e89036bce402', 'App\\Notifications\\UsulanBaruNotification', 'App\\Models\\User', 3, '{\"type\":\"usulan.submit\",\"usulan_id\":1,\"no_usulan\":\"USL\\/2026\\/05\\/001\",\"judul\":\"Usulan Alat Kedokteran Umum\",\"pemohon\":\"Admin Sistem\",\"url\":\"\\/usulan\\/1\",\"message\":\"Usulan baru masuk: USL\\/2026\\/05\\/001 \\u2014 Usulan Alat Kedokteran Umum\"}', NULL, '2026-05-15 11:42:02', '2026-05-15 11:42:02');
COMMIT;

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for pembayaran
-- ----------------------------
DROP TABLE IF EXISTS `pembayaran`;
CREATE TABLE `pembayaran` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pengadaan_id` bigint(20) unsigned NOT NULL,
  `petugas_id` bigint(20) unsigned NOT NULL,
  `no_spm` varchar(50) DEFAULT NULL COMMENT 'Surat Perintah Membayar',
  `no_sp2d` varchar(50) DEFAULT NULL COMMENT 'Surat Perintah Pencairan Dana',
  `tanggal_bayar` date DEFAULT NULL,
  `metode_bayar` enum('transfer','cek','tunai','giro') NOT NULL DEFAULT 'transfer',
  `nilai_bayar` decimal(18,2) NOT NULL DEFAULT 0.00,
  `pajak_pph` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pajak_ppn` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nilai_bersih` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `status` enum('pending','diproses','lunas','batal') NOT NULL DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pembayaran_pengadaan_id_unique` (`pengadaan_id`),
  KEY `pembayaran_petugas_id_foreign` (`petugas_id`),
  KEY `pembayaran_status_index` (`status`),
  KEY `pembayaran_tanggal_bayar_index` (`tanggal_bayar`),
  CONSTRAINT `pembayaran_pengadaan_id_foreign` FOREIGN KEY (`pengadaan_id`) REFERENCES `pengadaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_petugas_id_foreign` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of pembayaran
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for pengadaan
-- ----------------------------
DROP TABLE IF EXISTS `pengadaan`;
CREATE TABLE `pengadaan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usulan_id` bigint(20) unsigned NOT NULL,
  `nama_paket` varchar(200) DEFAULT NULL COMMENT 'Nama paket jika satu usulan dipecah jadi beberapa paket',
  `estimasi_paket` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT 'Porsi estimasi dari total_estimasi usulan untuk paket ini',
  `pejabat_id` bigint(20) unsigned NOT NULL,
  `pejabat_penandatangan_id` bigint(20) unsigned DEFAULT NULL,
  `kpa_penandatangan_id` bigint(20) unsigned DEFAULT NULL,
  `penyedia_id` bigint(20) unsigned DEFAULT NULL,
  `no_pengadaan` varchar(50) NOT NULL COMMENT 'format: PGD/{YYYY}/{MM}/{seq}',
  `metode` enum('pengadaan_langsung','penunjukan_langsung','tender','tender_cepat','e_purchasing','swakelola') NOT NULL DEFAULT 'pengadaan_langsung',
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `no_kontrak` varchar(100) DEFAULT NULL,
  `tanggal_kontrak` date DEFAULT NULL,
  `nilai_kontrak` decimal(18,2) NOT NULL DEFAULT 0.00,
  `file_kontrak` varchar(255) DEFAULT NULL,
  `file_hps` varchar(255) DEFAULT NULL COMMENT 'Harga Perkiraan Sendiri',
  `status` enum('proses','kontrak','selesai','batal') NOT NULL DEFAULT 'proses',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengadaan_no_pengadaan_unique` (`no_pengadaan`),
  KEY `pengadaan_pejabat_id_foreign` (`pejabat_id`),
  KEY `pengadaan_penyedia_id_foreign` (`penyedia_id`),
  KEY `pengadaan_status_index` (`status`),
  KEY `pengadaan_pejabat_penandatangan_id_foreign` (`pejabat_penandatangan_id`),
  KEY `pengadaan_kpa_penandatangan_id_foreign` (`kpa_penandatangan_id`),
  KEY `pengadaan_usulan_id_index` (`usulan_id`),
  CONSTRAINT `pengadaan_kpa_penandatangan_id_foreign` FOREIGN KEY (`kpa_penandatangan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pengadaan_pejabat_id_foreign` FOREIGN KEY (`pejabat_id`) REFERENCES `users` (`id`),
  CONSTRAINT `pengadaan_pejabat_penandatangan_id_foreign` FOREIGN KEY (`pejabat_penandatangan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pengadaan_penyedia_id_foreign` FOREIGN KEY (`penyedia_id`) REFERENCES `penyedia` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pengadaan_usulan_id_foreign` FOREIGN KEY (`usulan_id`) REFERENCES `usulan_pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of pengadaan
-- ----------------------------
BEGIN;
INSERT INTO `pengadaan` (`id`, `usulan_id`, `nama_paket`, `estimasi_paket`, `pejabat_id`, `pejabat_penandatangan_id`, `kpa_penandatangan_id`, `penyedia_id`, `no_pengadaan`, `metode`, `tanggal_mulai`, `tanggal_selesai`, `no_kontrak`, `tanggal_kontrak`, `nilai_kontrak`, `file_kontrak`, `file_hps`, `status`, `catatan`, `created_at`, `updated_at`) VALUES (1, 1, 'Pengadaan Incobator', 90000000.00, 1, 4, 1, 1, 'PGD/2026/05/001', 'e_purchasing', '2026-05-15', '2026-05-29', 'Peng/01/2026', '2026-05-15', 86000000.00, NULL, NULL, 'kontrak', 'tidak ada', '2026-05-15 11:42:40', '2026-05-15 11:43:15');
INSERT INTO `pengadaan` (`id`, `usulan_id`, `nama_paket`, `estimasi_paket`, `pejabat_id`, `pejabat_penandatangan_id`, `kpa_penandatangan_id`, `penyedia_id`, `no_pengadaan`, `metode`, `tanggal_mulai`, `tanggal_selesai`, `no_kontrak`, `tanggal_kontrak`, `nilai_kontrak`, `file_kontrak`, `file_hps`, `status`, `catatan`, `created_at`, `updated_at`) VALUES (2, 1, 'Pengadaan Infus Syring', 180000000.00, 1, 4, 4, 1, 'PGD/2026/05/002', 'pengadaan_langsung', '2026-05-15', '2026-05-29', 'dsd/02/2026', '2026-05-15', 180000000.00, NULL, NULL, 'kontrak', 'nothing', '2026-05-15 11:44:28', '2026-05-15 11:44:52');
COMMIT;

-- ----------------------------
-- Table structure for pengadaan_item_assignments
-- ----------------------------
DROP TABLE IF EXISTS `pengadaan_item_assignments`;
CREATE TABLE `pengadaan_item_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pengadaan_id` bigint(20) unsigned NOT NULL,
  `usulan_item_id` bigint(20) unsigned NOT NULL,
  `harga_satuan_kontrak` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga satuan hasil negosiasi/kontrak aktual, berbeda dari estimasi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pia_usulan_item_unique` (`usulan_item_id`),
  KEY `pia_pengadaan_id_index` (`pengadaan_id`),
  CONSTRAINT `pengadaan_item_assignments_pengadaan_id_foreign` FOREIGN KEY (`pengadaan_id`) REFERENCES `pengadaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pengadaan_item_assignments_usulan_item_id_foreign` FOREIGN KEY (`usulan_item_id`) REFERENCES `usulan_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of pengadaan_item_assignments
-- ----------------------------
BEGIN;
INSERT INTO `pengadaan_item_assignments` (`id`, `pengadaan_id`, `usulan_item_id`, `harga_satuan_kontrak`, `created_at`, `updated_at`) VALUES (1, 1, 1, 43000000.00, '2026-05-15 11:42:40', '2026-05-15 11:43:15');
INSERT INTO `pengadaan_item_assignments` (`id`, `pengadaan_id`, `usulan_item_id`, `harga_satuan_kontrak`, `created_at`, `updated_at`) VALUES (2, 2, 2, 25000000.00, '2026-05-15 11:44:28', '2026-05-15 11:44:52');
INSERT INTO `pengadaan_item_assignments` (`id`, `pengadaan_id`, `usulan_item_id`, `harga_satuan_kontrak`, `created_at`, `updated_at`) VALUES (3, 2, 3, 20000000.00, '2026-05-15 11:44:28', '2026-05-15 11:44:52');
COMMIT;

-- ----------------------------
-- Table structure for penyedia
-- ----------------------------
DROP TABLE IF EXISTS `penyedia`;
CREATE TABLE `penyedia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(200) NOT NULL,
  `jenis_badan` enum('PT','CV','UD','Firma','Koperasi','Perorangan') NOT NULL DEFAULT 'CV',
  `npwp` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nama_pic` varchar(150) DEFAULT NULL COMMENT 'Person In Charge',
  `rekening_bank` varchar(50) DEFAULT NULL,
  `nama_bank` varchar(100) DEFAULT NULL,
  `atas_nama_rekening` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `penyedia_nama_index` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of penyedia
-- ----------------------------
BEGIN;
INSERT INTO `penyedia` (`id`, `nama`, `jenis_badan`, `npwp`, `alamat`, `telepon`, `email`, `nama_pic`, `rekening_bank`, `nama_bank`, `atas_nama_rekening`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'PT. Sijalucom Kreasi Mandiri', 'Perorangan', '123455', 'Pameungpeuk - Garut', '082119033033', 'ptsijalucomkreasimandiri@gmail.com', 'Nevia Yusup', '1234455667', 'BJB', 'PT Sijalucom Kreasi Mandiri', 1, '2026-05-13 17:47:59', '2026-05-13 17:47:59');
COMMIT;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'slug: sarana_umum, pptk, pejabat_pengadaan, upbj, keuangan, perencanaan, admin',
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of roles
-- ----------------------------
BEGIN;
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (1, 'admin', 'Administrator', 'Akses penuh sistem', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (2, 'sarana_umum', 'Bagian Sarana & Umum', 'Pengguna / pemohon usulan pengadaan', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (3, 'pptk', 'PPTK', 'Pejabat Pelaksana Teknis Kegiatan – penanggung jawab anggaran', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (4, 'pejabat_pengadaan', 'Pejabat Pengadaan', 'Pelaksana proses pengadaan', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (5, 'upbj', 'Bagian UPBJ', 'Administrasi dokumen pengadaan', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (6, 'keuangan', 'Bagian Keuangan', 'Pengelola pembayaran', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES (7, 'perencanaan', 'Bagian Perencanaan', 'Evaluator & pelaporan pengadaan', '2026-05-13 16:32:30', '2026-05-13 16:32:30');
COMMIT;

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of sessions
-- ----------------------------
BEGIN;
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('TwlIoMqOrmKdb2exInc5c6CJRpeBw6lr7IvaTSHN', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJCeGRIVGY5U0FlTE5KRUlVMjNidTlWdGNoT2tVdzc4R0kySkZIdkNvIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwidGFodW5fYW5nZ2FyYW4iOjIwMjYsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC9zYW1wdXJhc3VuLnRlc3RcL3NldHRpbmdzXC9rb3Atc3VyYXQiLCJyb3V0ZSI6InNldHRpbmdzLmtvcC1zdXJhdC5lZGl0In0sInVybCI6W10sImF1dGgiOnsicGFzc3dvcmRfY29uZmlybWVkX2F0IjoxNzc4ODY0NDEyfSwidHdvX2ZhY3Rvcl9lbXB0eV9hdCI6MTc3ODg2NDQxN30=', 1778866958);
COMMIT;

-- ----------------------------
-- Table structure for sub_kegiatan
-- ----------------------------
DROP TABLE IF EXISTS `sub_kegiatan`;
CREATE TABLE `sub_kegiatan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dpa_anggaran_id` bigint(20) unsigned DEFAULT NULL,
  `kode_sub_kegiatan` varchar(255) DEFAULT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `tahun_anggaran` year(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_kegiatan_tahun_anggaran_is_active_index` (`tahun_anggaran`,`is_active`),
  KEY `sub_kegiatan_dpa_anggaran_id_foreign` (`dpa_anggaran_id`),
  CONSTRAINT `sub_kegiatan_dpa_anggaran_id_foreign` FOREIGN KEY (`dpa_anggaran_id`) REFERENCES `dpa_anggaran` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of sub_kegiatan
-- ----------------------------
BEGIN;
INSERT INTO `sub_kegiatan` (`id`, `dpa_anggaran_id`, `kode_sub_kegiatan`, `nama_kegiatan`, `tahun_anggaran`, `is_active`, `created_at`, `updated_at`) VALUES (1, 1, 'SUB-001', 'Pengadaan Sarana dan Prasarana Kantor', 2026, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `sub_kegiatan` (`id`, `dpa_anggaran_id`, `kode_sub_kegiatan`, `nama_kegiatan`, `tahun_anggaran`, `is_active`, `created_at`, `updated_at`) VALUES (2, 2, 'SUB-001', 'Pengadaan Sarana dan Prasarana Kantor', 2027, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `sub_kegiatan` (`id`, `dpa_anggaran_id`, `kode_sub_kegiatan`, `nama_kegiatan`, `tahun_anggaran`, `is_active`, `created_at`, `updated_at`) VALUES (3, 1, '1.02.03.28', 'Pengadaan Alkes', 2026, 1, '2026-05-14 18:11:33', '2026-05-14 18:11:33');
COMMIT;

-- ----------------------------
-- Table structure for unit_kerja
-- ----------------------------
DROP TABLE IF EXISTS `unit_kerja`;
CREATE TABLE `unit_kerja` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_kerja_kode_unique` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of unit_kerja
-- ----------------------------
BEGIN;
INSERT INTO `unit_kerja` (`id`, `kode`, `nama`, `alamat`, `telepon`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'UK-001', 'UOBK RSUD Pameungpeuk Prov. Jawa Barat', 'Jl. Miramare No. 99 Ds. Sirnabakti Kec. Pameungpeuk Kab. Garut', '0262 - 521119', 1, '2026-05-13 16:32:30', '2026-05-13 16:32:30');
COMMIT;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `unit_kerja_id` bigint(20) unsigned DEFAULT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `jabatan` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nip_unique` (`nip`),
  KEY `users_unit_kerja_id_foreign` (`unit_kerja_id`),
  KEY `users_role_id_is_active_index` (`role_id`,`is_active`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_unit_kerja_id_foreign` FOREIGN KEY (`unit_kerja_id`) REFERENCES `unit_kerja` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (1, 1, 1, '198001012010011001', 'Admin Sistem', 'admin@instansi.go.id', '2026-05-13 16:32:31', '$2y$12$nmPJFeX9JubG2xaeOK24feinOyQOsBo2CcYdxueuDJYThKp1CE8ya', NULL, NULL, NULL, 'Administrator', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:31', '2026-05-13 16:32:31');
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (2, 2, 1, '198202022011022001', 'Budi Sarana', 'sarana@instansi.go.id', '2026-05-13 16:32:31', '$2y$12$FFWF845ZpM9/KJOwgWUNJuSTN6TCwa3QwuDqubl1HAc7SG3ZliLpG', NULL, NULL, NULL, 'Bagian Sarana & Umum', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:31', '2026-05-13 16:32:31');
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (3, 3, 1, '197505052005051001', 'Citra Pratiwi', 'pptk@instansi.go.id', '2026-05-13 16:32:31', '$2y$12$y7yQVep3td/NlIE2DBwyTO1paIXRB/N7T4uY5Bp1FIziPea7ZHSqS', NULL, NULL, NULL, 'PPTK', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:31', '2026-05-13 16:32:31');
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (4, 4, 1, '198808082012081001', 'Dimas Hartono', 'pengadaan@instansi.go.id', '2026-05-13 16:32:32', '$2y$12$/29pi6kM6zKi109ShEAT.eEi/oRo1omUua5odi8rjV149mQTjR8h.', NULL, NULL, NULL, 'Pejabat Pengadaan', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (5, 5, 1, '199003032015031002', 'Eka Wijaya', 'upbj@instansi.go.id', '2026-05-13 16:32:32', '$2y$12$Oc6OJ0Sf.FQuS8uHxANLHOHLu1P4Js4Bwx1NBy9bUgSTufh7Si2iC', NULL, NULL, NULL, 'Bagian UPBJ', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (6, 6, 1, '198707072013071001', 'Fitri Ananda', 'keuangan@instansi.go.id', '2026-05-13 16:32:32', '$2y$12$/qXXC73SJiEvqHIOlGtjouIvj.n.ygafPOU6YUcA3D70B.EddgqP6', NULL, NULL, NULL, 'Bagian Keuangan', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `users` (`id`, `role_id`, `unit_kerja_id`, `nip`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `jabatan`, `phone`, `alamat`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES (7, 7, 1, '198404042014041001', 'Gunawan Saputra', 'perencanaan@instansi.go.id', '2026-05-13 16:32:32', '$2y$12$LAvvzkJcf3uX7zHlUyX13.jQybzTGHCCofyWM9AxNrGKQ31sHMPMq', NULL, NULL, NULL, 'Bagian Perencanaan', NULL, NULL, NULL, 1, NULL, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
COMMIT;

-- ----------------------------
-- Table structure for usulan_items
-- ----------------------------
DROP TABLE IF EXISTS `usulan_items`;
CREATE TABLE `usulan_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usulan_id` bigint(20) unsigned NOT NULL,
  `kategori_id` bigint(20) unsigned NOT NULL,
  `nama_barang` varchar(200) NOT NULL,
  `spesifikasi` text DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `satuan` varchar(30) NOT NULL DEFAULT 'unit',
  `harga_satuan_estimasi` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usulan_items_kategori_id_foreign` (`kategori_id`),
  KEY `usulan_items_usulan_id_index` (`usulan_id`),
  CONSTRAINT `usulan_items_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_barang` (`id`),
  CONSTRAINT `usulan_items_usulan_id_foreign` FOREIGN KEY (`usulan_id`) REFERENCES `usulan_pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of usulan_items
-- ----------------------------
BEGIN;
INSERT INTO `usulan_items` (`id`, `usulan_id`, `kategori_id`, `nama_barang`, `spesifikasi`, `jumlah`, `satuan`, `harga_satuan_estimasi`, `subtotal`, `keterangan`, `created_at`, `updated_at`) VALUES (1, 1, 5, 'Incubator', 'Incubator', 2, 'unit', 45000000.00, 90000000.00, NULL, '2026-05-15 11:42:02', '2026-05-15 11:42:02');
INSERT INTO `usulan_items` (`id`, `usulan_id`, `kategori_id`, `nama_barang`, `spesifikasi`, `jumlah`, `satuan`, `harga_satuan_estimasi`, `subtotal`, `keterangan`, `created_at`, `updated_at`) VALUES (2, 1, 5, 'Infus Pump', 'Infus', 4, 'unit', 25000000.00, 100000000.00, NULL, '2026-05-15 11:42:02', '2026-05-15 11:42:02');
INSERT INTO `usulan_items` (`id`, `usulan_id`, `kategori_id`, `nama_barang`, `spesifikasi`, `jumlah`, `satuan`, `harga_satuan_estimasi`, `subtotal`, `keterangan`, `created_at`, `updated_at`) VALUES (3, 1, 5, 'Syring pump', 'syring', 4, 'unit', 20000000.00, 80000000.00, NULL, '2026-05-15 11:42:02', '2026-05-15 11:42:02');
COMMIT;

-- ----------------------------
-- Table structure for usulan_pengadaan
-- ----------------------------
DROP TABLE IF EXISTS `usulan_pengadaan`;
CREATE TABLE `usulan_pengadaan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `no_usulan` varchar(30) NOT NULL COMMENT 'format: USL/{YYYY}/{MM}/{seq}',
  `pemohon_id` bigint(20) unsigned NOT NULL,
  `anggaran_id` bigint(20) unsigned NOT NULL,
  `tanggal_usulan` date NOT NULL,
  `judul` varchar(200) NOT NULL,
  `latar_belakang` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `total_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','diajukan','disetujui','ditolak','dalam_pengadaan','dokumen','pembayaran','evaluasi','selesai','dibatalkan') NOT NULL DEFAULT 'draft',
  `catatan_pemohon` text DEFAULT NULL,
  `file_pendukung` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usulan_pengadaan_no_usulan_unique` (`no_usulan`),
  KEY `usulan_pengadaan_anggaran_id_foreign` (`anggaran_id`),
  KEY `usulan_pengadaan_status_tanggal_usulan_index` (`status`,`tanggal_usulan`),
  KEY `usulan_pengadaan_pemohon_id_index` (`pemohon_id`),
  CONSTRAINT `usulan_pengadaan_anggaran_id_foreign` FOREIGN KEY (`anggaran_id`) REFERENCES `anggaran` (`id`),
  CONSTRAINT `usulan_pengadaan_pemohon_id_foreign` FOREIGN KEY (`pemohon_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of usulan_pengadaan
-- ----------------------------
BEGIN;
INSERT INTO `usulan_pengadaan` (`id`, `no_usulan`, `pemohon_id`, `anggaran_id`, `tanggal_usulan`, `judul`, `latar_belakang`, `keterangan`, `total_estimasi`, `status`, `catatan_pemohon`, `file_pendukung`, `submitted_at`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'USL/2026/05/001', 1, 3, '2026-05-15', 'Usulan Alat Kedokteran Umum', 'Untuk NICU', 'NICU', 270000000.00, 'dalam_pengadaan', NULL, NULL, '2026-05-15 11:42:02', '2026-05-15 11:42:02', '2026-05-15 11:43:15', NULL);
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
