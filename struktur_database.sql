
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of activity_logs
-- ----------------------------
BEGIN;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of anggaran
-- ----------------------------
BEGIN;
INSERT INTO `anggaran` (`id`, `sub_kegiatan_id`, `tahun`, `kode_rekening`, `nama_rekening`, `uraian`, `pagu`, `terpakai`, `is_active`, `created_at`, `updated_at`) VALUES (1, 1, 2026, '5.1.02.01.01.024', 'Belanja Modal Peralatan dan Mesin', 'Belanja kebutuhan peralatan dan mesin kantor', 400000000.00, 0.00, 1, '2026-05-13 16:32:32', '2026-05-13 16:52:39');
INSERT INTO `anggaran` (`id`, `sub_kegiatan_id`, `tahun`, `kode_rekening`, `nama_rekening`, `uraian`, `pagu`, `terpakai`, `is_active`, `created_at`, `updated_at`) VALUES (2, 2, 2027, '5.1.02.01.01.024', 'Belanja Modal Peralatan dan Mesin', 'Belanja kebutuhan peralatan dan mesin kantor', 500000000.00, 0.00, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of approvals
-- ----------------------------
BEGIN;
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
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('sampurasun-cache-bbea1129a9707f339cf3d5f4977b7dd6', 'i:1;', 1778690427);
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('sampurasun-cache-bbea1129a9707f339cf3d5f4977b7dd6:timer', 'i:1778690427;', 1778690427);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of document_sequences
-- ----------------------------
BEGIN;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dokumen_pengadaan
-- ----------------------------
BEGIN;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dokumen_upbj
-- ----------------------------
BEGIN;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  UNIQUE KEY `pengadaan_usulan_id_unique` (`usulan_id`),
  UNIQUE KEY `pengadaan_no_pengadaan_unique` (`no_pengadaan`),
  KEY `pengadaan_pejabat_id_foreign` (`pejabat_id`),
  KEY `pengadaan_penyedia_id_foreign` (`penyedia_id`),
  KEY `pengadaan_status_index` (`status`),
  KEY `pengadaan_pejabat_penandatangan_id_foreign` (`pejabat_penandatangan_id`),
  KEY `pengadaan_kpa_penandatangan_id_foreign` (`kpa_penandatangan_id`),
  CONSTRAINT `pengadaan_kpa_penandatangan_id_foreign` FOREIGN KEY (`kpa_penandatangan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pengadaan_pejabat_id_foreign` FOREIGN KEY (`pejabat_id`) REFERENCES `users` (`id`),
  CONSTRAINT `pengadaan_pejabat_penandatangan_id_foreign` FOREIGN KEY (`pejabat_penandatangan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pengadaan_penyedia_id_foreign` FOREIGN KEY (`penyedia_id`) REFERENCES `penyedia` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pengadaan_usulan_id_foreign` FOREIGN KEY (`usulan_id`) REFERENCES `usulan_pengadaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of pengadaan
-- ----------------------------
BEGIN;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of penyedia
-- ----------------------------
BEGIN;
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
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('QX2idN2mbpGBvaXv8t93N5IGlXksK9zSI7g5Ttse', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJ6VlV2TVZOSDlJVVdCNnM4NHlZVXlDYUYybFV4akMxYUk3TWpRRm5LIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvc2FtcHVyYXN1bi50ZXN0XC9hbmdnYXJhbiIsInJvdXRlIjoiYW5nZ2FyYW4uaW5kZXgifSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInRhaHVuX2FuZ2dhcmFuIjoyMDI2fQ==', 1778691180);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of sub_kegiatan
-- ----------------------------
BEGIN;
INSERT INTO `sub_kegiatan` (`id`, `dpa_anggaran_id`, `kode_sub_kegiatan`, `nama_kegiatan`, `tahun_anggaran`, `is_active`, `created_at`, `updated_at`) VALUES (1, 1, 'SUB-001', 'Pengadaan Sarana dan Prasarana Kantor', 2026, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
INSERT INTO `sub_kegiatan` (`id`, `dpa_anggaran_id`, `kode_sub_kegiatan`, `nama_kegiatan`, `tahun_anggaran`, `is_active`, `created_at`, `updated_at`) VALUES (2, 2, 'SUB-001', 'Pengadaan Sarana dan Prasarana Kantor', 2027, 1, '2026-05-13 16:32:32', '2026-05-13 16:32:32');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of usulan_items
-- ----------------------------
BEGIN;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of usulan_pengadaan
-- ----------------------------
BEGIN;
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
