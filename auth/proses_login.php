<?php
require_once __DIR__ . '/../config/koneksi.php';
if (!$conn) {
    set_flash('danger', 'Login gagal. Silakan coba kembali.');
    redirect('auth/login.php');
}
$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
$stmt = mysqli_prepare($conn, 'SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username=? LIMIT 1');
if (!$stmt) {
    app_log('Login prepare failed: ' . mysqli_error($conn));
    set_flash('danger', 'Login gagal. Silakan coba kembali.');
    redirect('auth/login.php');
}
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    set_flash('success', 'Login berhasil.');
    if ($user['role'] === 'admin') {
        redirect('dashboard_admin.php');
    } else {
        redirect('dashboard_pimpinan.php');
    }
}
set_flash('danger', 'Login gagal. Gunakan alternatif Lupa Password apabila diperlukan.');
redirect('auth/login.php');
