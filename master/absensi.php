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
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>NIP</th><th>Nama</th><th>Periode</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpha</th><th>Potongan Alpha</th><th>Status Edit</th><th>Aksi</th></tr></thead><tbody>
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
  <td>
    <?php if($row['status_edit'] === 'Menunggu'): ?>
      <button class="btn btn-sm btn-outline-secondary" disabled title="Menunggu Persetujuan Pimpinan"><i class="bi bi-hourglass-split"></i> Menunggu</button>
    <?php else: ?>
      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id_absensi'] ?>" title="Ajukan Edit">
        <i class="bi bi-pencil-square"></i> Ajukan Edit
      </button>
      
      <!-- Modal Ajukan Edit -->
      <div class="modal fade" id="editModal<?= $row['id_absensi'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form method="post" class="modal-content text-start">
            <input type="hidden" name="ajukan_edit_absensi" value="1">
            <input type="hidden" name="id_absensi" value="<?= $row['id_absensi'] ?>">
            <div class="modal-header">
              <h5 class="modal-title">Ajukan Edit Absensi - <?= e($row['nama_karyawan']) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="mb-3 text-muted">Periode: <?= e($row['bulan'] . ' ' . $row['tahun']) ?></p>
              
              <div class="alert alert-info py-2 small">
                <i class="bi bi-info-circle me-1"></i> Perubahan ini memerlukan persetujuan Pimpinan.
              </div>

              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label">Hadir</label>
                  <input type="number" name="hadir" class="form-control" value="<?= $row['hadir'] ?>" min="0" required>
                </div>
                <div class="col-6">
                  <label class="form-label">Sakit</label>
                  <input type="number" name="sakit" class="form-control" value="<?= $row['sakit'] ?>" min="0" required>
                </div>
                <div class="col-6">
                  <label class="form-label">Izin</label>
                  <input type="number" name="izin" class="form-control" value="<?= $row['izin'] ?>" min="0" required>
                </div>
                <div class="col-6">
                  <label class="form-label">Alpha</label>
                  <input type="number" name="alpha" class="form-control" value="<?= $row['alpha'] ?>" min="0" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Alasan Perubahan</label>
                  <textarea name="alasan" class="form-control" rows="2" required placeholder="Jelaskan alasan mengapa absensi diubah..."></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
