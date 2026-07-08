<?php
require_once __DIR__ . '/../config/koneksi.php';
require_admin();
set_flash('info','Menu slip gaji telah dihapus. Gunakan tombol Cetak Rincian pada Proses Payroll.');
redirect('transaksi/payroll.php');
