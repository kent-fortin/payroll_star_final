<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();

// ── POST: Hitung otomatis rekap dari presensi_harian ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hitung_rekap'])) {
    $bulan  = trim($_POST['bulan']  ?? '');
    $tahun  = (int)($_POST['tahun'] ?? date('Y'));
    $bulanNomor = bulan_nomor($bulan);

    if ($bulanNomor === 0 || $tahun < 2000) {
        set_flash('danger', 'Pilih bulan dan tahun yang valid.');
    } else {
        // Hitung rekap dari presensi_harian untuk bulan & tahun yang dipilih
        $userId = (int)$_SESSION['id_user'];
        $sql = "SELECT
                    k.id_karyawan,
                    SUM(CASE WHEN ph.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
                    SUM(CASE WHEN ph.status_kehadiran = 'Sakit'  THEN 1 ELSE 0 END) AS sakit,
                    SUM(CASE WHEN ph.status_kehadiran = 'Izin'   THEN 1 ELSE 0 END) AS izin,
                    SUM(CASE WHEN ph.status_kehadiran = 'Alpha'  THEN 1 ELSE 0 END) AS alpha
                FROM karyawan k
                INNER JOIN presensi_harian ph ON ph.id_karyawan = k.id_karyawan
                WHERE MONTH(ph.tanggal) = $bulanNomor AND YEAR(ph.tanggal) = $tahun
                  AND k.status_karyawan IN ('Tetap','Kontrak')
                GROUP BY k.id_karyawan";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            set_flash('danger', 'Query rekap gagal: ' . mysqli_error($conn));
            app_log('Rekap absensi query: ' . mysqli_error($conn));
        } else {
            $bulanEsc = mysqli_real_escape_string($conn, $bulan);
            $berhasil = 0; $diperbarui = 0; $gagal = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $idK    = (int)$row['id_karyawan'];
                $hadir  = (int)$row['hadir'];
                $sakit  = (int)$row['sakit'];
                $izin   = (int)$row['izin'];
                $alpha  = (int)$row['alpha'];

                // Cek apakah rekap bulan ini sudah ada
                $cekRes = mysqli_query($conn, "SELECT id_absensi FROM absensi
                    WHERE id_karyawan=$idK AND bulan='$bulanEsc' AND tahun=$tahun LIMIT 1");
                $cek = $cekRes ? mysqli_fetch_assoc($cekRes) : null;

                if ($cek) {
                    // Update rekap yang sudah ada
                    $ok = mysqli_query($conn, "UPDATE absensi
                        SET hadir=$hadir, sakit=$sakit, izin=$izin, alpha=$alpha,
                            diperbarui_pada=NOW()
                        WHERE id_absensi={$cek['id_absensi']}");
                    $ok ? $diperbarui++ : $gagal++;
                } else {
                    // Insert rekap baru
                    $stmt = mysqli_prepare($conn,
                        'INSERT INTO absensi (id_karyawan,bulan,tahun,hadir,sakit,izin,alpha,dibuat_oleh)
                         VALUES (?,?,?,?,?,?,?,?)');
                    $ok = false;
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'isiiiiii',
                            $idK, $bulan, $tahun, $hadir, $sakit, $izin, $alpha, $userId);
                        $ok = mysqli_stmt_execute($stmt);
                    }
                    $ok ? $berhasil++ : $gagal++;
                    if (!$ok) app_log('Insert rekap absensi: ' . mysqli_error($conn));
                }
            }
            if ($gagal === 0) {
                set_flash('success', "Rekap $bulan $tahun berhasil! Baru: $berhasil, Diperbarui: $diperbarui karyawan.");
            } else {
                set_flash('warning', "Rekap selesai. Baru: $berhasil, Diperbarui: $diperbarui, Gagal: $gagal.");
            }
        }
    }
    redirect('master/absensi.php');
}

// ── GET: Tampilkan halaman ───────────────────────────────────────────────────
$data = mysqli_query($conn, "SELECT a.*,k.nip,k.nama_karyawan,
    (SELECT p.status FROM permintaan_edit_absensi p WHERE p.id_absensi=a.id_absensi ORDER BY p.id_permintaan DESC LIMIT 1) status_edit
    FROM absensi a JOIN karyawan k ON k.id_karyawan=a.id_karyawan
    ORDER BY a.tahun DESC,FIELD(a.bulan,'Desember','November','Oktober','September','Agustus','Juli','Juni','Mei','April','Maret','Februari','Januari'),k.nama_karyawan");
$tarifAlpha = get_setting($conn, 'potongan_alpha_per_hari', 25000);
?>
<div class="card p-4 mb-4">
<div class="section-header">
  <i class="bi bi-calendar-check"></i>
  <h2 class="h5">Rekap Absensi Bulanan (Otomatis)</h2>
</div>
<p class="section-desc">Pilih periode lalu klik <strong>Hitung Otomatis</strong>. Sistem akan merekap data dari tabel <strong>Presensi Harian</strong> untuk bulan dan tahun yang dipilih.</p>
<div class="formula-box mb-3 small">
  <i class="bi bi-info-circle me-1"></i>
  <strong>Catatan:</strong> Pastikan data <strong>Presensi Harian</strong> sudah diinput terlebih dahulu sebelum membuat rekap bulanan.
  Potongan alpha = hari alpha × <?= rupiah($tarifAlpha) ?>.
</div>
<form method="post" class="row g-3 align-items-end" onsubmit="return confirm('Hitung dan simpan rekap absensi dari data presensi harian untuk periode yang dipilih?')">
  <div class="col-md-3">
    <label class="form-label">Bulan</label>
    <select name="bulan" class="form-select" required>
      <?= bulan_options(current_month_name()) ?>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Tahun</label>
    <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>" min="2000" required>
  </div>
  <div class="col-md-3">
    <button name="hitung_rekap" class="btn btn-primary px-4">
      <i class="bi bi-calculator me-1"></i>Hitung Otomatis
    </button>
  </div>
</form>
</div>

<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Rekap Absensi</h2>
</div>
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>NIP</th><th>Nama</th><th>Periode</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpha</th><th>Potongan Alpha</th><th>Status Edit</th></tr></thead><tbody>
<?php $no=1; if($data): while($row=mysqli_fetch_assoc($data)): ?>
<tr>
  <td><?= $no++ ?></td>
  <td><?= e($row['nip']) ?></td>
  <td><?= e($row['nama_karyawan']) ?></td>
  <td><?= e($row['bulan'].' '.$row['tahun']) ?></td>
  <td><?= $row['hadir'] ?></td>
  <td><?= $row['sakit'] ?></td>
  <td><?= $row['izin'] ?></td>
  <td><?= $row['alpha'] ?></td>
  <td><?= rupiah($row['alpha'] * $tarifAlpha) ?></td>
  <td><?= $row['status_edit'] ? status_badge($row['status_edit']) : '<span class="text-muted">-</span>' ?></td>
</tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
