<?php
/**
 * ============================================================================
 * NAMA FILE: logout.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Menangani proses keluar (logout) dari aplikasi dan mengakhiri sesi pengguna.
 *
 * ALUR & FITUR UTAMA:
 * 1. Menghapus seluruh data session (session_unset & session_destroy).
 * 2. Mengarahkan kembali pengguna ke halaman login dengan pesan sukses keluar.
 *
 * HAK AKSES / PENGGUNA: Admin & Pimpinan
 * ============================================================================
 */

require_once __DIR__ . '/../config/koneksi.php';

// --- SECTION 1: PENGHAPUSAN DAN PENGHANCURAN SESI LOGIN ---
// [PENCARIAN-FUNGSI: LOGOUT] Menghancurkan seluruh sesi login aktif dan mengalihkan user kembali ke form login
session_start();
session_unset();
session_destroy();

session_start();
set_flash('success', 'Anda telah logout.');
redirect('auth/login.php');
