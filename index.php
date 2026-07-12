<?php
/**
 * index.php — Entry point utama aplikasi
 * Redirect ke dashboard sesuai role, atau ke login jika belum masuk.
 */
require_once __DIR__ . '/config/koneksi.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
} elseif (is_admin()) {
    redirect('dashboard_admin.php');
} else {
    redirect('dashboard_pimpinan.php');
}
