<?php
require_once __DIR__ . '/../layout/header.php';
require_pimpinan();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['keputusan'])) {
    $id=(int)($_POST['id_permintaan']??0);
    $keputusan=$_POST['keputusan']==='setujui'?'Disetujui':'Ditolak';
    $catatan=trim($_POST['catatan_pimpinan']??'');
    $catatanEsc=mysqli_real_escape_string($conn,$catatan);
    $userId=(int)$_SESSION['id_user'];
    mysqli_begin_transaction($conn);
    $result=mysqli_query($conn,"SELECT * FROM permintaan_edit_absensi WHERE id_permintaan=$id AND status='Menunggu' FOR UPDATE");
    $req=$result?mysqli_fetch_assoc($result):null;
    $ok=(bool)$req;
    if($ok && $keputusan==='Disetujui'){
        $idAbs=(int)$req['id_absensi'];
        $ok=mysqli_query($conn,"UPDATE absensi SET hadir=".(int)$req['hadir_baru'].",sakit=".(int)$req['sakit_baru'].",izin=".(int)$req['izin_baru'].",alpha=".(int)$req['alpha_baru'].",lembur_jam=".(int)$req['lembur_jam_baru'].",diperbarui_pada=NOW() WHERE id_absensi=$idAbs");
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
<p class="section-desc">Pimpinan menyetujui atau menolak perubahan rekap absensi bulanan.</p>
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>Karyawan</th><th>Periode</th><th>Data Lama</th><th>Data Usulan</th><th>Alasan</th><th>Pengaju</th><th>Status</th><th>Keputusan</th></tr></thead><tbody>
<?php $no=1;if($data):while($row=mysqli_fetch_assoc($data)):$old=json_decode($row['data_lama'],true)?:[];?><tr><td><?= $no++ ?></td><td><strong><?= e($row['nip']) ?></strong><br><?= e($row['nama_karyawan']) ?></td><td><?= e($row['bulan'].' '.$row['tahun']) ?></td><td class="small">Hadir <?= (int)($old['hadir']??0) ?>; Sakit <?= (int)($old['sakit']??0) ?>; Izin <?= (int)($old['izin']??0) ?>; Alpha <?= (int)($old['alpha']??0) ?>; Lembur <?= (int)($old['lembur_jam']??0) ?> jam</td><td class="small">Hadir <?= $row['hadir_baru'] ?>; Sakit <?= $row['sakit_baru'] ?>; Izin <?= $row['izin_baru'] ?>; Alpha <?= $row['alpha_baru'] ?>; Lembur <?= $row['lembur_jam_baru'] ?> jam</td><td><?= e($row['alasan_perubahan']) ?></td><td><?= e($row['pengaju']) ?><br><span class="text-muted small"><?= e($row['tanggal_pengajuan']) ?></span></td><td><?= status_badge($row['status']) ?><?php if($row['catatan_pimpinan']):?><div class="small mt-1"><?= e($row['catatan_pimpinan']) ?></div><?php endif;?></td><td>
<?php if($row['status']==='Menunggu'):?><form method="post" class="d-grid gap-2"><input type="hidden" name="id_permintaan" value="<?= $row['id_permintaan'] ?>"><input name="catatan_pimpinan" class="form-control form-control-sm" placeholder="Catatan (opsional)"><button name="keputusan" value="setujui" class="btn btn-sm btn-success">Setujui</button><button name="keputusan" value="tolak" class="btn btn-sm btn-danger">Tolak</button></form><?php else:?><span class="text-muted">Selesai</span><?php endif;?>
</td></tr><?php endwhile;endif;?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
