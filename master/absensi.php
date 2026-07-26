<?php
/**
 * ============================================================================
 * NAMA FILE: absensi.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman pengelola data rekapitulasi absensi bulanan karyawan (Hadir, Sakit, Izin, Alpha).
 *
 * ALUR & FITUR UTAMA:
 * 1. Kalkulasi otomatis rekap bulanan berdasarkan data presensi harian.
 * 2. Fitur pengajuan edit rekap absensi yang mengalihkan persetujuan ke Pimpinan.
 * 3. Tabel Riwayat Pengajuan Edit yang menampilkan status dan catatan penolakan Pimpinan.
 *
 * HAK AKSES / PENGGUNA: Admin
 * ============================================================================
 */

require_once __DIR__ . '/../layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan pengguna adalah Admin sebelum mengelola rekap absensi.
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
                    $sqlInsert = "INSERT INTO absensi (id_karyawan, bulan, tahun, hadir, sakit, izin, alpha, dibuat_oleh)
                                  VALUES ($idK, '$bulanEsc', $tahun, $hadir, $sakit, $izin, $alpha, $userId)";
                    $ok = mysqli_query($conn, $sqlInsert);
                    $ok ? $berhasil++ : $gagal++;
                    if (!$ok) app_log('Insert rekap absensi: ' . mysqli_error($conn));
                }
            }
            
            if ($berhasil === 0 && $diperbarui === 0 && $gagal === 0) {
                set_flash('warning', "Tidak ada data presensi harian pada $bulan $tahun untuk direkap.");
            } elseif ($gagal === 0) {
                set_flash('success', "Rekap $bulan $tahun berhasil! Baru: $berhasil, Diperbarui: $diperbarui karyawan.");
            } else {
                set_flash('warning', "Rekap selesai. Baru: $berhasil, Diperbarui: $diperbarui, Gagal: $gagal.");
            }
        }
    }
    redirect('master/absensi.php');
}

// ── POST: Ajukan Edit Rekap Absensi ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_edit_absensi'])) {
    $id_absensi = (int)$_POST['id_absensi'];
    $hadir_baru = (int)$_POST['hadir'];
    $sakit_baru = (int)$_POST['sakit'];
    $izin_baru = (int)$_POST['izin'];
    $alpha_baru = (int)$_POST['alpha'];
    $alasan = trim($_POST['alasan']);
    $userId = (int)$_SESSION['id_user'];
    
    // Cek apakah sedang ada pengajuan menunggu
    $cekPending = mysqli_query($conn, "SELECT id_permintaan FROM permintaan_edit_absensi WHERE id_absensi = $id_absensi AND status = 'Menunggu'");
    if (mysqli_num_rows($cekPending) > 0) {
        set_flash('warning', 'Masih ada pengajuan edit yang menunggu persetujuan Pimpinan.');
    } else {
        // Ambil data lama
        $resLama = mysqli_query($conn, "SELECT hadir, sakit, izin, alpha FROM absensi WHERE id_absensi = $id_absensi");
        $dataLama = $resLama ? mysqli_fetch_assoc($resLama) : [];
        $jsonLama = mysqli_real_escape_string($conn, json_encode($dataLama));
        $alasanEsc = mysqli_real_escape_string($conn, $alasan);
        
        $sql = "INSERT INTO permintaan_edit_absensi (id_absensi, hadir_baru, sakit_baru, izin_baru, alpha_baru, alasan_perubahan, data_lama, id_pengaju, status) 
                VALUES ($id_absensi, $hadir_baru, $sakit_baru, $izin_baru, $alpha_baru, '$alasanEsc', '$jsonLama', $userId, 'Menunggu')";
                
        if (mysqli_query($conn, $sql)) {
            set_flash('success', 'Pengajuan edit absensi berhasil dikirim ke Pimpinan.');
        } else {
            set_flash('danger', 'Gagal mengajukan edit absensi: ' . mysqli_error($conn));
        }
    }
    redirect('master/absensi.php');
}

// ── GET: Tampilkan halaman ───────────────────────────────────────────────────
$data = mysqli_query($conn, "SELECT a.*,k.nip,k.nama_karyawan,
    (SELECT p.status FROM permintaan_edit_absensi p WHERE p.id_absensi=a.id_absensi ORDER BY p.id_permintaan DESC LIMIT 1) status_edit,
    (SELECT p.catatan_pimpinan FROM permintaan_edit_absensi p WHERE p.id_absensi=a.id_absensi ORDER BY p.id_permintaan DESC LIMIT 1) catatan_pimpinan
    FROM absensi a JOIN karyawan k ON k.id_karyawan=a.id_karyawan
    ORDER BY a.tahun DESC,FIELD(a.bulan,'Desember','November','Oktober','September','Agustus','Juli','Juni','Mei','April','Maret','Februari','Januari'),k.nama_karyawan");
$tarifAlpha = get_setting($conn, 'potongan_alpha_per_hari', 25000);
// --- SECTION 4: PENGAMBILAN DATA RIWAYAT PENGAJUAN EDIT ---
// Mengambil daftar riwayat pengajuan edit absensi beserta status dan catatan penolakan dari Pimpinan.
$historyEdit = mysqli_query($conn, "SELECT p.*, a.bulan, a.tahun, k.nip, k.nama_karyawan, u.nama_lengkap as penyetuju 
    FROM permintaan_edit_absensi p 
    JOIN absensi a ON a.id_absensi = p.id_absensi 
    JOIN karyawan k ON k.id_karyawan = a.id_karyawan 
    LEFT JOIN users u ON u.id_user = p.id_penyetuju 
    ORDER BY p.tanggal_pengajuan DESC");
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
<form method="post" id="formRekap" class="row g-3 align-items-end">
  <input type="hidden" name="hitung_rekap" value="1">
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
    <button type="button" class="btn btn-primary px-4" onclick="konfirmasiRekap()">
      <i class="bi bi-calculator me-1"></i>Hitung Otomatis
    </button>
  </div>
</form>

<script>
function konfirmasiRekap() {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Hitung dan simpan rekap absensi dari data presensi harian untuk periode yang dipilih?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Lanjutkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formRekap').submit();
        }
    });
}
</script>
</div>

