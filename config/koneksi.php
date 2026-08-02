<?php
/**
 * ============================================================================
 * NAMA FILE: koneksi.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Jembatan penghubung utama antara aplikasi PHP dan database MySQL.
 *
 * ALUR & FITUR UTAMA:
 * 1. Menyimpan parameter koneksi database (host, user, password, db name).
 * 2. Mengatur mode error reporting yang aman (tanpa memunculkan pesan error fatal ke layar).
 * 3. Auto-migrasi untuk membuat tabel atau kolom pendukung secara otomatis jika belum ada.
 *
 * HAK AKSES / PENGGUNA: Sistem / Semua File
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
mysqli_report(MYSQLI_REPORT_OFF);

// [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../helpers/functions.php';

// ==========================================
// KONFIGURASI DATABASE
// ==========================================

// --- VERSI LOCAL ---
// --- SECTION 1: PARAMETER KONEKSI DATABASE ---
// Konfigurasi host, user, password, dan nama database MySQL.
// $host = 'localhost';
// $user = 'root';
// $pass = '';
// $db = 'db_payroll_star_samudera';

// --- VERSI LIVE ---
$host = 'sql312.infinityfree.com';
$user = 'if0_42362934';
$pass = 'fFQbSZ02B5U';
$db = 'if0_42362934_db_payroll_star_samudera';


// --- SECTION 2: PEMBUATAN KONEKSI & ERROR REPORTING ---
// [PENCARIAN-FUNGSI: KONEKSI DATABASE] Menghubungkan script PHP dengan server MySQL menggunakan mysqli_connect
$conn = mysqli_connect($host, $user, $pass, $db);
// [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    @mysqli_query($conn, "SET time_zone = '+07:00'");

    // --- SECTION 3: AUTO-MIGRASI SKEMA DATABASE ---
    // Mengecek dan membuat tabel/kolom baru secara otomatis jika belum tersedia di database.
    $qKaryawan = @mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE 'no_ktp'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qKaryawan && mysqli_num_rows($qKaryawan) == 0) {
        @mysqli_query($conn, "ALTER TABLE karyawan ADD no_ktp VARCHAR(20) NULL, ADD no_kk VARCHAR(20) NULL");
    }
    @mysqli_query($conn, "ALTER TABLE karyawan MODIFY status_karyawan ENUM('Tetap','Kontrak','Resign') NOT NULL");

    $qJabatan = @mysqli_query($conn, "SHOW COLUMNS FROM jabatan LIKE 'status_jabatan'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qJabatan && mysqli_num_rows($qJabatan) == 0) {
        @mysqli_query($conn, "ALTER TABLE jabatan ADD status_jabatan ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif'");
    }

    $qPayroll1 = @mysqli_query($conn, "SHOW COLUMNS FROM payroll LIKE 'total_tunjangan'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qPayroll1 && mysqli_num_rows($qPayroll1) == 0) {
        @mysqli_query($conn, "ALTER TABLE payroll ADD total_tunjangan DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_lembur");
    }
    $qPayroll2 = @mysqli_query($conn, "SHOW COLUMNS FROM payroll LIKE 'status_validasi'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qPayroll2 && mysqli_num_rows($qPayroll2) == 0) {
        @mysqli_query($conn, "ALTER TABLE payroll ADD status_validasi ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu' AFTER status_pembayaran");
    }

    $qAbsensi = @mysqli_query($conn, "SHOW COLUMNS FROM absensi LIKE 'diperbarui_pada'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qAbsensi && mysqli_num_rows($qAbsensi) == 0) {
        @mysqli_query($conn, "ALTER TABLE absensi ADD diperbarui_pada DATETIME NULL");
    }

    $qSetting = @mysqli_query($conn, "SELECT id_pengaturan FROM pengaturan_payroll WHERE nama_pengaturan = 'total_hari_kerja'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qSetting && mysqli_num_rows($qSetting) == 0) {
        @mysqli_query($conn, "INSERT INTO pengaturan_payroll (nama_pengaturan, nilai, keterangan) VALUES ('total_hari_kerja', 26, 'Standar total hari kerja dalam sebulan')");
    }

    $qPresensi = @mysqli_query($conn, "SHOW TABLES LIKE 'presensi_harian'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qPresensi && mysqli_num_rows($qPresensi) == 0) {
        @mysqli_query($conn, "CREATE TABLE presensi_harian (
            id_presensi      INT AUTO_INCREMENT PRIMARY KEY,
            id_karyawan      INT NOT NULL,
            tanggal          DATE NOT NULL,
            status_kehadiran ENUM('Hadir','Sakit','Izin','Alpha') NOT NULL DEFAULT 'Hadir',
            UNIQUE KEY unik_presensi (id_karyawan, tanggal),
            CONSTRAINT fk_presensi_karyawan FOREIGN KEY (id_karyawan)
                REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB COMMENT='Presensi harian karyawan'");
    }

    $qLembur = @mysqli_query($conn, "SHOW TABLES LIKE 'lembur'");
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($qLembur && mysqli_num_rows($qLembur) == 0) {
        @mysqli_query($conn, "CREATE TABLE lembur (
            id_lembur INT AUTO_INCREMENT PRIMARY KEY,
            id_karyawan INT NOT NULL,
            tanggal_lembur DATE NOT NULL,
            jam_lembur INT NOT NULL DEFAULT 0,
            dibuat_oleh INT NULL,
            dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_lembur_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_lembur_user FOREIGN KEY (dibuat_oleh) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
        ) ENGINE=InnoDB COMMENT='Data lembur harian karyawan'");
    }
// [PENJELASAN LOGIKA]: Menjalankan blok perintah default (Else) karena semua kondisi di atasnya tidak terpenuhi
} else {
    die(mysqli_connect_error());
}
