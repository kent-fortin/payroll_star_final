-- Database Dump (Latest Local Structure)

DROP TABLE IF EXISTS `absensi`;
CREATE TABLE `absensi` (
  `id_absensi` int(11) NOT NULL AUTO_INCREMENT,
  `id_karyawan` int(11) NOT NULL,
  `bulan` varchar(20) NOT NULL,
  `tahun` year(4) NOT NULL,
  `hadir` int(11) NOT NULL DEFAULT 0,
  `sakit` int(11) NOT NULL DEFAULT 0,
  `izin` int(11) NOT NULL DEFAULT 0,
  `alpha` int(11) NOT NULL DEFAULT 0,
  `dibuat_oleh` int(11) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id_absensi`),
  UNIQUE KEY `unik_absensi_bulanan` (`id_karyawan`,`bulan`,`tahun`),
  KEY `fk_absensi_user` (`dibuat_oleh`),
  CONSTRAINT `fk_absensi_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE,
  CONSTRAINT `fk_absensi_user` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jabatan`;
CREATE TABLE `jabatan` (
  `id_jabatan` int(11) NOT NULL AUTO_INCREMENT,
  `kode_jabatan` varchar(20) NOT NULL,
  `nama_jabatan` varchar(100) NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL,
  `status_jabatan` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_jabatan`),
  UNIQUE KEY `kode_jabatan` (`kode_jabatan`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES ('1', 'JBT001', 'Manager Operasional', '8000000.00', 'Tidak Aktif');
INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES ('2', 'JBT002', 'Supervisor Administrasi', '6500000.00', 'Aktif');
INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES ('3', 'JBT003', 'Staff Administrasi', '4500000.00', 'Aktif');
INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES ('4', 'JBT004', 'Staff Keuangan', '5000000.00', 'Aktif');
INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES ('5', 'JBT005', 'Driver Logistik', '4000000.00', 'Aktif');
INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES ('6', 'JBT006', 'Staff Gudang', '3800000.00', 'Aktif');

DROP TABLE IF EXISTS `karyawan`;
CREATE TABLE `karyawan` (
  `id_karyawan` int(11) NOT NULL AUTO_INCREMENT,
  `nip` varchar(20) NOT NULL,
  `nama_karyawan` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `id_jabatan` int(11) NOT NULL,
  `status_karyawan` enum('Tetap','Kontrak','Resign') NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `no_ktp` varchar(20) DEFAULT NULL,
  `no_kk` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_karyawan`),
  UNIQUE KEY `nip` (`nip`),
  KEY `fk_karyawan_jabatan` (`id_jabatan`),
  CONSTRAINT `fk_karyawan_jabatan` FOREIGN KEY (`id_jabatan`) REFERENCES `jabatan` (`id_jabatan`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lembur`;
CREATE TABLE `lembur` (
  `id_lembur` int(11) NOT NULL AUTO_INCREMENT,
  `id_karyawan` int(11) NOT NULL,
  `tanggal_lembur` date NOT NULL,
  `jam_lembur` int(11) NOT NULL DEFAULT 0,
  `dibuat_oleh` int(11) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_lembur`),
  KEY `fk_lembur_karyawan` (`id_karyawan`),
  KEY `fk_lembur_user` (`dibuat_oleh`),
  CONSTRAINT `fk_lembur_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lembur_user` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data lembur harian karyawan';

DROP TABLE IF EXISTS `payroll`;
CREATE TABLE `payroll` (
  `id_payroll` int(11) NOT NULL AUTO_INCREMENT,
  `id_karyawan` int(11) NOT NULL,
  `bulan` varchar(20) NOT NULL,
  `tahun` year(4) NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL,
  `jam_lembur` int(11) NOT NULL DEFAULT 0,
  `tarif_lembur` decimal(12,2) NOT NULL DEFAULT 15000.00,
  `total_lembur` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_tunjangan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `jumlah_alpha` int(11) NOT NULL DEFAULT 0,
  `tarif_alpha` decimal(12,2) NOT NULL DEFAULT 25000.00,
  `total_potongan_alpha` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_gaji_bersih` decimal(12,2) NOT NULL,
  `status_pembayaran` enum('Belum Dibayar','Sudah Dibayar') NOT NULL DEFAULT 'Belum Dibayar',
  `status_validasi` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `tanggal_pembayaran` date DEFAULT NULL,
  `tanggal_proses` datetime NOT NULL DEFAULT current_timestamp(),
  `diproses_oleh` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_payroll`),
  UNIQUE KEY `unik_payroll_bulanan` (`id_karyawan`,`bulan`,`tahun`),
  KEY `fk_payroll_user` (`diproses_oleh`),
  CONSTRAINT `fk_payroll_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE,
  CONSTRAINT `fk_payroll_user` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pengaturan_payroll`;
CREATE TABLE `pengaturan_payroll` (
  `id_pengaturan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_pengaturan` varchar(60) NOT NULL,
  `nilai` decimal(12,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_pengaturan`),
  UNIQUE KEY `nama_pengaturan` (`nama_pengaturan`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengaturan_payroll` (`id_pengaturan`, `nama_pengaturan`, `nilai`, `keterangan`) VALUES ('1', 'tarif_lembur_per_jam', '15000.00', 'Tarif lembur setiap satu jam');
INSERT INTO `pengaturan_payroll` (`id_pengaturan`, `nama_pengaturan`, `nilai`, `keterangan`) VALUES ('2', 'potongan_alpha_per_hari', '25000.00', 'Potongan untuk setiap satu hari alpha');
INSERT INTO `pengaturan_payroll` (`id_pengaturan`, `nama_pengaturan`, `nilai`, `keterangan`) VALUES ('3', 'total_hari_kerja', '26.00', 'Standar total hari kerja dalam sebulan');

DROP TABLE IF EXISTS `permintaan_edit_absensi`;
CREATE TABLE `permintaan_edit_absensi` (
  `id_permintaan` int(11) NOT NULL AUTO_INCREMENT,
  `id_absensi` int(11) NOT NULL,
  `hadir_baru` int(11) NOT NULL,
  `sakit_baru` int(11) NOT NULL,
  `izin_baru` int(11) NOT NULL,
  `alpha_baru` int(11) NOT NULL,
  `alasan_perubahan` varchar(255) NOT NULL,
  `data_lama` text NOT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `id_pengaju` int(11) NOT NULL,
  `id_penyetuju` int(11) DEFAULT NULL,
  `tanggal_pengajuan` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_keputusan` datetime DEFAULT NULL,
  `catatan_pimpinan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_permintaan`),
  KEY `fk_permintaan_absensi` (`id_absensi`),
  KEY `fk_permintaan_pengaju` (`id_pengaju`),
  KEY `fk_permintaan_penyetuju` (`id_penyetuju`),
  CONSTRAINT `fk_permintaan_absensi` FOREIGN KEY (`id_absensi`) REFERENCES `absensi` (`id_absensi`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_permintaan_pengaju` FOREIGN KEY (`id_pengaju`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_permintaan_penyetuju` FOREIGN KEY (`id_penyetuju`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `presensi_harian`;
CREATE TABLE `presensi_harian` (
  `id_presensi` int(11) NOT NULL AUTO_INCREMENT,
  `id_karyawan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status_kehadiran` enum('Hadir','Sakit','Izin','Alpha') NOT NULL DEFAULT 'Hadir',
  PRIMARY KEY (`id_presensi`),
  UNIQUE KEY `unik_presensi` (`id_karyawan`,`tanggal`),
  CONSTRAINT `fk_presensi_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Presensi harian karyawan';

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','pimpinan') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_whatsapp` varchar(30) DEFAULT NULL,
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expired_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `email`, `no_whatsapp`, `reset_otp`, `reset_otp_expired_at`) VALUES ('1', 'admin', '$2y$12$qOoEq1cYdKjhvFcREUFnEeXxN6YQCY9g6n63CeSk15.h5KjcH4Axy', 'Administrator Payroll', 'admin', 'angelinocttt@gmail.com', '6287738565119', NULL, NULL);
INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `email`, `no_whatsapp`, `reset_otp`, `reset_otp_expired_at`) VALUES ('2', 'pimpinan', '$2y$12$cKBHjEPMil4982WxBgAoZuff8wb0ccZcbSNRAHRG82A.5meQlWQWi', 'Pimpinan PT Star Samudera Logistik', 'pimpinan', 'kentfh206@gmail.com', '6281933630535', NULL, NULL);

