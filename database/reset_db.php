<?php
/**
 * ============================================================================
 * NAMA FILE: reset_db.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Skrip utilitas untuk mengosongkan dan mereset ulang seluruh data transaksi aplikasi.
 *
 * ALUR & FITUR UTAMA:
 * 1. Mengosongkan (truncate) tabel absensi, presensi, lembur, payroll, dan edit absensi.
 * 2. Mereset nilai AUTO_INCREMENT tabel karyawan dan transaksi kembali ke angka 1.
 * 3. Memastikan input karyawan baru dimulai dari NIP SSL001.
 *
 * HAK AKSES / PENGGUNA: Administrator / Maintenance
 * ============================================================================
 */

require_once __DIR__ . '/../config/koneksi.php';

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error() . PHP_EOL);
}

echo "Memulai reset database (mengosongkan tabel karyawan dan transaksi terkait)...\n";

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

$tables = [
    'permintaan_edit_absensi',
    'payroll',
    'lembur',
    'presensi_harian',
    'absensi',
    'karyawan'
];

foreach ($tables as $tbl) {
    $q = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
    if ($q && mysqli_num_rows($q) > 0) {
        if (mysqli_query($conn, "TRUNCATE TABLE `$tbl`")) {
            echo "- Truncated table: $tbl\n";
            mysqli_query($conn, "ALTER TABLE `$tbl` AUTO_INCREMENT = 1");
        } else {
            echo "- Gagal truncate table $tbl: " . mysqli_error($conn) . "\n";
        }
    }
}

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

echo "Reset selesai! NIP karyawan berikutnya akan dimulai dari SSL001.\n";
