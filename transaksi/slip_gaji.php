<?php
/**
 * ============================================================================
 * NAMA FILE: slip_gaji.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman daftar slip gaji karyawan yang telah diproses dan disetujui.
 *
 * ALUR & FITUR UTAMA:
 * 1. Menampilkan status validasi dan status pembayaran gaji karyawan.
 * 2. Filter periode bulan dan tahun untuk pencarian slip gaji.
 * 3. Tautan cepat untuk mencetak rincian slip gaji setiap karyawan.
 *
 * HAK AKSES / PENGGUNA: Admin & Pimpinan
 * ============================================================================
 */

require_once __DIR__ . '/../config/koneksi.php';
require_admin();
set_flash('info','Menu slip gaji telah dihapus. Gunakan tombol Cetak Rincian pada Proses Payroll.');
redirect('transaksi/payroll.php');