<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Rekap Absensi</h2>
</div>
<div class="table-responsive">
  <table class="table table-hover dt-table align-middle" style="width:100%">
    <thead>
      <tr>
        <th style="width: 5%">No</th>
        <th style="width: 12%">NIP</th>
        <th style="width: 20%">Karyawan & Periode</th>
        <th style="width: 25%">Rekap Kehadiran (H / S / I / A)</th>
        <th style="width: 14%">Potongan Alpha</th>
        <th style="width: 12%">Status Edit</th>
        <th style="width: 12%" class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php $no=1; if($data): while($row=mysqli_fetch_assoc($data)): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($row['nip']) ?></span></td>
        <td>
          <div class="fw-bold fs-6 text-dark"><?= e($row['nama_karyawan']) ?></div>
          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 mt-1"><i class="bi bi-calendar3 me-1"></i><?= e($row['bulan'].' '.$row['tahun']) ?></span>
        </td>
        <td>
          <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Hadir: <strong><?= $row['hadir'] ?></strong></span>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="bi bi-plus-circle me-1"></i>Sakit: <strong><?= $row['sakit'] ?></strong></span>
            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-info-circle me-1"></i>Izin: <strong><?= $row['izin'] ?></strong></span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Alpha: <strong><?= $row['alpha'] ?></strong></span>
          </div>
        </td>
        <td>
          <?php $pot = $row['alpha'] * $tarifAlpha; ?>
          <div class="<?= $pot > 0 ? 'text-danger fw-bold fs-6' : 'text-success fw-semibold' ?>"><?= rupiah($pot) ?></div>
          <div class="text-muted" style="font-size: 0.75rem;">(<?= $row['alpha'] ?> hari × <?= rupiah($tarifAlpha) ?>)</div>
        </td>
        <td>
          <?= $row['status_edit'] ? status_badge($row['status_edit']) : '<span class="text-muted small">-</span>' ?>
          <?php if (!empty($row['catatan_pimpinan'])): ?>
            <div class="mt-2 p-2 bg-danger-subtle text-danger rounded border border-danger-subtle small fw-semibold" style="max-width: 220px; word-wrap: break-word; white-space: normal; font-size: 0.75rem;">
              <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($row['catatan_pimpinan']) ?>
            </div>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if($row['status_edit'] === 'Menunggu'): ?>
            <button class="btn btn-sm btn-outline-secondary w-100 py-2 fw-semibold" disabled title="Menunggu Persetujuan Pimpinan"><i class="bi bi-hourglass-split me-1"></i> Menunggu</button>
          <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-primary w-100 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id_absensi'] ?>" title="Ajukan Edit">
              <i class="bi bi-pencil-square me-1"></i> Ajukan Edit
            </button>
            
            <!-- Modal Ajukan Edit -->
            <div class="modal fade" id="editModal<?= $row['id_absensi'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form method="post" class="modal-content text-start form-ajukan-edit shadow-lg border-0" style="border-radius: 14px;">
                  <input type="hidden" name="ajukan_edit_absensi" value="1">
                  <input type="hidden" name="id_absensi" value="<?= $row['id_absensi'] ?>">
                  <div class="modal-header bg-light border-bottom py-3">
                    <h5 class="modal-title fs-6 fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Ajukan Edit Absensi - <?= e($row['nama_karyawan']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-secondary-subtle rounded small text-secondary">
                      <span><strong>NIP:</strong> <?= e($row['nip']) ?></span>
                      <span><strong>Periode:</strong> <?= e($row['bulan'] . ' ' . $row['tahun']) ?></span>
                    </div>
                    
                    <div class="alert alert-info py-2 small mb-4 d-flex align-items-center">
                      <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                      <div>Perubahan ini akan dikirim ke <strong>Pimpinan</strong> untuk diproses dan disetujui.</div>
                    </div>

                    <label class="form-label fw-bold small text-muted mb-2">USULAN JUMLAH KEHADIRAN (HARI)</label>
                    <div class="row g-3 mb-4">
                      <div class="col-6">
                        <label class="form-label small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Hadir</label>
                        <input type="number" name="hadir" class="form-control fw-bold" value="<?= $row['hadir'] ?>" min="0" required>
                      </div>
                      <div class="col-6">
                        <label class="form-label small text-warning fw-semibold"><i class="bi bi-plus-circle me-1"></i>Sakit</label>
                        <input type="number" name="sakit" class="form-control fw-bold" value="<?= $row['sakit'] ?>" min="0" required>
                      </div>
                      <div class="col-6">
                        <label class="form-label small text-info fw-semibold"><i class="bi bi-info-circle me-1"></i>Izin</label>
                        <input type="number" name="izin" class="form-control fw-bold" value="<?= $row['izin'] ?>" min="0" required>
                      </div>
                      <div class="col-6">
                        <label class="form-label small text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Alpha</label>
                        <input type="number" name="alpha" class="form-control fw-bold" value="<?= $row['alpha'] ?>" min="0" required>
                      </div>
                    </div>
                    
                    <div class="mb-2">
                      <label class="form-label fw-bold small text-dark">Alasan Perubahan <span class="text-danger">*</span></label>
                      <textarea name="alasan" class="form-control" rows="3" required placeholder="Contoh: Koreksi salah input hari alpha karena karyawan ternyata izin cuti tahunan..."></textarea>
                      <div class="form-text small">Jelaskan alasan secara spesifik agar mudah disetujui Pimpinan.</div>
                    </div>
                  </div>
                  <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold"><i class="bi bi-send me-1"></i>Kirim Pengajuan</button>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</div>
