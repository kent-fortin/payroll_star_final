<?php
/**
 * ============================================================================
 * NAMA FILE: index.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Gerbang utama (entry point) aplikasi Payroll Star yang bertugas mengarahkan pengguna.
 *
 * ALUR & FITUR UTAMA:
 * 1. Mengecek apakah pengguna sudah memiliki sesi login yang sah.
 * 2. Jika belum login, otomatis dialihkan ke halaman auth/login.php.
 * 3. Jika sudah login, dialihkan ke dashboard sesuai peran (Admin atau Pimpinan).
 *
 * HAK AKSES / PENGGUNA: Publik / Semua Pengguna
 * ============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';

// [PENCARIAN-FUNGSI: REDIRECT AWAL] Cek status sesi login awal pengunjung
if (!is_logged_in()) {
    redirect('auth/login.php');
// [PENJELASAN LOGIKA]: Pemeriksaan kondisi alternatif (Else-If) jika kondisi sebelumnya tidak terpenuhi
} elseif (is_admin()) {
    redirect('dashboard_admin.php');
// [PENJELASAN LOGIKA]: Menjalankan blok perintah default (Else) karena semua kondisi di atasnya tidak terpenuhi
} else {
    redirect('dashboard_pimpinan.php');
}
