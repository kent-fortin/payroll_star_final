<?php
require_once __DIR__ . '/config/koneksi.php';

echo "Memulai migrasi 2...\n";

// 1. Tambah setting total_hari_kerja
$result = mysqli_query($conn, "SELECT id_pengaturan FROM pengaturan_payroll WHERE nama_pengaturan = 'total_hari_kerja'");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "INSERT INTO pengaturan_payroll (nama_pengaturan, nilai, keterangan) VALUES ('total_hari_kerja', 26, 'Standar total hari kerja dalam sebulan')");
    echo "Added setting: total_hari_kerja (26)\n";
}

// 2. Tambah no_ktp dan no_kk ke karyawan
$q = mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE 'no_ktp'");
if (mysqli_num_rows($q) == 0) {
    mysqli_query($conn, "ALTER TABLE karyawan ADD no_ktp VARCHAR(20) NULL, ADD no_kk VARCHAR(20) NULL");
    echo "Added columns no_ktp and no_kk to karyawan\n";
}

// 3. Check lembur insert bug by running a raw insert test
$userId = 1;
$stmt = mysqli_prepare($conn, 'INSERT INTO lembur (id_karyawan,tanggal_lembur,jam_lembur,dibuat_oleh) VALUES (?,?,?,?)');
if ($stmt) {
    $id = 1; $t = '2026-07-16'; $j = 2;
    mysqli_stmt_bind_param($stmt, 'isii', $id, $t, $j, $userId);
    if (!mysqli_stmt_execute($stmt)) {
        echo "Lembur insert error: " . mysqli_error($conn) . "\n";
    } else {
        echo "Lembur insert test success!\n";
        mysqli_query($conn, "DELETE FROM lembur WHERE id_lembur = " . mysqli_insert_id($conn));
    }
} else {
    echo "Lembur prepare error: " . mysqli_error($conn) . "\n";
}

echo "Selesai.\n";
