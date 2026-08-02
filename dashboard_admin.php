<?php
/**
 * ============================================================================
 * NAMA FILE: dashboard_admin.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman beranda utama (dashboard) untuk pengguna dengan hak akses Admin.
 *
 * ALUR & FITUR UTAMA:
 * 1. Menampilkan statistik singkat jumlah karyawan, jabatan, dan payroll.
 * 2. Navigasi alur sistem bergaya step-by-step yang mudah dipahami.
 * 3. Widget tabel Presensi Rekap Harian yang menampilkan kehadiran karyawan hari ini.
 *
 * HAK AKSES / PENGGUNA: Admin
 * ============================================================================
 */

require_once __DIR__ . '/layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan hanya Admin yang dapat mengakses dashboard utama ini.
require_admin();

$countKaryawan = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM karyawan'))['total'] ?? 0);
$countJabatan = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM jabatan'))['total'] ?? 0);
$currentMonth = current_month_name();
$currentYear = (int)date('Y');
$monthEsc = mysqli_real_escape_string($conn, $currentMonth);
$countAbsensi = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM absensi WHERE bulan='$monthEsc' AND tahun=$currentYear"))['total'] ?? 0);
$paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total,COALESCE(SUM(total_gaji_bersih),0) nominal FROM payroll WHERE status_pembayaran='Sudah Dibayar'"));

