<?php
/**
 * ============================================================================
 * NAMA FILE: absensi.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Menampilkan daftar pengajuan perubahan (edit) absensi bulanan yang diajukan oleh Admin untuk disetujui atau ditolak oleh Pimpinan.
 *
 * ALUR & FITUR UTAMA:
 * 1. Validasi wajib isi Catatan Pimpinan apabila menolak pengajuan (di client via SweetAlert2 & di server via flash error).
 * 2. Persetujuan otomatis memperbarui data kehadiran di tabel absensi.
 * 3. Menampilkan badge status dan riwayat pengajuan.
 *
 * HAK AKSES / PENGGUNA: Pimpinan
 * ============================================================================
 */

require_once __DIR__ . '/../layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan hanya pengguna dengan peran Pimpinan yang dapat mengakses halaman approval ini.
require_pimpinan();

// --- SECTION 2: PEMROSESAN PERSETUJUAN ATAU PENOLAKAN EDIT ABSENSI ---
// Menangani aksi tombol Setujui atau Tolak. Jika ditolak, sistem memvalidasi bahwa kolom Catatan Pimpinan wajib diisi.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keputusan'])) {
  $id = (int) ($_POST['id_permintaan'] ?? 0);
  $keputusan = $_POST['keputusan'] === 'setujui' ? 'Disetujui' : 'Ditolak';
  $catatan = trim($_POST['catatan_pimpinan'] ?? '');
  if ($keputusan === 'Ditolak' && $catatan === '') {
    set_flash('danger', 'Catatan Pimpinan wajib diisi jika menolak pengajuan edit absensi.');
    redirect('approval/absensi.php');
  }
  $catatanEsc = mysqli_real_escape_string($conn, $catatan);
  $userId = (int) $_SESSION['id_user'];
  mysqli_begin_transaction($conn);
  $result = mysqli_query($conn, "SELECT * FROM permintaan_edit_absensi WHERE id_permintaan=$id AND status='Menunggu' FOR UPDATE");
  $req = $result ? mysqli_fetch_assoc($result) : null;
  $ok = (bool) $req;
  if ($ok && $keputusan === 'Disetujui') {
    $idAbs = (int) $req['id_absensi'];
    // Update absensi tanpa lembur_jam (sudah dipisah ke tabel lembur)
    $ok = mysqli_query($conn, "UPDATE absensi SET hadir=" . (int) $req['hadir_baru'] . ",sakit=" . (int) $req['sakit_baru'] . ",izin=" . (int) $req['izin_baru'] . ",alpha=" . (int) $req['alpha_baru'] . ",diperbarui_pada=NOW() WHERE id_absensi=$idAbs");
  }
  if ($ok) {
    $statusEsc = mysqli_real_escape_string($conn, $keputusan);
    $ok = mysqli_query($conn, "UPDATE permintaan_edit_absensi SET status='$statusEsc',id_penyetuju=$userId,tanggal_keputusan=NOW(),catatan_pimpinan='$catatanEsc' WHERE id_permintaan=$id");
  }
  if ($ok) {
    mysqli_commit($conn);
    set_flash('success', 'Keputusan edit absensi berhasil disimpan.');
  } else {
    mysqli_rollback($conn);
    app_log('Approval absensi: ' . mysqli_error($conn));
    set_flash('danger', 'Keputusan gagal disimpan. Silakan coba kembali.');
  }
  redirect('approval/absensi.php');
}

