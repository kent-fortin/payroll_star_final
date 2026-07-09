DROP DATABASE IF EXISTS db_payroll_star_samudera;
CREATE DATABASE db_payroll_star_samudera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_payroll_star_samudera;

CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin','pimpinan') NOT NULL,
    email VARCHAR(100) NULL,
    no_whatsapp VARCHAR(30) NULL,
    reset_otp VARCHAR(10) NULL,
    reset_otp_expired_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE pengaturan_payroll (
    id_pengaturan INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengaturan VARCHAR(60) NOT NULL UNIQUE,
    nilai DECIMAL(12,2) NOT NULL,
    keterangan VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE jabatan (
    id_jabatan INT AUTO_INCREMENT PRIMARY KEY,
    kode_jabatan VARCHAR(20) NOT NULL UNIQUE,
    nama_jabatan VARCHAR(100) NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE karyawan (
    id_karyawan INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(20) NOT NULL UNIQUE,
    nama_karyawan VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    id_jabatan INT NOT NULL,
    status_karyawan ENUM('Tetap','Kontrak') NOT NULL,
    tanggal_masuk DATE NOT NULL,
    CONSTRAINT fk_karyawan_jabatan FOREIGN KEY (id_jabatan) REFERENCES jabatan(id_jabatan) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE absensi (
    id_absensi INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    bulan VARCHAR(20) NOT NULL,
    tahun YEAR NOT NULL,
    hadir INT NOT NULL DEFAULT 0,
    sakit INT NOT NULL DEFAULT 0,
    izin INT NOT NULL DEFAULT 0,
    alpha INT NOT NULL DEFAULT 0,
    lembur_jam INT NOT NULL DEFAULT 0,
    dibuat_oleh INT NULL,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME NULL,
    UNIQUE KEY unik_absensi_bulanan (id_karyawan, bulan, tahun),
    CONSTRAINT fk_absensi_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_absensi_user FOREIGN KEY (dibuat_oleh) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE permintaan_edit_absensi (
    id_permintaan INT AUTO_INCREMENT PRIMARY KEY,
    id_absensi INT NOT NULL,
    hadir_baru INT NOT NULL,
    sakit_baru INT NOT NULL,
    izin_baru INT NOT NULL,
    alpha_baru INT NOT NULL,
    lembur_jam_baru INT NOT NULL,
    alasan_perubahan VARCHAR(255) NOT NULL,
    data_lama TEXT NOT NULL,
    status ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
    id_pengaju INT NOT NULL,
    id_penyetuju INT NULL,
    tanggal_pengajuan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_keputusan DATETIME NULL,
    catatan_pimpinan VARCHAR(255) NULL,
    CONSTRAINT fk_permintaan_absensi FOREIGN KEY (id_absensi) REFERENCES absensi(id_absensi) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_permintaan_pengaju FOREIGN KEY (id_pengaju) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_permintaan_penyetuju FOREIGN KEY (id_penyetuju) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payroll (
    id_payroll INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    bulan VARCHAR(20) NOT NULL,
    tahun YEAR NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL,
    jam_lembur INT NOT NULL DEFAULT 0,
    tarif_lembur DECIMAL(12,2) NOT NULL DEFAULT 15000,
    total_lembur DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_tunjangan DECIMAL(12,2) NOT NULL DEFAULT 0,
    jumlah_alpha INT NOT NULL DEFAULT 0,
    tarif_alpha DECIMAL(12,2) NOT NULL DEFAULT 25000,
    total_potongan_alpha DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_gaji_bersih DECIMAL(12,2) NOT NULL,
    status_pembayaran ENUM('Belum Dibayar','Sudah Dibayar') NOT NULL DEFAULT 'Belum Dibayar',
    tanggal_pembayaran DATE NULL,
    tanggal_proses DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diproses_oleh INT NULL,
    UNIQUE KEY unik_payroll_bulanan (id_karyawan, bulan, tahun),
    CONSTRAINT fk_payroll_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payroll_user FOREIGN KEY (diproses_oleh) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (username,password,nama_lengkap,role,email,no_whatsapp) VALUES
('admin','$2y$12$qOoEq1cYdKjhvFcREUFnEeXxN6YQCY9g6n63CeSk15.h5KjcH4Axy','Administrator Payroll','admin','angelinocttt@gmail.com','6287738565119'),
('pimpinan','$2y$12$cKBHjEPMil4982WxBgAoZuff8wb0ccZcbSNRAHRG82A.5meQlWQWi','Pimpinan PT Star Samudera Logistik','pimpinan','kentfh206@gmail.com','6281933630535');

INSERT INTO pengaturan_payroll (nama_pengaturan,nilai,keterangan) VALUES
('tarif_lembur_per_jam',15000,'Tarif lembur setiap satu jam'),
('potongan_alpha_per_hari',25000,'Potongan untuk setiap satu hari alpha');

INSERT INTO jabatan (kode_jabatan,nama_jabatan,gaji_pokok) VALUES
('JBT001','Manager Operasional',8000000),
('JBT002','Supervisor Administrasi',6500000),
('JBT003','Staff Administrasi',4500000),
('JBT004','Staff Keuangan',5000000),
('JBT005','Driver Logistik',4000000),
('JBT006','Staff Gudang',3800000);

INSERT INTO karyawan (nip,nama_karyawan,jenis_kelamin,id_jabatan,status_karyawan,tanggal_masuk) VALUES
('SSL001','Ari Masjaya','L',3,'Tetap','2022-01-10'),
('SSL002','Budi Santoso','L',5,'Tetap','2021-03-15'),
('SSL003','Citra Lestari','P',4,'Tetap','2023-02-01'),
('SSL004','Dedi Saputra','L',6,'Kontrak','2023-06-12'),
('SSL005','Eka Pratiwi','P',2,'Tetap','2020-11-03'),
('SSL006','Fajar Hidayat','L',5,'Kontrak','2024-01-08'),
('SSL007','Gita Amelia','P',3,'Tetap','2022-07-20'),
('SSL008','Hendra Wijaya','L',6,'Tetap','2021-09-14'),
('SSL009','Intan Permata','P',4,'Tetap','2022-04-05'),
('SSL010','Joko Prabowo','L',1,'Tetap','2019-08-01'),
('SSL011','Kiki Aulia','P',3,'Kontrak','2024-02-19'),
('SSL012','Lukman Hakim','L',5,'Tetap','2021-12-10'),
('SSL013','Maya Sari','P',6,'Kontrak','2023-10-02'),
('SSL014','Nanda Putra','L',3,'Tetap','2022-05-16'),
('SSL015','Olivia Marbun','P',4,'Tetap','2020-06-22');

INSERT INTO absensi (id_karyawan,bulan,tahun,hadir,sakit,izin,alpha,lembur_jam,dibuat_oleh) VALUES
(1,'April',2026,24,1,0,0,6,1),(2,'April',2026,23,0,1,1,4,1),(3,'April',2026,25,0,0,0,5,1),
(4,'April',2026,22,1,1,1,3,1),(5,'April',2026,25,0,0,0,8,1),(6,'April',2026,24,0,0,1,6,1),
(7,'April',2026,25,0,0,0,4,1),(8,'April',2026,23,1,0,1,2,1),(9,'April',2026,25,0,0,0,4,1),
(10,'April',2026,25,0,0,0,10,1),(11,'April',2026,24,0,1,0,2,1),(12,'April',2026,23,0,1,1,5,1),
(13,'April',2026,24,0,0,1,3,1),(14,'April',2026,25,0,0,0,4,1),(15,'April',2026,25,0,0,0,6,1),
(1,'Mei',2026,25,0,0,0,4,1),(2,'Mei',2026,24,0,1,0,5,1),(3,'Mei',2026,24,1,0,0,6,1),
(4,'Mei',2026,23,0,1,1,2,1),(5,'Mei',2026,25,0,0,0,7,1),(6,'Mei',2026,23,1,0,1,5,1),
(7,'Mei',2026,25,0,0,0,3,1),(8,'Mei',2026,24,0,1,0,4,1),(9,'Mei',2026,25,0,0,0,5,1),
(10,'Mei',2026,25,0,0,0,8,1),(11,'Mei',2026,24,0,1,0,4,1),(12,'Mei',2026,24,0,0,1,6,1),
(13,'Mei',2026,23,1,0,1,2,1),(14,'Mei',2026,25,0,0,0,5,1),(15,'Mei',2026,24,1,0,0,4,1);

INSERT INTO payroll
(id_karyawan,bulan,tahun,gaji_pokok,jam_lembur,tarif_lembur,total_lembur,jumlah_alpha,tarif_alpha,total_potongan_alpha,total_gaji_bersih,status_pembayaran,tanggal_pembayaran,tanggal_proses,diproses_oleh)
SELECT k.id_karyawan,a.bulan,a.tahun,j.gaji_pokok,a.lembur_jam,15000,a.lembur_jam*15000,a.alpha,25000,a.alpha*25000,
       j.gaji_pokok+(a.lembur_jam*15000)-(a.alpha*25000),
       CASE WHEN k.id_karyawan<=10 THEN 'Sudah Dibayar' ELSE 'Belum Dibayar' END,
       CASE WHEN k.id_karyawan<=10 THEN '2026-04-30' ELSE NULL END,
       '2026-04-30 10:00:00',1
FROM absensi a JOIN karyawan k ON k.id_karyawan=a.id_karyawan JOIN jabatan j ON j.id_jabatan=k.id_jabatan
WHERE a.bulan='April' AND a.tahun=2026;

INSERT INTO payroll
(id_karyawan,bulan,tahun,gaji_pokok,jam_lembur,tarif_lembur,total_lembur,jumlah_alpha,tarif_alpha,total_potongan_alpha,total_gaji_bersih,status_pembayaran,tanggal_pembayaran,tanggal_proses,diproses_oleh)
SELECT k.id_karyawan,a.bulan,a.tahun,j.gaji_pokok,a.lembur_jam,15000,a.lembur_jam*15000,a.alpha,25000,a.alpha*25000,
       j.gaji_pokok+(a.lembur_jam*15000)-(a.alpha*25000),
       CASE WHEN k.id_karyawan<=7 THEN 'Sudah Dibayar' ELSE 'Belum Dibayar' END,
       CASE WHEN k.id_karyawan<=7 THEN '2026-05-31' ELSE NULL END,
       '2026-05-31 10:00:00',1
FROM absensi a JOIN karyawan k ON k.id_karyawan=a.id_karyawan JOIN jabatan j ON j.id_jabatan=k.id_jabatan
WHERE a.bulan='Mei' AND a.tahun=2026;
