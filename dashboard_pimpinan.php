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

$today = date('Y-m-d');
$todayQuery = "SELECT 
    SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
    SUM(CASE WHEN status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
    SUM(CASE WHEN status_kehadiran = 'Izin' THEN 1 ELSE 0 END) AS izin,
    SUM(CASE WHEN status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) AS alpha
FROM presensi_harian WHERE tanggal = '$today'";
$todayData = mysqli_fetch_assoc(mysqli_query($conn, $todayQuery));
$todayHadir = (int)($todayData['hadir'] ?? 0);
$todaySakit = (int)($todayData['sakit'] ?? 0);
$todayIzin = (int)($todayData['izin'] ?? 0);
$todayAlpha = (int)($todayData['alpha'] ?? 0);
?>
<div class="row g-4 mb-4">
<div class="col-md-3"><div class="card stat-card bg-primary text-white p-4"><div class="small">Jumlah Karyawan</div><div class="metric-number"><?= $countKaryawan ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-warning text-dark p-4"><div class="small">Rekap Absensi <?= e($currentMonth) ?></div><div class="metric-number"><?= $countAbsensi ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-danger text-white p-4"><div class="small">Edit Absensi (Menunggu)</div><div class="metric-number"><?= $countPending ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-dark text-white p-4"><div class="small">Validasi Payroll (Menunggu)</div><div class="metric-number"><?= $countValidasi ?></div></div></div>
</div>
<div class="row g-4">
<div class="col-lg-8"><div class="card content-card shadow-sm p-4 h-100"><h2 class="h4 mb-3">Daftar Tugas & Approval</h2>
<div class="row g-3 h-100 pb-3">
<div class="col-md-4">
  <a class="btn btn-outline-primary w-100 py-3 position-relative d-flex flex-column align-items-center justify-content-center h-100" style="border-radius: 12px;" href="<?= url('approval/absensi.php') ?>">
    <i class="bi bi-check2-circle fs-4 mb-2"></i>
    <span style="font-size: 0.9rem;">Edit Absensi</span>
    <?php if ($countPending > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm" style="font-size: 0.75rem; padding: 0.4em 0.65em;">
        <?= $countPending ?>
        <span class="visually-hidden">menunggu persetujuan</span>
      </span>
    <?php endif; ?>
  </a>
</div>
<div class="col-md-4">
  <a class="btn btn-outline-primary w-100 py-3 position-relative d-flex flex-column align-items-center justify-content-center h-100" style="border-radius: 12px;" href="<?= url('approval/validasi_payroll.php') ?>">
    <i class="bi bi-check2-square fs-4 mb-2"></i>
    <span style="font-size: 0.9rem;">Validasi Payroll</span>
    <?php if ($countValidasi > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm" style="font-size: 0.75rem; padding: 0.4em 0.65em;">
        <?= $countValidasi ?>
        <span class="visually-hidden">menunggu validasi</span>
      </span>
    <?php endif; ?>
  </a>
</div>
<div class="col-md-4">
  <a class="btn btn-outline-secondary w-100 py-3 position-relative d-flex flex-column align-items-center justify-content-center h-100" style="border-radius: 12px;" href="<?= url('laporan/laporan.php') ?>">
    <i class="bi bi-file-earmark-bar-graph fs-4 mb-2"></i>
    <span style="font-size: 0.9rem;">Lihat Laporan</span>
  </a>
</div>
</div></div></div>
<div class="col-lg-4"><div class="card content-card shadow-sm p-4 h-100"><h2 class="h4 mb-3">Presensi Hari Ini</h2><div class="formula-box small flex-grow-1">
<div class="d-flex justify-content-between mb-2"><strong>Hadir</strong> <span class="badge bg-success"><?= $todayHadir ?></span></div>
<div class="d-flex justify-content-between mb-2"><strong>Sakit</strong> <span class="badge bg-warning text-dark"><?= $todaySakit ?></span></div>
<div class="d-flex justify-content-between mb-2"><strong>Izin</strong> <span class="badge bg-info text-dark"><?= $todayIzin ?></span></div>
<div class="d-flex justify-content-between"><strong>Alpha</strong> <span class="badge bg-danger"><?= $todayAlpha ?></span></div>
</div><div class="mt-3 small text-muted"><i class="bi bi-clock me-1"></i> Data presensi harian per <?= date('d M Y') ?>.</div></div></div>
</div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
