<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();

// ── POST: Simpan presensi untuk satu tanggal ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_presensi'])) {
    $tanggal = trim($_POST['tanggal'] ?? '');
    $presensiData = $_POST['presensi'] ?? []; // array [id_karyawan => status]

    if (!$tanggal || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        set_flash('danger', 'Tanggal tidak valid.');
    } elseif (empty($presensiData)) {
        set_flash('warning', 'Tidak ada data presensi yang diinput.');
    } else {
        $tanggalEsc = mysqli_real_escape_string($conn, $tanggal);
        $berhasil = 0;
        $gagal = 0;
        foreach ($presensiData as $idKaryawan => $status) {
            $idKaryawan = (int)$idKaryawan;
            $allowedStatus = ['Hadir', 'Sakit', 'Izin', 'Alpha'];
            if ($idKaryawan < 1 || !in_array($status, $allowedStatus)) continue;
            $statusEsc = mysqli_real_escape_string($conn, $status);
            $sql = "INSERT INTO presensi_harian (id_karyawan, tanggal, status_kehadiran)
                    VALUES ($idKaryawan, '$tanggalEsc', '$statusEsc')
                    ON DUPLICATE KEY UPDATE status_kehadiran = '$statusEsc'";
            if (mysqli_query($conn, $sql)) {
                $berhasil++;
            } else {
                $gagal++;
                app_log('Insert presensi_harian: ' . mysqli_error($conn));
            }
        }
        if ($gagal === 0) {
            set_flash('success', "Presensi tanggal $tanggal berhasil disimpan ($berhasil karyawan).");
        } else {
            set_flash('warning', "Disimpan $berhasil karyawan, $gagal gagal. Cek log untuk detail.");
        }
    }
    // Redirect ke halaman dengan tanggal yang sama
    $redirectTgl = urlencode($_POST['tanggal'] ?? '');
    redirect("master/presensi_harian.php?tanggal=$redirectTgl");
}

// ── GET: Tampilkan form ──────────────────────────────────────────────────────
$tanggalInput = trim($_GET['tanggal'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalInput)) {
    $tanggalInput = date('Y-m-d');
}
$tanggalEsc = mysqli_real_escape_string($conn, $tanggalInput);

// Ambil semua karyawan aktif
$karyawanQuery = mysqli_query($conn, "SELECT k.id_karyawan, k.nip, k.nama_karyawan, j.nama_jabatan
    FROM karyawan k
    JOIN jabatan j ON j.id_jabatan = k.id_jabatan
    WHERE k.status_karyawan IN ('Tetap','Kontrak')
    ORDER BY k.nama_karyawan");

// Ambil presensi yang sudah ada untuk tanggal tersebut
$existingQuery = mysqli_query($conn, "SELECT id_karyawan, status_kehadiran FROM presensi_harian WHERE tanggal='$tanggalEsc'");
$existing = [];
if ($existingQuery) {
    while ($row = mysqli_fetch_assoc($existingQuery)) {
        $existing[(int)$row['id_karyawan']] = $row['status_kehadiran'];
    }
}

// Hitung ringkasan
$ringkasanQuery = mysqli_query($conn, "SELECT status_kehadiran, COUNT(*) total FROM presensi_harian WHERE tanggal='$tanggalEsc' GROUP BY status_kehadiran");
$ringkasan = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpha' => 0];
if ($ringkasanQuery) {
    while ($r = mysqli_fetch_assoc($ringkasanQuery)) {
        $ringkasan[$r['status_kehadiran']] = (int)$r['total'];
    }
}
?>
<div class="card p-4 mb-4">
  <div class="section-header">
    <i class="bi bi-calendar3-week-fill"></i>
    <h2 class="h5">Input Presensi Harian</h2>
  </div>
  <p class="section-desc">Pilih tanggal lalu tentukan status kehadiran setiap karyawan. Klik <strong>Simpan Presensi</strong> untuk menyimpan.</p>

  <form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
      <label class="form-label">Pilih Tanggal</label>
      <input type="date" name="tanggal" class="form-control" value="<?= e($tanggalInput) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Tampilkan</button>
    </div>
    <div class="col-auto">
      <a href="?tanggal=<?= urlencode(date('Y-m-d')) ?>" class="btn btn-outline-secondary">Hari Ini</a>
    </div>
  </form>

  <?php if (!empty($ringkasan) && array_sum($ringkasan) > 0): ?>
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card text-center p-2 border-success"><div class="small text-muted">Hadir</div><div class="fw-bold text-success fs-5"><?= $ringkasan['Hadir'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center p-2 border-warning"><div class="small text-muted">Sakit</div><div class="fw-bold text-warning fs-5"><?= $ringkasan['Sakit'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center p-2 border-info"><div class="small text-muted">Izin</div><div class="fw-bold text-info fs-5"><?= $ringkasan['Izin'] ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center p-2 border-danger"><div class="small text-muted">Alpha</div><div class="fw-bold text-danger fs-5"><?= $ringkasan['Alpha'] ?></div></div></div>
  </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="tanggal" value="<?= e($tanggalInput) ?>">
    <div class="table-responsive">
      <table class="table table-hover align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th style="width:40px">No</th>
            <th>NIP</th>
            <th>Nama Karyawan</th>
            <th>Jabatan</th>
            <th style="width:260px">Status Kehadiran</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 1; if ($karyawanQuery): while ($k = mysqli_fetch_assoc($karyawanQuery)):
            $currentStatus = $existing[(int)$k['id_karyawan']] ?? 'Hadir';
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= e($k['nip']) ?></td>
          <td><?= e($k['nama_karyawan']) ?></td>
          <td><?= e($k['nama_jabatan']) ?></td>
          <td>
            <div class="d-flex gap-2 flex-wrap">
              <?php foreach (['Hadir' => 'success', 'Sakit' => 'warning', 'Izin' => 'info', 'Alpha' => 'danger'] as $st => $color): ?>
              <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio"
                  name="presensi[<?= $k['id_karyawan'] ?>]"
                  id="p_<?= $k['id_karyawan'] ?>_<?= $st ?>"
                  value="<?= $st ?>"
                  <?= $currentStatus === $st ? 'checked' : '' ?> required>
                <label class="form-check-label text-<?= $color ?> fw-semibold" for="p_<?= $k['id_karyawan'] ?>_<?= $st ?>">
                  <?= $st ?>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-3 pt-3 border-top">
      <button name="simpan_presensi" class="btn btn-primary px-5">
        <i class="bi bi-save me-1"></i>Simpan Presensi
      </button>
      <span class="text-muted small ms-3">Tanggal: <strong><?= e(date('d-m-Y', strtotime($tanggalInput))) ?></strong></span>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
