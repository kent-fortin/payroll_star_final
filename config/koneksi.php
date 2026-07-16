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
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_payroll_star_samudera';

// --- VERSI LIVE ---
/*
$host = 'sql312.infinityfree.com';
$user = 'if0_42362934';
$pass = 'fFQbSZ02B5U';
$db   = 'if0_42362934_db_payroll_star_samudera';
*/

$conn = mysqli_connect($host, $user, $pass, $db);
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
} else {
    die(mysqli_connect_error());
}
