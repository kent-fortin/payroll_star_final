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
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['keputusan'])) {
    $id=(int)($_POST['id_permintaan']??0);
    $keputusan=$_POST['keputusan']==='setujui'?'Disetujui':'Ditolak';
    $catatan=trim($_POST['catatan_pimpinan']??'');
    if ($keputusan === 'Ditolak' && $catatan === '') {
        set_flash('danger', 'Catatan Pimpinan wajib diisi jika menolak pengajuan edit absensi.');
        redirect('approval/absensi.php');
    }
    $catatanEsc=mysqli_real_escape_string($conn,$catatan);
    $userId=(int)$_SESSION['id_user'];
    mysqli_begin_transaction($conn);
    $result=mysqli_query($conn,"SELECT * FROM permintaan_edit_absensi WHERE id_permintaan=$id AND status='Menunggu' FOR UPDATE");
    $req=$result?mysqli_fetch_assoc($result):null;
    $ok=(bool)$req;
    if($ok && $keputusan==='Disetujui'){
        $idAbs=(int)$req['id_absensi'];
        // Update absensi tanpa lembur_jam (sudah dipisah ke tabel lembur)
        $ok=mysqli_query($conn,"UPDATE absensi SET hadir=".(int)$req['hadir_baru'].",sakit=".(int)$req['sakit_baru'].",izin=".(int)$req['izin_baru'].",alpha=".(int)$req['alpha_baru'].",diperbarui_pada=NOW() WHERE id_absensi=$idAbs");
    }
    if($ok){
        $statusEsc=mysqli_real_escape_string($conn,$keputusan);
        $ok=mysqli_query($conn,"UPDATE permintaan_edit_absensi SET status='$statusEsc',id_penyetuju=$userId,tanggal_keputusan=NOW(),catatan_pimpinan='$catatanEsc' WHERE id_permintaan=$id");
    }
    if($ok){mysqli_commit($conn);set_flash('success','Keputusan edit absensi berhasil disimpan.');}
    else{mysqli_rollback($conn);app_log('Approval absensi: '.mysqli_error($conn));set_flash('danger','Keputusan gagal disimpan. Silakan coba kembali.');}
    redirect('approval/absensi.php');
}

