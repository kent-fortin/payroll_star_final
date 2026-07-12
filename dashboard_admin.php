<?php
require_once __DIR__ . '/layout/header.php';
require_admin();

$countKaryawan = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM karyawan'))['total'] ?? 0);
$countJabatan = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM jabatan'))['total'] ?? 0);
$currentMonth = current_month_name();
$currentYear = (int)date('Y');
$monthEsc = mysqli_real_escape_string($conn, $currentMonth);
$countAbsensi = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM absensi WHERE bulan='$monthEsc' AND tahun=$currentYear"))['total'] ?? 0);
$paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total,COALESCE(SUM(total_gaji_bersih),0) nominal FROM payroll WHERE status_pembayaran='Sudah Dibayar'"));
?>
<div class="row g-4 mb-4">
<div class="col-md-3"><div class="card stat-card bg-primary text-white p-4"><div class="small">Jumlah Karyawan</div><div class="metric-number"><?= $countKaryawan ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-success text-white p-4"><div class="small">Jumlah Jabatan</div><div class="metric-number"><?= $countJabatan ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-warning text-dark p-4"><div class="small">Rekap Absensi <?= e($currentMonth) ?></div><div class="metric-number"><?= $countAbsensi ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-dark text-white p-4"><div class="small">Payroll Sudah Dibayar</div><div class="metric-number"><?= (int)($paid['total'] ?? 0) ?></div></div></div>
</div>
<div class="row g-4">
<div class="col-lg-8"><div class="card content-card shadow-sm p-4"><h2 class="h4 mb-3">Alur Sistem</h2>
<div class="row g-3">
<div class="col-md-6"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('master/absensi.php') ?>">1. Catat Rekap Absensi</a></div>
<div class="col-md-6"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('master/lembur.php') ?>">2. Input Data Lembur</a></div>
<div class="col-md-6"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('transaksi/payroll.php') ?>">3. Proses Payroll</a></div>
<div class="col-md-6"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('master/karyawan.php') ?>">Kelola Data Karyawan</a></div>
</div></div></div>
<div class="col-lg-4"><div class="card content-card shadow-sm p-4"><h2 class="h4 mb-3">Rumus Payroll</h2><div class="formula-box small">
<div><strong>Total Lembur</strong> = Jam lembur × <?= rupiah(get_setting($conn,'tarif_lembur_per_jam',15000)) ?></div>
<div class="mt-2"><strong>Potongan Alpha</strong> = Hari alpha × <?= rupiah(get_setting($conn,'potongan_alpha_per_hari',25000)) ?></div>
<div class="mt-2"><strong>Gaji Bersih</strong> = Gaji pokok + total lembur − potongan alpha</div>
</div><div class="mt-3 small text-muted">Absensi yang dikelola merupakan rekap bulanan, bukan presensi harian.</div></div></div>
</div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
