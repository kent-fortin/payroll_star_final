<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn,"SELECT * FROM absensi WHERE id_absensi=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan'])) {
    $idAbsensi=(int)($_POST['id_absensi']??0);
    $idKaryawan=(int)($_POST['id_karyawan']??0);
    $bulan=trim($_POST['bulan']??'');
    $tahun=(int)($_POST['tahun']??date('Y'));
    $sakit=max(0,(int)($_POST['sakit']??0));
    $izin=max(0,(int)($_POST['izin']??0));
    $alpha=max(0,(int)($_POST['alpha']??0));
    
    // Hitung hadir secara otomatis
    $totalHariKerja = get_setting($conn, 'total_hari_kerja', 26);
    $hadir = max(0, $totalHariKerja - ($sakit + $izin + $alpha));
    if($idKaryawan<1||bulan_nomor($bulan)===0||$tahun<2000){
        set_flash('danger','Data absensi gagal disimpan. Periksa karyawan, bulan, dan tahun.');
    } elseif($idAbsensi>0){
        $alasan=trim($_POST['alasan_perubahan']??'');
        $oldResult=mysqli_query($conn,"SELECT * FROM absensi WHERE id_absensi=$idAbsensi LIMIT 1");
        $old=$oldResult?mysqli_fetch_assoc($oldResult):null;
        $pending=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM permintaan_edit_absensi WHERE id_absensi=$idAbsensi AND status='Menunggu'"));
        if(!$old){
            set_flash('danger','Data absensi yang akan diedit tidak ditemukan.');
        } elseif((int)($pending['total']??0)>0){
            set_flash('warning','Masih ada permintaan edit yang menunggu persetujuan pimpinan.');
        } elseif($alasan===''){
            set_flash('danger','Alasan perubahan wajib diisi untuk pengajuan edit.');
        } else {
            // Simpan data lama tanpa lembur_jam (sudah dipisah ke tabel lembur)
            $dataLama = ['hadir'=>$old['hadir'],'sakit'=>$old['sakit'],'izin'=>$old['izin'],'alpha'=>$old['alpha']];
            $json=mysqli_real_escape_string($conn,json_encode($dataLama,JSON_UNESCAPED_UNICODE));
            $alasanEsc=mysqli_real_escape_string($conn,$alasan);
            $userId=(int)$_SESSION['id_user'];
            $sql="INSERT INTO permintaan_edit_absensi (id_absensi,hadir_baru,sakit_baru,izin_baru,alpha_baru,alasan_perubahan,data_lama,id_pengaju) VALUES ($idAbsensi,$hadir,$sakit,$izin,$alpha,'$alasanEsc','$json',$userId)";
            $ok=mysqli_query($conn,$sql);
            set_flash($ok?'success':'danger',$ok?'Permintaan edit absensi telah dikirim kepada pimpinan.':'Permintaan edit absensi gagal dikirim. '.mysqli_error($conn));
            if(!$ok) app_log('Request edit absensi: '.mysqli_error($conn));
        }
    } else {
        $userId=(int)$_SESSION['id_user'];
        $stmt=mysqli_prepare($conn,'INSERT INTO absensi (id_karyawan,bulan,tahun,hadir,sakit,izin,alpha,dibuat_oleh) VALUES (?,?,?,?,?,?,?,?)');
        $ok=false;
        if($stmt){mysqli_stmt_bind_param($stmt,'isiiiiii',$idKaryawan,$bulan,$tahun,$hadir,$sakit,$izin,$alpha,$userId);$ok=mysqli_stmt_execute($stmt);}
        set_flash($ok?'success':'danger',$ok?'Rekap absensi bulanan berhasil disimpan.':'Data gagal disimpan. Rekap untuk karyawan dan periode tersebut mungkin sudah ada.');
        if(!$ok) app_log('Insert absensi: '.mysqli_error($conn));
    }
    redirect('master/absensi.php');
}
$karyawan=mysqli_query($conn,"SELECT id_karyawan,nip,nama_karyawan FROM karyawan WHERE status_karyawan IN ('Tetap','Kontrak') ORDER BY nama_karyawan");
$data=mysqli_query($conn,"SELECT a.*,k.nip,k.nama_karyawan,
(SELECT p.status FROM permintaan_edit_absensi p WHERE p.id_absensi=a.id_absensi ORDER BY p.id_permintaan DESC LIMIT 1) status_edit
FROM absensi a JOIN karyawan k ON k.id_karyawan=a.id_karyawan ORDER BY a.tahun DESC,FIELD(a.bulan,'Desember','November','Oktober','September','Agustus','Juli','Juni','Mei','April','Maret','Februari','Januari'),k.nama_karyawan");
$tarifAlpha=get_setting($conn,'potongan_alpha_per_hari',25000);
?>
<div class="card p-4 mb-4">
<div class="section-header">
  <i class="bi bi-calendar-check"></i>
  <h2 class="h5"><?= $edit ? 'Ajukan Edit Rekap Absensi' : 'Tambah Rekap Absensi Bulanan' ?></h2>
</div>
<p class="section-desc"><?= $edit ? 'Edit data harus disetujui pimpinan.' : 'Catat rekap kehadiran bulanan karyawan. Data lembur harian dicatat di menu <strong>Data Lembur</strong>.' ?></p>
<form method="post" class="row g-3"><input type="hidden" name="id_absensi" value="<?= e($edit['id_absensi']??'') ?>">
<div class="col-md-4"><label class="form-label">Karyawan</label><select name="id_karyawan" class="form-select" <?= $edit?'disabled':'' ?> required><?php if($karyawan):while($k=mysqli_fetch_assoc($karyawan)):?><option value="<?= $k['id_karyawan'] ?>" <?= (int)($edit['id_karyawan']??0)===(int)$k['id_karyawan']?'selected':'' ?>><?= e($k['nip'].' - '.$k['nama_karyawan']) ?></option><?php endwhile;endif;?></select><?php if($edit):?><input type="hidden" name="id_karyawan" value="<?= $edit['id_karyawan'] ?>"><?php endif;?></div>
<div class="col-md-2"><label class="form-label">Bulan</label><select name="bulan" class="form-select" <?= $edit?'disabled':'' ?>><?= bulan_options($edit['bulan']??current_month_name()) ?></select><?php if($edit):?><input type="hidden" name="bulan" value="<?= e($edit['bulan']) ?>"><?php endif;?></div>
<div class="col-md-2"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-control" value="<?= e($edit['tahun']??date('Y')) ?>" <?= $edit?'readonly':'' ?> required></div>
<div class="col-md-1"><label class="form-label">Sakit</label><input type="number" min="0" name="sakit" class="form-control" value="<?= e($edit['sakit']??0) ?>"></div>
<div class="col-md-1"><label class="form-label">Izin</label><input type="number" min="0" name="izin" class="form-control" value="<?= e($edit['izin']??0) ?>"></div>
<div class="col-md-1"><label class="form-label">Alpha</label><input type="number" min="0" name="alpha" class="form-control" value="<?= e($edit['alpha']??0) ?>"></div>
<?php if($edit):?><div class="col-md-7"><label class="form-label">Alasan Perubahan</label><input name="alasan_perubahan" class="form-control" required placeholder="Jelaskan alasan koreksi data absensi"></div><?php endif;?>
<div class="col-12 mt-4 pt-3 border-top"><button name="simpan" class="btn btn-primary px-5"><?= $edit?'Ajukan Edit':'Simpan Data' ?></button> <?php if($edit):?><a class="btn btn-secondary px-4 ms-2" href="<?= url('master/absensi.php') ?>">Batal</a><?php endif;?></div>
</form></div>
<div class="formula-box mb-4 small"><strong>Info:</strong> Potongan alpha = hari alpha × <?= rupiah($tarifAlpha) ?>. Untuk data lembur, gunakan menu <strong>Data Lembur</strong>.</div>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Rekap Absensi</h2>
</div>
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>NIP</th><th>Nama</th><th>Periode</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpha</th><th>Potongan Alpha</th><th>Status Edit</th><th>Aksi</th></tr></thead><tbody>
<?php $no=1;if($data):while($row=mysqli_fetch_assoc($data)):?><tr><td><?= $no++ ?></td><td><?= e($row['nip']) ?></td><td><?= e($row['nama_karyawan']) ?></td><td><?= e($row['bulan'].' '.$row['tahun']) ?></td><td><?= $row['hadir'] ?></td><td><?= $row['sakit'] ?></td><td><?= $row['izin'] ?></td><td><?= $row['alpha'] ?></td><td><?= rupiah($row['alpha']*$tarifAlpha) ?></td><td><?= $row['status_edit']?status_badge($row['status_edit']):'<span class="text-muted">-</span>' ?></td><td><a class="btn btn-sm btn-warning" href="?edit=<?= $row['id_absensi'] ?>">Ajukan Edit</a></td></tr><?php endwhile;endif;?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
