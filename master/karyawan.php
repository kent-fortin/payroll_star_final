<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id_karyawan'] ?? 0);
    $nama = trim($_POST['nama_karyawan'] ?? '');
    $jk = ($_POST['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L';
    $jabatan = (int)($_POST['id_jabatan'] ?? 0);
    $s = $_POST['status_karyawan'] ?? 'Tetap';
    $status = in_array($s, ['Tetap','Kontrak','Resign']) ? $s : 'Tetap';
    $tanggal = trim($_POST['tanggal_masuk'] ?? '');
    $today = date('Y-m-d');
    if ($nama === '' || $jabatan < 1 || $tanggal === '') {
        set_flash('danger', 'Data karyawan gagal disimpan. Lengkapi seluruh data wajib.');
    } elseif ($tanggal > $today) {
        set_flash('danger', 'Data karyawan gagal disimpan. Tanggal masuk tidak boleh lebih dari tanggal hari ini.');
    } elseif ($id > 0) {
        $stmt = mysqli_prepare($conn, 'UPDATE karyawan SET nama_karyawan=?,jenis_kelamin=?,id_jabatan=?,status_karyawan=?,tanggal_masuk=? WHERE id_karyawan=?');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssissi', $nama, $jk, $jabatan, $status, $tanggal, $id);
            $ok = mysqli_stmt_execute($stmt);
        }
        set_flash($ok ? 'success':'danger', $ok ? 'Data karyawan berhasil diperbarui.':'Data karyawan gagal diperbarui.');
        if (!$ok) app_log('Update karyawan: '.mysqli_error($conn));
    } else {
        $placeholder = 'TMP' . bin2hex(random_bytes(5));
        $stmt = mysqli_prepare($conn, 'INSERT INTO karyawan (nip,nama_karyawan,jenis_kelamin,id_jabatan,status_karyawan,tanggal_masuk) VALUES (?,?,?,?,?,?)');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sssiss', $placeholder, $nama, $jk, $jabatan, $status, $tanggal);
            $ok = mysqli_stmt_execute($stmt);
        }
        if ($ok) {
            $newId = mysqli_insert_id($conn);
            $nip = generate_nip($newId);
            $nipEsc = mysqli_real_escape_string($conn, $nip);
            $ok = mysqli_query($conn, "UPDATE karyawan SET nip='$nipEsc' WHERE id_karyawan=$newId");
        }
        set_flash($ok ? 'success':'danger', $ok ? 'Data karyawan berhasil ditambahkan dengan NIP otomatis.':'Data karyawan gagal disimpan.');
        if (!$ok) app_log('Insert karyawan: '.mysqli_error($conn));
    }
    redirect('master/karyawan.php');
}
if (isset($_POST['resign'])) {
    $id = (int)($_POST['id_karyawan'] ?? 0);
    $ok = mysqli_query($conn, "UPDATE karyawan SET status_karyawan='Resign' WHERE id_karyawan=$id");
    set_flash($ok ? 'success' : 'danger', $ok ? 'Status karyawan berhasil diubah menjadi Resign.' : 'Status karyawan gagal diperbarui.');
    redirect('master/karyawan.php');
}
$jabatan = mysqli_query($conn,'SELECT id_jabatan,nama_jabatan FROM jabatan ORDER BY nama_jabatan');
$data = mysqli_query($conn,'SELECT k.*,j.nama_jabatan FROM karyawan k JOIN jabatan j ON j.id_jabatan=k.id_jabatan ORDER BY k.id_karyawan');
?>
<div class="card p-4 mb-4">
<div class="section-header">
  <i class="bi bi-people"></i>
  <h2 class="h5"><?= $edit ? 'Edit Karyawan' : 'Tambah Karyawan' ?></h2>
</div>
<form method="post" class="row g-3">
<input type="hidden" name="id_karyawan" value="<?= e($edit['id_karyawan']??'') ?>">
<div class="col-md-2"><label class="form-label">NIP</label><input class="form-control" value="<?= e($edit['nip']??'Dibuat otomatis') ?>" readonly></div>
<div class="col-md-3"><label class="form-label">Nama Karyawan</label><input name="nama_karyawan" class="form-control" value="<?= e($edit['nama_karyawan']??'') ?>" required></div>
<div class="col-md-1"><label class="form-label">JK</label><select name="jenis_kelamin" class="form-select"><option value="L" <?= ($edit['jenis_kelamin']??'L')==='L'?'selected':'' ?>>L</option><option value="P" <?= ($edit['jenis_kelamin']??'')==='P'?'selected':'' ?>>P</option></select></div>
<div class="col-md-2"><label class="form-label">Jabatan</label><select name="id_jabatan" class="form-select" required><option value="">Pilih</option><?php if($jabatan): while($j=mysqli_fetch_assoc($jabatan)): ?><option value="<?= $j['id_jabatan'] ?>" <?= (int)($edit['id_jabatan']??0)===(int)$j['id_jabatan']?'selected':'' ?>><?= e($j['nama_jabatan']) ?></option><?php endwhile; endif; ?></select></div>
<div class="col-md-2"><label class="form-label">Status</label><select name="status_karyawan" class="form-select"><option <?= ($edit['status_karyawan']??'Tetap')==='Tetap'?'selected':'' ?>>Tetap</option><option <?= ($edit['status_karyawan']??'')==='Kontrak'?'selected':'' ?>>Kontrak</option><option <?= ($edit['status_karyawan']??'')==='Resign'?'selected':'' ?>>Resign</option></select></div>
<div class="col-md-2"><label class="form-label">Tanggal Masuk</label><input type="date" name="tanggal_masuk" class="form-control" value="<?= e($edit['tanggal_masuk']??'') ?>" max="<?= date('Y-m-d') ?>" required></div>
<div class="col-12 mt-4 pt-3 border-top"><button name="simpan" class="btn btn-primary px-5"><?= $edit?'Update Data':'Simpan Data' ?></button> <?php if($edit): ?><a class="btn btn-secondary px-4 ms-2" href="<?= url('master/karyawan.php') ?>">Batal</a><?php endif; ?></div>
</form></div>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Karyawan</h2>
</div>
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>NIP</th><th>Nama</th><th>JK</th><th>Jabatan</th><th>Status</th><th>Tanggal Masuk</th><th>Aksi</th></tr></thead><tbody>
<?php $no=1;if($data):while($row=mysqli_fetch_assoc($data)): ?><tr><td><?= $no++ ?></td><td><?= e($row['nip']) ?></td><td><?= e($row['nama_karyawan']) ?></td><td><?= e($row['jenis_kelamin']) ?></td><td><?= e($row['nama_jabatan']) ?></td><td><span class="badge <?= $row['status_karyawan'] === 'Resign' ? 'bg-danger' : ($row['status_karyawan'] === 'Kontrak' ? 'bg-info' : 'bg-success') ?>"><?= e($row['status_karyawan']) ?></span></td><td><?= e($row['tanggal_masuk']) ?></td><td><a class="btn btn-sm btn-warning" href="?edit=<?= $row['id_karyawan'] ?>">Edit</a> <?php if($row['status_karyawan'] !== 'Resign'): ?><form class="d-inline" method="post" onsubmit="return confirm('Apakah Anda yakin karyawan ini telah resign?')"><input type="hidden" name="id_karyawan" value="<?= $row['id_karyawan'] ?>"><button name="resign" class="btn btn-sm btn-danger">Resign</button></form><?php endif; ?></td></tr><?php endwhile;endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
