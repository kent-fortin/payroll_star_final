<?php
require_once __DIR__ . '/../config/koneksi.php';
require_admin();
set_flash('info','Menu potongan telah dihapus. Potongan alpha dihitung otomatis dari rekap absensi bulanan.');
redirect('master/absensi.php');
