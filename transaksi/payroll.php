<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();
$preview=null;
$selectedId=(int)($_POST['id_karyawan']??0);
$selectedMonth=trim($_POST['bulan']??current_month_name());
$selectedYear=(int)($_POST['tahun']??date('Y'));
$selectedTunjangan=(float)($_POST['total_tunjangan']??0);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['preview'])) {
    $preview=calculate_payroll($conn,$selectedId,$selectedMonth,$selectedYear,$selectedTunjangan);
    if(!$preview)set_flash('warning','Rekap absensi pada periode tersebut belum tersedia.');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_payroll'])) {
    $calc=calculate_payroll($conn,$selectedId,$selectedMonth,$selectedYear,$selectedTunjangan);
    if(!$calc){
        set_flash('danger','Payroll gagal disimpan karena rekap absensi belum tersedia.');
    } else {
        $bulanEsc=mysqli_real_escape_string($conn,$selectedMonth);
        $existingResult=mysqli_query($conn,"SELECT id_payroll,status_pembayaran FROM payroll WHERE id_karyawan=$selectedId AND bulan='$bulanEsc' AND tahun=$selectedYear LIMIT 1");
        $existing=$existingResult?mysqli_fetch_assoc($existingResult):null;
        if($existing && $existing['status_pembayaran']==='Sudah Dibayar'){
            set_flash('warning','Payroll yang sudah dibayar tidak dapat dihitung ulang.');
        } else {
            $userId=(int)$_SESSION['id_user'];
            if($existing){
                $idPayroll=(int)$existing['id_payroll'];
                $stmt=mysqli_prepare($conn,'UPDATE payroll SET gaji_pokok=?,jam_lembur=?,tarif_lembur=?,total_lembur=?,total_tunjangan=?,jumlah_alpha=?,tarif_alpha=?,total_potongan_alpha=?,total_gaji_bersih=?,tanggal_proses=NOW(),diproses_oleh=? WHERE id_payroll=?');
                $ok=false;
                if($stmt){mysqli_stmt_bind_param($stmt,'didddidddii',$calc['gaji_pokok'],$calc['lembur_jam'],$calc['tarif_lembur'],$calc['total_lembur'],$calc['total_tunjangan'],$calc['alpha'],$calc['tarif_alpha'],$calc['potongan_alpha'],$calc['gaji_bersih'],$userId,$idPayroll);$ok=mysqli_stmt_execute($stmt);}
            } else {
                $stmt=mysqli_prepare($conn,'INSERT INTO payroll (id_karyawan,bulan,tahun,gaji_pokok,jam_lembur,tarif_lembur,total_lembur,total_tunjangan,jumlah_alpha,tarif_alpha,total_potongan_alpha,total_gaji_bersih,diproses_oleh) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $ok=false;
                if($stmt){mysqli_stmt_bind_param($stmt,'isididddidddi',$selectedId,$selectedMonth,$selectedYear,$calc['gaji_pokok'],$calc['lembur_jam'],$calc['tarif_lembur'],$calc['total_lembur'],$calc['total_tunjangan'],$calc['alpha'],$calc['tarif_alpha'],$calc['potongan_alpha'],$calc['gaji_bersih'],$userId);$ok=mysqli_stmt_execute($stmt);}
            }
            set_flash($ok?'success':'danger',$ok?'Payroll berhasil disimpan.':'Payroll gagal disimpan. Silakan coba kembali.');
            if(!$ok)app_log('Save payroll: '.mysqli_error($conn));
        }
    }
    redirect('transaksi/payroll.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ubah_status'])) {
    $id=(int)($_POST['id_payroll']??0);
    $status=$_POST['status']==='Sudah Dibayar'?'Sudah Dibayar':'Belum Dibayar';
    $tanggal=$status==='Sudah Dibayar'?"CURDATE()":"NULL";
    $statusEsc=mysqli_real_escape_string($conn,$status);
    $ok=mysqli_query($conn,"UPDATE payroll SET status_pembayaran='$statusEsc',tanggal_pembayaran=$tanggal WHERE id_payroll=$id");
    set_flash($ok?'success':'danger',$ok?'Status pembayaran berhasil diperbarui.':'Status pembayaran gagal diperbarui.');
    redirect('transaksi/payroll.php');
}