$today = date('Y-m-d');
// --- SECTION 3: PENGAMBILAN DATA WIDGET PRESENSI HARI INI ---
// Mengambil daftar absensi karyawan pada tanggal hari ini (CURDATE()) untuk tabel rekap harian.
$queryPresensiHariIni = mysqli_query($conn, "SELECT p.*, k.nip, k.nama_karyawan, j.nama_jabatan 
    FROM presensi_harian p 
    JOIN karyawan k ON k.id_karyawan = p.id_karyawan 
    JOIN jabatan j ON j.id_jabatan = k.id_jabatan 
    WHERE p.tanggal = '$today' 
    ORDER BY k.nama_karyawan");

$rejectedEdits = mysqli_query($conn, "SELECT p.*, k.nama_karyawan, a.bulan, a.tahun 
    FROM permintaan_edit_absensi p 
    JOIN absensi a ON a.id_absensi = p.id_absensi 
    JOIN karyawan k ON k.id_karyawan = a.id_karyawan 
    WHERE p.status = 'Ditolak' 
      AND p.id_pengaju = " . (int)($_SESSION['id_user'] ?? 0) . " 
      AND p.tanggal_keputusan >= DATE_SUB(NOW(), INTERVAL 3 DAY)
    ORDER BY p.tanggal_keputusan DESC LIMIT 3");
?>
<?php if ($rejectedEdits && mysqli_num_rows($rejectedEdits) > 0): ?>
<div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between" style="border-left: 5px solid #dc2626; padding-right: 3rem !important;">
  <div>
    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 align-middle text-danger"></i>
    <strong>Perhatian!</strong> Ada pengajuan edit absensi Anda yang <strong>ditolak</strong> oleh Pimpinan. Periksa catatan penolakan.
  </div>
  <a href="<?= url('master/rekap_absensi.php') ?>" class="btn btn-sm btn-danger fw-bold px-3">Lihat Riwayat</a>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
<div class="col-md-3"><div class="card stat-card bg-primary text-white p-4"><div class="small">Jumlah Karyawan</div><div class="metric-number"><?= $countKaryawan ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-success text-white p-4"><div class="small">Jumlah Jabatan</div><div class="metric-number"><?= $countJabatan ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-warning text-dark p-4"><div class="small">Rekap Absensi <?= e($currentMonth) ?></div><div class="metric-number"><?= $countAbsensi ?></div></div></div>
<div class="col-md-3"><div class="card stat-card bg-dark text-white p-4"><div class="small">Payroll Sudah Dibayar</div><div class="metric-number"><?= (int)($paid['total'] ?? 0) ?></div></div></div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-12">
    <div class="card content-card shadow-sm p-4">
      <div class="section-header mb-3">
        <i class="bi bi-diagram-3 text-primary"></i>
        <h2 class="h5 mb-0">Alur Sistem (Step-by-Step)</h2>
      </div>
      <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch text-center">
        <div class="flex-fill">
          <a class="btn btn-outline-primary w-100 py-3 h-100 d-flex align-items-center justify-content-center flex-column" style="border-radius: 12px; font-size: 0.85rem;" href="<?= url('master/presensi_harian.php') ?>">
            <i class="bi bi-1-circle fs-4 mb-2"></i> <span>Input Presensi</span>
          </a>
        </div>
        <div class="d-none d-md-flex align-items-center justify-content-center text-muted">
          <i class="bi bi-chevron-double-right fs-4"></i>
        </div>
        <div class="flex-fill">
          <a class="btn btn-outline-primary w-100 py-3 h-100 d-flex align-items-center justify-content-center flex-column" style="border-radius: 12px; font-size: 0.85rem;" href="<?= url('master/rekap_absensi.php') ?>">
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
      <hr class="mb-4 mt-4">
      <div class="section-header mb-3">
        <i class="bi bi-folder2-open text-primary"></i>
        <h2 class="h5 mb-0">Master Data</h2>
      </div>
      <div class="row g-3">
        <div class="col-md-6"><a class="btn btn-outline-secondary w-100 py-2 text-start px-3" href="<?= url('master/karyawan.php') ?>"><i class="bi bi-people-fill me-2 text-primary"></i>Kelola Data Karyawan</a></div>
        <div class="col-md-6"><a class="btn btn-outline-secondary w-100 py-2 text-start px-3" href="<?= url('master/jabatan.php') ?>"><i class="bi bi-tag-fill me-2 text-success"></i>Kelola Data Jabatan</a></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-12">
    <div class="card content-card shadow-sm p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-header mb-0">
          <i class="bi bi-calendar-check text-primary"></i>
          <h2 class="h5 mb-0">Presensi Rekap Harian (Hari Ini: <?= date('d M Y') ?>)</h2>
        </div>
        <a href="<?= url('master/presensi_harian.php') ?>" class="btn btn-sm btn-primary px-3"><i class="bi bi-plus-circle me-1"></i> Input / Kelola Presensi</a>
      </div>
      <p class="section-desc mb-3">Daftar singkat kehadiran dan absensi karyawan yang dicatat pada hari ini.</p>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0" style="width:100%">
          <thead class="table-light">
            <tr>
              <th style="width: 5%">No</th>
              <th style="width: 25%">NIP & Nama</th>
              <th style="width: 30%">Jabatan</th>
              <th style="width: 25%">Tanggal</th>
              <th style="width: 15%" class="text-center">Status Kehadiran</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($queryPresensiHariIni && mysqli_num_rows($queryPresensiHariIni) > 0): 
              $no = 1;
              while ($pr = mysqli_fetch_assoc($queryPresensiHariIni)): 
                $badgeClass = match ($pr['status_kehadiran']) {
                    'Hadir' => 'bg-success',
                    'Sakit' => 'bg-warning text-dark',
                    'Izin' => 'bg-info text-dark',
                    'Alpha' => 'bg-danger',
                    default => 'bg-secondary'
                };
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><strong><?= e($pr['nip']) ?></strong><br><?= e($pr['nama_karyawan']) ?></td>
              <td><?= e($pr['nama_jabatan']) ?></td>
              <td><?= e($pr['tanggal']) ?></td>
              <td class="text-center"><span class="badge <?= $badgeClass ?> px-3 py-2 rounded-pill"><?= e($pr['status_kehadiran']) ?></span></td>
            </tr>
            <?php 
              endwhile;
            else: 
            ?>
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">
                <i class="bi bi-calendar-x fs-3 d-block mb-2 text-secondary"></i>
                Belum ada data presensi yang diinput untuk hari ini (<?= date('d-m-Y') ?>).
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