$data = mysqli_query($conn, "SELECT p.*,a.bulan,a.tahun,k.nip,k.nama_karyawan,u.nama_lengkap pengaju
FROM permintaan_edit_absensi p JOIN absensi a ON a.id_absensi=p.id_absensi JOIN karyawan k ON k.id_karyawan=a.id_karyawan JOIN users u ON u.id_user=p.id_pengaju
ORDER BY FIELD(p.status,'Menunggu','Disetujui','Ditolak'),p.id_permintaan DESC");
?>
<div class="card p-4">
  <div class="section-header">
    <i class="bi bi-check2-circle"></i>
    <h2 class="h5 mb-0">Persetujuan Edit Absensi</h2>
  </div>
  <p class="section-desc mb-4">Pimpinan menyetujui atau menolak perubahan rekap absensi bulanan (Hadir, Sakit, Izin,
    Alpha) yang diajukan oleh Admin.</p>

  <div class="table-responsive">
    <table class="table table-hover dt-table align-middle" style="width:100%">
      <thead>
        <tr>
          <th style="width: 5%">No</th>
          <th style="width: 22%">Karyawan & Periode</th>
          <th style="width: 25%">Perbandingan Rekap (Lama ➔ Usulan)</th>
          <th style="width: 23%">Alasan & Pengaju</th>
          <th style="width: 12%">Status</th>
          <th style="width: 13%" class="text-center">Aksi Keputusan</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1;
        if ($data):
          while ($row = mysqli_fetch_assoc($data)):
            $old = json_decode($row['data_lama'], true) ?: []; ?>
            <tr>
              <td>
                <?= $no++ ?>
              </td>
              <td>
                <div class="fw-bold fs-6 text-dark">
                  <?= e($row['nama_karyawan']) ?>
                </div>
                <div class="small text-muted mb-1">NIP: <strong>
                    <?= e($row['nip']) ?>
                  </strong></div>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i
                    class="bi bi-calendar3 me-1"></i>
                  <?= e($row['bulan'] . ' ' . $row['tahun']) ?>
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2" style="min-width: 200px;">
                  <div class="bg-light p-2 rounded border flex-fill text-center" style="font-size: 0.8rem;">
                    <div class="text-muted fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">DATA LAMA</div>
                    <div class="d-flex justify-content-around">
                      <span title="Hadir" class="text-success fw-bold">H:
                        <?= (int) ($old['hadir'] ?? 0) ?>
                      </span>
                      <span title="Sakit" class="text-warning fw-bold">S:
                        <?= (int) ($old['sakit'] ?? 0) ?>
                      </span>
                      <span title="Izin" class="text-info fw-bold">I:
                        <?= (int) ($old['izin'] ?? 0) ?>
                      </span>
                      <span title="Alpha" class="text-danger fw-bold">A:
                        <?= (int) ($old['alpha'] ?? 0) ?>
                      </span>
                    </div>
                  </div>
                  <i class="bi bi-arrow-right-circle-fill text-primary fs-5"></i>
                  <div class="bg-primary-subtle p-2 rounded border border-primary-subtle flex-fill text-center"
                    style="font-size: 0.8rem;">
                    <div class="text-primary fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">USULAN BARU
                    </div>
                    <div class="d-flex justify-content-around">
                      <span title="Hadir" class="text-success fw-bold">H:
                        <?= (int) $row['hadir_baru'] ?>
                      </span>
                      <span title="Sakit" class="text-warning fw-bold">S:
                        <?= (int) $row['sakit_baru'] ?>
                      </span>
                      <span title="Izin" class="text-info fw-bold">I:
                        <?= (int) $row['izin_baru'] ?>
                      </span>
                      <span title="Alpha" class="text-danger fw-bold">A:
                        <?= (int) $row['alpha_baru'] ?>
                      </span>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="p-2 bg-light rounded border-start border-primary border-3 small mb-1 fst-italic"
                  style="max-width: 260px; word-wrap: break-word; white-space: normal;">
                  "
                  <?= e($row['alasan_perubahan']) ?>"
                </div>
                <div class="text-muted" style="font-size: 0.75rem;">
                  <i class="bi bi-person me-1"></i>
                  <?= e($row['pengaju']) ?><br>
                  <i class="bi bi-clock me-1"></i>
                  <?= e($row['tanggal_pengajuan']) ?>
                </div>
              </td>
              <td>
                <?= status_badge($row['status']) ?>
                <?php if ($row['catatan_pimpinan']): ?>
                  <div class="mt-2 p-2 bg-danger-subtle text-danger rounded border border-danger-subtle small fw-semibold"
                    style="max-width: 200px; word-wrap: break-word; white-space: normal; font-size: 0.75rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?= e($row['catatan_pimpinan']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($row['status'] === 'Menunggu'): ?>
                  <form method="post" class="form-approval-absensi m-0">
                    <input type="hidden" name="id_permintaan" value="<?= $row['id_permintaan'] ?>">
                    <div class="d-flex justify-content-center gap-1">
                      <button type="button" class="btn btn-sm btn-success fw-bold px-3 btn-setujui-absensi"
                        title="Setujui Usulan"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                      <button type="button" class="btn btn-sm btn-danger fw-bold px-3 btn-tolak-absensi"
                        title="Tolak Usulan"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                    </div>
                  </form>
                <?php else: ?>
                  <span class="badge bg-light text-muted border px-3 py-2"><i class="bi bi-check2-all me-1"></i>Selesai</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Tombol Setujui dengan SweetAlert Konfirmasi
    document.querySelectorAll('.btn-setujui-absensi').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var form = this.closest('form');
        Swal.fire({
          title: 'Setujui Perubahan Absensi?',
          text: 'Data rekap absensi bulanan karyawan ini akan segera diperbarui sesuai usulan.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#16a34a',
          cancelButtonColor: '#64748b',
          confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Ya, Setujui!',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'keputusan';
            input.value = 'setujui';
            form.appendChild(input);
            form.submit();
          }
        });
      });
    });

    // Tombol Tolak dengan SweetAlert Textarea Prompt
    document.querySelectorAll('.btn-tolak-absensi').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var form = this.closest('form');
        Swal.fire({
          title: 'Tolak Pengajuan Edit Absensi',
          text: 'Silakan berikan alasan atau catatan mengapa pengajuan ini ditolak (wajib diisi):',
          input: 'textarea',
          inputPlaceholder: 'Contoh: Surat keterangan dokter tidak dilampirkan atau alasan tidak sah...',
          inputAttributes: {
            'aria-label': 'Masukkan alasan penolakan'
          },
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#64748b',
          confirmButtonText: '<i class="bi bi-x-lg me-1"></i> Ya, Tolak Pengajuan',
          cancelButtonText: 'Batal',
          preConfirm: (catatan) => {
            if (!catatan || !catatan.trim()) {
              Swal.showValidationMessage('Catatan Pimpinan wajib diisi saat menolak pengajuan!');
              return false;
            }
            return catatan.trim();
          }
        }).then((result) => {
          if (result.isConfirmed) {
            var inputCatatan = document.createElement('input');
            inputCatatan.type = 'hidden';
            inputCatatan.name = 'catatan_pimpinan';
            inputCatatan.value = result.value;
            form.appendChild(inputCatatan);

            var inputKeputusan = document.createElement('input');
            inputKeputusan.type = 'hidden';
            inputKeputusan.name = 'keputusan';
            inputKeputusan.value = 'tolak';
            form.appendChild(inputKeputusan);

            form.submit();
          }
        });
      });
    });
  });
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>