$karyawan=mysqli_query($conn,'SELECT k.id_karyawan,k.nip,k.nama_karyawan,j.nama_jabatan FROM karyawan k JOIN jabatan j ON j.id_jabatan=k.id_jabatan ORDER BY k.nama_karyawan');
$data=mysqli_query($conn,"SELECT p.*,k.nip,k.nama_karyawan,j.nama_jabatan FROM payroll p JOIN karyawan k ON k.id_karyawan=p.id_karyawan JOIN jabatan j ON j.id_jabatan=k.id_jabatan ORDER BY p.tahun DESC,FIELD(p.bulan,'Desember','November','Oktober','September','Agustus','Juli','Juni','Mei','April','Maret','Februari','Januari'),k.nama_karyawan");
?>
<div class="card p-4 mb-4">
<div class="section-header">
  <i class="bi bi-cash-stack"></i>
  <h2 class="h5">Proses Payroll</h2>
</div>
<p class="section-desc">Tampilkan hitungan terlebih dahulu, periksa rinciannya, lalu simpan payroll.</p>
<form method="post" class="row g-3 align-items-end">
<div class="col-md-3"><label class="form-label">Karyawan</label><select name="id_karyawan" class="form-select" required><option value="">Pilih Karyawan</option><?php if($karyawan):while($k=mysqli_fetch_assoc($karyawan)):?><option value="<?= $k['id_karyawan'] ?>" <?= $selectedId===(int)$k['id_karyawan']?'selected':'' ?>><?= e($k['nip'].' - '.$k['nama_karyawan'].' ('.$k['nama_jabatan'].')') ?></option><?php endwhile;endif;?></select></div>
<div class="col-md-2"><label class="form-label">Bulan</label><select name="bulan" class="form-select"><?= bulan_options($selectedMonth) ?></select></div>
<div class="col-md-2"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-control" value="<?= $selectedYear ?>"></div>
<div class="col-md-2"><label class="form-label">Tunjangan</label><input type="number" name="total_tunjangan" class="form-control" value="<?= $selectedTunjangan ?>"></div>
<div class="col-md-3 d-flex align-items-end"><button name="preview" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Tampilkan Hitungan</button></div>
</form></div>
<?php if($preview):?>
<div class="card content-card shadow-sm p-4 mb-4" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important;">
    <div class="d-flex justify-content-between align-items-start mb-4 pb-3" style="border-bottom: 2px dashed #e2e8f0;">
        <div>
            <h2 class="h4 mb-2 fw-bolder text-dark">Preview Perhitungan Gaji</h2>
            <div class="text-muted" style="font-size: 0.95rem;"><i class="bi bi-person-badge text-primary me-2"></i><strong class="text-dark"><?= e($preview['nip']) ?></strong> - <?= e($preview['nama_karyawan']) ?> <span class="mx-2 text-light">|</span> <i class="bi bi-calendar-event text-primary me-2"></i><span class="fw-medium text-dark"><?= e($preview['bulan'].' '.$preview['tahun']) ?></span></div>
        </div>
        <span class="badge px-3 py-2 fs-6 rounded-pill text-white shadow-sm" style="background: linear-gradient(135deg, #0ea5e9, #0284c7); font-weight: 600; letter-spacing: 0.5px;">Belum Disimpan</span>
    </div>
    
    <div class="row g-4 mb-2">
        <!-- Penerimaan -->
        <div class="col-lg-6 d-flex flex-column">
            <h6 class="text-success mb-3 fw-bold" style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;"><i class="bi bi-arrow-down-circle-fill me-2 fs-5 align-middle"></i> Penerimaan</h6>
            <div class="bg-white rounded-4 p-3 mb-3" style="border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div class="preview-row" style="border-bottom: 1px solid #f1f5f9;"><span>Jabatan</span><strong class="text-dark"><?= e($preview['nama_jabatan']) ?></strong></div>
                <div class="preview-row border-0 pb-0 pt-3"><span>Gaji Pokok</span><strong class="text-dark fs-6"><?= rupiah($preview['gaji_pokok']) ?></strong></div>
            </div>
            <div class="bg-white rounded-4 p-3 mb-3" style="border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div class="preview-row" style="border-bottom: 1px solid #f1f5f9;"><span>Jam Lembur (<?= $preview['lembur_jam'] ?> jam)</span><span class="text-muted small">@ <?= rupiah($preview['tarif_lembur']) ?></span></div>
                <div class="preview-row border-0 pb-0 pt-3"><span>Total Lembur</span><strong class="text-success">+ <?= rupiah($preview['total_lembur']) ?></strong></div>
            </div>
            <div class="bg-white rounded-4 p-3" style="border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div class="preview-row border-0 py-1"><span>Total Tunjangan</span><strong class="text-success">+ <?= rupiah($preview['total_tunjangan']) ?></strong></div>
            </div>
        </div>
        
        <!-- Potongan -->
        <div class="col-lg-6 d-flex flex-column">
            <h6 class="text-danger mb-3 fw-bold" style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;"><i class="bi bi-arrow-up-circle-fill me-2 fs-5 align-middle"></i> Potongan</h6>
            <div class="bg-white rounded-4 p-3 mb-4" style="border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div class="preview-row" style="border-bottom: 1px solid #f1f5f9;"><span>Jumlah Alpha (<?= $preview['alpha'] ?> hari)</span><span class="text-muted small">@ <?= rupiah($preview['tarif_alpha']) ?></span></div>
                <div class="preview-row border-0 pb-0 pt-3"><span>Total Potongan Alpha</span><strong class="text-danger">− <?= rupiah($preview['potongan_alpha']) ?></strong></div>
            </div>
            
            <div class="mt-auto">
                <div class="p-4 rounded-4 text-center overflow-hidden" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px dashed #93c5fd; position: relative;">
                    <div class="text-primary fw-bold mb-2 text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">Gaji Bersih (Take Home Pay)</div>
                    <h2 class="text-primary mb-0 fw-black" style="font-size: 2.25rem; letter-spacing: -1px;"><strong><?= rupiah($preview['gaji_bersih']) ?></strong></h2>
                    <div style="position: absolute; top: -20px; right: -20px; font-size: 6rem; color: #60a5fa; opacity: 0.1; line-height: 1;"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-4 pt-4" style="border-top: 2px dashed #e2e8f0;">
        <div class="text-muted small w-50" style="line-height: 1.6;">
            <strong>Formula Gaji:</strong><br>
            Gaji Pokok + Total Lembur + Total Tunjangan − Total Potongan Alpha
        </div>
        <form method="post" class="text-end">
            <input type="hidden" name="id_karyawan" value="<?= $selectedId ?>">
            <input type="hidden" name="bulan" value="<?= e($selectedMonth) ?>">
            <input type="hidden" name="tahun" value="<?= $selectedYear ?>">
            <input type="hidden" name="total_tunjangan" value="<?= $selectedTunjangan ?>">
            <button name="simpan_payroll" class="btn btn-primary btn-lg px-5 shadow rounded-pill fw-bold" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); transition: transform 0.2s;"><i class="bi bi-save2-fill me-2"></i>Simpan Payroll</button>
        </form>
    </div>
