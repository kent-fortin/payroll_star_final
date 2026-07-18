<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/functions.php';

// ==========================================
// KONFIGURASI DATABASE
// ==========================================

// --- VERSI LOCAL ---
// $host = 'localhost';
// $user = 'root';
// $pass = '';
// $db = 'db_payroll_star_samudera';

// --- VERSI LIVE ---
$host = 'sql312.infinityfree.com';
$user = 'if0_42362934';
$pass = 'fFQbSZ02B5U';
$db = 'if0_42362934_db_payroll_star_samudera';


$conn = mysqli_connect($host, $user, $pass, $db);
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    
    // Auto-migrate for missing columns
    $qKaryawan = @mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE 'no_ktp'");
    if ($qKaryawan && mysqli_num_rows($qKaryawan) == 0) {
        @mysqli_query($conn, "ALTER TABLE karyawan ADD no_ktp VARCHAR(20) NULL, ADD no_kk VARCHAR(20) NULL");
    }

    // Auto-migrate: buat tabel presensi_harian jika belum ada
    $qPresensi = @mysqli_query($conn, "SHOW TABLES LIKE 'presensi_harian'");
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
} else {
    die(mysqli_connect_error());
}
