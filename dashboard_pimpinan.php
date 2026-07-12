<?php
require_once __DIR__ . '/layout/header.php';
require_pimpinan();

$countKaryawan = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM karyawan'))['total'] ?? 0);
$countJabatan = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM jabatan'))['total'] ?? 0);
$currentMonth = current_month_name();
$currentYear = (int)date('Y');
$monthEsc = mysqli_real_escape_string($conn, $currentMonth);
$countAbsensi = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM absensi WHERE bulan='$monthEsc' AND tahun=$currentYear"))['total'] ?? 0);
$countPending = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM permintaan_edit_absensi WHERE status='Menunggu'"))['total'] ?? 0);
$countValidasi = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM payroll WHERE status_validasi='Menunggu'"))['total'] ?? 0);
?>
<div class="row g-4 mb-4">
<div class="col-md-3"><div class="card stat-card bg-primary text-white p-4"><div class="small">Jumlah Karyawan</div><div class="metric-number"><?= $countKaryawan ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-warning text-dark p-4"><div class="small">Rekap Absensi <?= e($currentMonth) ?></div><div class="metric-number"><?= $countAbsensi ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-danger text-white p-4"><div class="small">Edit Absensi (Menunggu)</div><div class="metric-number"><?= $countPending ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-dark text-white p-4"><div class="small">Validasi Payroll (Menunggu)</div><div class="metric-number"><?= $countValidasi ?></div></div></div>
</div>
<div class="row g-4">
<div class="col-lg-8"><div class="card content-card shadow-sm p-4"><h2 class="h4 mb-3">Akses Menu</h2>
<div class="row g-3">
<div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('approval/absensi.php') ?>">Persetujuan Edit Absensi</a></div>
<div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('approval/validasi_payroll.php') ?>">Validasi Payroll</a></div>
<div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="<?= url('laporan/laporan.php') ?>">Lihat Laporan Gaji</a></div>
</div></div></div>
<div class="col-lg-4"><div class="card content-card shadow-sm p-4"><h2 class="h4 mb-3">Rumus Payroll</h2><div class="formula-box small">
<div><strong>Total Lembur</strong> = Jam lembur × <?= rupiah(get_setting($conn,'tarif_lembur_per_jam',15000)) ?></div>
<div class="mt-2"><strong>Potongan Alpha</strong> = Hari alpha × <?= rupiah(get_setting($conn,'potongan_alpha_per_hari',25000)) ?></div>
<div class="mt-2"><strong>Gaji Bersih</strong> = Gaji pokok + total lembur − potongan alpha</div>
</div><div class="mt-3 small text-muted">Absensi yang dikelola merupakan rekap bulanan, bukan presensi harian.</div></div></div>
</div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
