<?php
require 'config/koneksi.php';
$res = mysqli_query($conn, 'SELECT id_payroll, bulan, tahun, status_pembayaran, total_tunjangan FROM payroll');
while($r = mysqli_fetch_assoc($res)) print_r($r);
