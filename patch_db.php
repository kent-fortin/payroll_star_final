<?php
require_once __DIR__ . '/config/koneksi.php';

$queries = [
    "ALTER TABLE jabatan ADD status_jabatan ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif'",
    "ALTER TABLE karyawan MODIFY status_karyawan ENUM('Tetap','Kontrak','Resign') NOT NULL"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Success: $sql\n";
    } else {
        echo "Error on $sql: " . mysqli_error($conn) . "\n";
    }
}
