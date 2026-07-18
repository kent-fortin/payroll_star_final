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
<div class="col-lg-8"><div class="card content-card shadow-sm p-4 h-100"><h2 class="h4 mb-3">Alur Sistem (Step-by-Step)</h2>
<div class="d-flex flex-column flex-md-row gap-2 align-items-stretch text-center mb-4">
  <div class="flex-fill">
    <a class="btn btn-outline-primary w-100 py-3 h-100 d-flex align-items-center justify-content-center flex-column" style="border-radius: 12px; font-size: 0.85rem;" href="<?= url('master/presensi_harian.php') ?>">
      <i class="bi bi-1-circle fs-4 mb-2"></i> <span>Input Presensi</span>
    </a>
  </div>
  <div class="d-none d-md-flex align-items-center justify-content-center text-muted">
    <i class="bi bi-chevron-double-right fs-4"></i>
  </div>
  <div class="flex-fill">
    <a class="btn btn-outline-primary w-100 py-3 h-100 d-flex align-items-center justify-content-center flex-column" style="border-radius: 12px; font-size: 0.85rem;" href="<?= url('master/absensi.php') ?>">
      <i class="bi bi-2-circle fs-4 mb-2"></i> <span>Rekap Absensi</span>
    </a>
  </div>
  <div class="d-none d-md-flex align-items-center justify-content-center text-muted">
    <i class="bi bi-chevron-double-right fs-4"></i>
  </div>
  <div class="flex-fill">
    <a class="btn btn-outline-primary w-100 py-3 h-100 d-flex align-items-center justify-content-center flex-column" style="border-radius: 12px; font-size: 0.85rem;" href="<?= url('master/lembur.php') ?>">
      <i class="bi bi-3-circle fs-4 mb-2"></i> <span>Data Lembur</span>
    </a>
  </div>
  <div class="d-none d-md-flex align-items-center justify-content-center text-muted">
    <i class="bi bi-chevron-double-right fs-4"></i>
  </div>
  <div class="flex-fill">
    <a class="btn btn-outline-primary w-100 py-3 h-100 d-flex align-items-center justify-content-center flex-column" style="border-radius: 12px; font-size: 0.85rem;" href="<?= url('transaksi/payroll.php') ?>">
      <i class="bi bi-4-circle fs-4 mb-2"></i> <span>Proses Payroll</span>
    </a>
  </div>
</div>
<hr class="mb-4 mt-2">
<h2 class="h5 mb-3">Master Data</h2>
<div class="row g-3">
<div class="col-md-6"><a class="btn btn-outline-secondary w-100 py-2 text-start px-3" href="<?= url('master/karyawan.php') ?>"><i class="bi bi-people-fill me-2 text-primary"></i>Kelola Data Karyawan</a></div>
<div class="col-md-6"><a class="btn btn-outline-secondary w-100 py-2 text-start px-3" href="<?= url('master/jabatan.php') ?>"><i class="bi bi-tag-fill me-2 text-success"></i>Kelola Data Jabatan</a></div>
</div>
</div></div>
<div class="col-lg-4"><div class="card content-card shadow-sm p-4 h-100"><h2 class="h4 mb-3">Rumus Payroll</h2><div class="formula-box small flex-grow-1">
<div><strong>Total Lembur</strong> = Jam lembur × <?= rupiah(get_setting($conn,'tarif_lembur_per_jam',15000)) ?></div>
<div class="mt-3"><strong>Potongan Alpha</strong> = Hari alpha × <?= rupiah(get_setting($conn,'potongan_alpha_per_hari',25000)) ?></div>
<div class="mt-3"><strong>Gaji Bersih</strong> = Gaji pokok + total lembur − potongan alpha</div>
</div><div class="mt-4 small text-muted"><i class="bi bi-info-circle me-1"></i>Pastikan master data karyawan dan jabatan up-to-date sebelum Anda memulai proses hitung Payroll.</div></div></div>
</div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
