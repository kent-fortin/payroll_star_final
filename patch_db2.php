<?php
require_once __DIR__ . '/config/koneksi.php';

$queries = [
    "ALTER TABLE payroll ADD total_tunjangan DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_lembur"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Success: $sql\n";
    } else {
        echo "Error on $sql: " . mysqli_error($conn) . "\n";
    }
}