</div>

<div class="card p-4 mt-4">
<div class="section-header">
  <i class="bi bi-clock-history"></i>
  <h2 class="h5">Riwayat Pengajuan Edit Absensi</h2>
</div>
<p class="section-desc mb-4">Daftar seluruh riwayat pengajuan edit absensi kepada Pimpinan beserta status dan catatan keputusannya.</p>
<div class="table-responsive">
  <table class="table table-hover dt-table align-middle" style="width:100%">
    <thead>
      <tr>
        <th style="width: 5%">No</th>
        <th style="width: 12%">NIP</th>
        <th style="width: 18%">Karyawan & Periode</th>
        <th style="width: 25%">Usulan Perubahan</th>
        <th style="width: 20%">Alasan & Tanggal</th>
        <th style="width: 20%">Status & Catatan Pimpinan</th>
      </tr>
    </thead>
    <tbody>
    <?php 
    if ($historyEdit && mysqli_num_rows($historyEdit) > 0): 
      $noHist = 1;
      while ($h = mysqli_fetch_assoc($historyEdit)): 
        $badge = match($h['status']) {
            'Disetujui' => 'bg-success',
            'Ditolak' => 'bg-danger',
            default => 'bg-warning text-dark'
        };
    ?>
      <tr>
        <td><?= $noHist++ ?></td>
        <td><span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($h['nip']) ?></span></td>
        <td>
          <div class="fw-bold fs-6 text-dark"><?= e($h['nama_karyawan']) ?></div>
          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 mt-1"><i class="bi bi-calendar3 me-1"></i><?= e($h['bulan'].' '.$h['tahun']) ?></span>
        </td>
        <td>
          <div class="d-flex flex-wrap gap-1" style="max-width: 200px;">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">H: <strong><?= $h['hadir_baru'] ?></strong></span>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">S: <strong><?= $h['sakit_baru'] ?></strong></span>
            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">I: <strong><?= $h['izin_baru'] ?></strong></span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">A: <strong><?= $h['alpha_baru'] ?></strong></span>
          </div>
        </td>
        <td>
          <div class="p-2 bg-light rounded border-start border-primary border-3 small mb-1 fst-italic" style="max-width: 260px; word-wrap: break-word; white-space: normal;">
            "<?= e($h['alasan_perubahan']) ?>"
          </div>
          <div class="small text-muted" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= e($h['tanggal_pengajuan']) ?></div>
        </td>
        <td>
          <?= status_badge($h['status']) ?>
          <?php if (!empty($h['catatan_pimpinan'])): ?>
            <div class="mt-2 p-2 <?= $h['status']==='Ditolak'?'bg-danger-subtle text-danger border border-danger-subtle':'bg-light text-dark border' ?> rounded small fw-semibold" style="max-width: 220px; white-space: normal !important; word-break: break-word; font-size: 0.75rem;">
              <i class="bi bi-chat-left-text me-1"></i><?= e($h['catatan_pimpinan']) ?>
            </div>
          <?php endif; ?>
        </td>
      </tr>
    <?php 
      endwhile; 
    endif; 
    ?>
    </tbody>
  </table>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.form-ajukan-edit').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var modalEl = this.closest('.modal');
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            
            Swal.fire({
                title: 'Kirim Pengajuan Edit?',
                text: 'Usulan perubahan absensi ini akan dikirim ke Pimpinan untuk proses persetujuan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-send me-1"></i> Ya, Kirim Sekarang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (modalInstance) modalInstance.hide();
                    form.submit();
                }
            });
        });
    });
});
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
