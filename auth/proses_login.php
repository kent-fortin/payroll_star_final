<?php
/**
 * ============================================================================
 * NAMA FILE: proses_login.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Skrip pemroses verifikasi data kredensial login yang dikirimkan dari form login.
 *
 * ALUR & FITUR UTAMA:
 * 1. Mencocokkan username dan password di database tabel users.
 * 2. Mengatur sesi (session) berdasarkan hak akses (role: admin atau pimpinan).
 * 3. Mengalihkan ke dashboard admin atau pimpinan sesuai perannya.
 *
 * HAK AKSES / PENGGUNA: Publik / Tamu (Guest)
 * ============================================================================
 */

require_once __DIR__ . '/../config/koneksi.php';
if (!$conn) {
    set_flash('danger', 'Login gagal. Silakan coba kembali.');
    redirect('auth/login.php');
}
// [PENCARIAN-FUNGSI: AMBIL DATA LOGIN] Menerima input dari form login
$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

// [PENCARIAN-FUNGSI: CEK USERNAME] Mencari apakah username terdaftar di database
$stmt = mysqli_prepare($conn, 'SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username=? LIMIT 1');
if (!$stmt) {
    app_log('Login prepare failed: ' . mysqli_error($conn));
    set_flash('danger', 'Login gagal. Silakan coba kembali.');
    redirect('auth/login.php');
}
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// [PENCARIAN-FUNGSI: VERIFIKASI PASSWORD] Mengecek kecocokan password yang diinput dengan hash di database
if ($user && password_verify($password, $user['password'])) {
    // [PENCARIAN-FUNGSI: BUAT SESSION] Jika cocok, simpan data user ke dalam sesi (session) browser
    session_regenerate_id(true);
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    set_flash('success', 'Login berhasil.');

    // [PENCARIAN-FUNGSI: REDIRECT ROLE] Melempar user ke halaman dashboard sesuai jabatan (admin / pimpinan)
    if ($user['role'] === 'admin') {
        redirect('dashboard_admin.php');
    } else {
        redirect('dashboard_pimpinan.php');
    }
}
set_flash('danger', 'Login gagal. Username atau password yang Anda masukkan salah.');
redirect('auth/login.php');