$data=mysqli_query($conn,"SELECT p.*,a.bulan,a.tahun,k.nip,k.nama_karyawan,u.nama_lengkap pengaju
FROM permintaan_edit_absensi p JOIN absensi a ON a.id_absensi=p.id_absensi JOIN karyawan k ON k.id_karyawan=a.id_karyawan JOIN users u ON u.id_user=p.id_pengaju
ORDER BY FIELD(p.status,'Menunggu','Disetujui','Ditolak'),p.id_permintaan DESC");
?>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-check2-circle"></i>
  <h2 class="h5">Persetujuan Edit Absensi</h2>
</div>
<p class="section-desc">Pimpinan menyetujui atau menolak perubahan rekap absensi bulanan (Hadir, Sakit, Izin, Alpha).</p>
<div class="table-responsive"><table class="table table-striped dt-table align-middle" style="width:100%"><thead><tr><th>No</th><th>Karyawan</th><th>Periode</th><th>Data Lama</th><th>Data Usulan</th><th>Alasan</th><th>Pengaju</th><th>Status</th><th style="min-width: 200px;">Keputusan</th></tr></thead><tbody>
<?php $no=1;if($data):while($row=mysqli_fetch_assoc($data)):$old=json_decode($row['data_lama'],true)?:[];?>
<tr>
  <td><?= $no++ ?></td>
  <td><strong><?= e($row['nip']) ?></strong><br><?= e($row['nama_karyawan']) ?></td>
  <td><?= e($row['bulan'].' '.$row['tahun']) ?></td>
  <td class="small">
    <div class="d-flex flex-wrap gap-1">
      <span class="badge bg-success-subtle text-success border border-success-subtle">H: <?= (int)($old['hadir']??0) ?></span>
      <span class="badge bg-warning-subtle text-warning border border-warning-subtle">S: <?= (int)($old['sakit']??0) ?></span>
      <span class="badge bg-info-subtle text-info border border-info-subtle">I: <?= (int)($old['izin']??0) ?></span>
      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">A: <?= (int)($old['alpha']??0) ?></span>
    </div>
  </td>
  <td class="small">
    <div class="d-flex flex-wrap gap-1">
      <span class="badge bg-success-subtle text-success border border-success-subtle">H: <?= (int)$row['hadir_baru'] ?></span>
      <span class="badge bg-warning-subtle text-warning border border-warning-subtle">S: <?= (int)$row['sakit_baru'] ?></span>
      <span class="badge bg-info-subtle text-info border border-info-subtle">I: <?= (int)$row['izin_baru'] ?></span>
      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">A: <?= (int)$row['alpha_baru'] ?></span>
    </div>
  </td>
  <td><?= e($row['alasan_perubahan']) ?></td>
  <td><?= e($row['pengaju']) ?><br><span class="text-muted small"><?= e($row['tanggal_pengajuan']) ?></span></td>
  <td><?= status_badge($row['status']) ?><?php if($row['catatan_pimpinan']):?><div class="small mt-1 p-2 bg-danger-subtle text-danger rounded fw-bold" style="font-size: 0.75rem;"><i class="bi bi-chat-left-text me-1"></i><?= e($row['catatan_pimpinan']) ?></div><?php endif;?></td>
  <td>
    <?php if($row['status']==='Menunggu'):?>
    <form method="post" class="form-approval-absensi" style="min-width: 200px;">
      <input type="hidden" name="id_permintaan" value="<?= $row['id_permintaan'] ?>">
      <div class="mb-2">
        <input name="catatan_pimpinan" class="form-control form-control-sm input-catatan" placeholder="Catatan (wajib saat menolak)" style="font-size: 0.8rem;">
      </div>
      <div class="d-flex gap-1">
        <button type="submit" name="keputusan" value="setujui" class="btn btn-sm btn-success flex-fill fw-bold"><i class="bi bi-check-lg me-1"></i>Setujui</button>
        <button type="button" class="btn btn-sm btn-danger flex-fill fw-bold btn-tolak-absensi"><i class="bi bi-x-lg me-1"></i>Tolak</button>
      </div>
    </form>
    <?php else:?><span class="text-muted">Selesai</span><?php endif;?>
  </td>
</tr>
<?php endwhile;endif;?>
</tbody></table></div></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-tolak-absensi').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = this.closest('form');
            var catatanInput = form.querySelector('.input-catatan');
            var catatanVal = catatanInput ? catatanInput.value.trim() : '';
            if (catatanVal === '') {
                Swal.fire({
                    title: 'Perhatian!',
                    text: 'Catatan Pimpinan wajib diisi jika menolak pengajuan edit absensi. Silakan berikan alasan penolakan pada kolom catatan.',
                    icon: 'warning',
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'OK, Saya Mengerti'
                }).then(() => {
                    if (catatanInput) {
                        catatanInput.focus();
                        catatanInput.style.borderColor = '#dc2626';
                    }
                });
                return false;
            } else {
                Swal.fire({
                    title: 'Konfirmasi Tolak',
                    text: 'Apakah Anda yakin ingin menolak pengajuan edit absensi ini dengan catatan: "' + catatanVal + '"?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Tolak Pengajuan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var inputHidden = document.createElement('input');
                        inputHidden.type = 'hidden';
                        inputHidden.name = 'keputusan';
                        inputHidden.value = 'tolak';
                        form.appendChild(inputHidden);
                        form.submit();
                    }
                });
            }
        });
    });
    
    document.querySelectorAll('.input-catatan').forEach(function(input) {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.style.borderColor = '';
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