</div>
<?php endif;?>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Payroll</h2>
</div>
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>Karyawan</th><th>Periode</th><th>Gaji Pokok</th><th>Lembur</th><th>Tunjangan</th><th>Potongan Alpha</th><th>Gaji Bersih</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php $no=1;if($data):while($row=mysqli_fetch_assoc($data)):?><tr><td><?= $no++ ?></td><td><strong><?= e($row['nip']) ?></strong><br><?= e($row['nama_karyawan']) ?><br><span class="small text-muted"><?= e($row['nama_jabatan']) ?></span></td><td><?= e($row['bulan'].' '.$row['tahun']) ?></td><td><?= rupiah($row['gaji_pokok']) ?></td><td><?= $row['jam_lembur'] ?> × <?= rupiah($row['tarif_lembur']) ?><br><strong><?= rupiah($row['total_lembur']) ?></strong></td><td><strong><?= rupiah($row['total_tunjangan'] ?? 0) ?></strong></td><td><?= $row['jumlah_alpha'] ?> × <?= rupiah($row['tarif_alpha']) ?><br><strong><?= rupiah($row['total_potongan_alpha']) ?></strong></td><td><strong><?= rupiah($row['total_gaji_bersih']) ?></strong></td><td><?= status_badge($row['status_pembayaran']) ?><div class="small text-muted mt-1"><?= e($row['tanggal_pembayaran']??'-') ?></div></td><td><a class="btn btn-sm btn-dark mb-1 w-100" href="<?= url('transaksi/cetak_rincian.php?id='.$row['id_payroll']) ?>">Cetak Rincian</a><form method="post"><input type="hidden" name="id_payroll" value="<?= $row['id_payroll'] ?>"><input type="hidden" name="status" value="<?= $row['status_pembayaran']==='Sudah Dibayar'?'Belum Dibayar':'Sudah Dibayar' ?>"><button name="ubah_status" class="btn btn-sm w-100 <?= $row['status_pembayaran']==='Sudah Dibayar'?'btn-outline-warning':'btn-success' ?>"><?= $row['status_pembayaran']==='Sudah Dibayar'?'Batalkan Bayar':'Tandai Dibayar' ?></button></form></td></tr><?php endwhile;endif;?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
