<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM jabatan WHERE id_jabatan=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['toggle_status'])) {
    $id = (int)($_POST['id_jabatan'] ?? 0);
    $nama = trim($_POST['nama_jabatan'] ?? '');
    $gaji = (float)($_POST['gaji_pokok'] ?? 0);
    if ($nama === '' || $gaji <= 0) {
        set_flash('danger', 'Data jabatan gagal disimpan. Lengkapi nama jabatan dan gaji pokok.');
    } elseif ($id > 0) {
        $stmt = mysqli_prepare($conn, 'UPDATE jabatan SET nama_jabatan=?,gaji_pokok=? WHERE id_jabatan=?');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sdi', $nama, $gaji, $id);
            $ok = mysqli_stmt_execute($stmt);
        } else $ok = false;
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data jabatan berhasil diperbarui.' : 'Data jabatan gagal diperbarui.');
        if (!$ok) app_log('Update jabatan: ' . mysqli_error($conn));
    } else {
        $placeholder = 'TMP' . bin2hex(random_bytes(5));
        $stmt = mysqli_prepare($conn, 'INSERT INTO jabatan (kode_jabatan,nama_jabatan,gaji_pokok) VALUES (?,?,?)');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssd', $placeholder, $nama, $gaji);
            $ok = mysqli_stmt_execute($stmt);
        }
        if ($ok) {
            $newId = mysqli_insert_id($conn);
            $code = generate_jabatan_code($newId);
            $codeEsc = mysqli_real_escape_string($conn, $code);
            $ok = mysqli_query($conn, "UPDATE jabatan SET kode_jabatan='$codeEsc' WHERE id_jabatan=$newId");
        }
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data jabatan berhasil ditambahkan dengan kode otomatis.' : 'Data jabatan gagal disimpan.');
        if (!$ok) app_log('Insert jabatan: ' . mysqli_error($conn));
    }
    redirect('master/jabatan.php');
}
if (isset($_POST['toggle_status'])) {
    $id = (int)($_POST['id_jabatan'] ?? 0);
    $status = $_POST['status_baru'] ?? 'Aktif';
    $allowedStatus = ['Aktif', 'Tidak Aktif'];
    if ($id > 0 && in_array($status, $allowedStatus)) {
        $statusEsc = mysqli_real_escape_string($conn, $status);
        $ok = mysqli_query($conn, "UPDATE jabatan SET status_jabatan='$statusEsc' WHERE id_jabatan=$id");
        set_flash($ok ? 'success' : 'danger', $ok ? 'Status jabatan berhasil diperbarui.' : 'Status jabatan gagal diperbarui.');
        if (!$ok) app_log('Toggle jabatan status: ' . mysqli_error($conn));
    } else {
        set_flash('danger', 'Data tidak valid.');
    }
    redirect('master/jabatan.php');
}
$data = mysqli_query($conn, 'SELECT * FROM jabatan ORDER BY id_jabatan');
?>
<div class="card p-4 mb-4">
<div class="section-header">
  <i class="bi bi-tag"></i>
  <h2 class="h5"><?= $edit ? 'Edit Jabatan' : 'Tambah Jabatan' ?></h2>
</div>
<form method="post" class="row g-3">
<input type="hidden" name="id_jabatan" value="<?= e($edit['id_jabatan'] ?? '') ?>">
<div class="col-md-3"><label class="form-label">Kode Jabatan</label><input class="form-control" value="<?= e($edit['kode_jabatan'] ?? 'Dibuat otomatis') ?>" readonly></div>
<div class="col-md-5"><label class="form-label">Nama Jabatan</label><input name="nama_jabatan" class="form-control" value="<?= e($edit['nama_jabatan'] ?? '') ?>" required></div>
<div class="col-md-3"><label class="form-label">Gaji Pokok</label><input type="number" min="1" name="gaji_pokok" class="form-control" value="<?= e($edit['gaji_pokok'] ?? '') ?>" required></div>
<div class="col-12 mt-4 pt-3 border-top"><button class="btn btn-primary px-5"><?= $edit ? 'Update Data' : 'Simpan Data' ?></button> <?php if ($edit): ?><a href="<?= url('master/jabatan.php') ?>" class="btn btn-secondary px-4 ms-2">Batal</a><?php endif; ?></div>
</form></div>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Jabatan</h2>
</div>
<div class="table-responsive"><table class="table table-striped dt-table" style="width:100%"><thead><tr><th>No</th><th>Kode</th><th>Nama Jabatan</th><th>Gaji Pokok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php $no=1; if ($data): while ($row=mysqli_fetch_assoc($data)): $status = $row['status_jabatan'] ?? 'Aktif'; $newStatus = $status === 'Aktif' ? 'Tidak Aktif' : 'Aktif'; ?><tr><td><?= $no++ ?></td><td><?= e($row['kode_jabatan']) ?></td><td><?= e($row['nama_jabatan']) ?></td><td><?= rupiah($row['gaji_pokok']) ?></td><td><span class="badge <?= $status === 'Aktif' ? 'bg-success' : 'bg-secondary' ?>"><?= e($status) ?></span></td><td><a class="btn btn-sm btn-warning" href="?edit=<?= $row['id_jabatan'] ?>">Edit</a> <button type="button" class="btn btn-sm <?= $status === 'Aktif' ? 'btn-danger' : 'btn-success' ?>" onclick="toggleStatusJabatan(<?= $row['id_jabatan'] ?>, <?= json_encode($newStatus) ?>, <?= json_encode($row['nama_jabatan']) ?>)"><?= $status === 'Aktif' ? 'Tidak Aktif' : 'Aktifkan' ?></button></td></tr><?php endwhile; endif; ?>
</tbody></table></div></div>

<form id="form_toggle_status" method="post" style="display:none;">
    <input type="hidden" name="id_jabatan" id="id_jabatan_input">
    <input type="hidden" name="status_baru" id="status_baru_input">
    <input type="hidden" name="toggle_status" value="1">
</form>

<script>
function toggleStatusJabatan(idJabatan, statusBaru, namaJabatan) {
    let konfirmasi = confirm(`Yakin ingin mengubah status jabatan "${namaJabatan}" menjadi "${statusBaru}"?`);
    if (konfirmasi) {
        document.getElementById('id_jabatan_input').value = idJabatan;
        document.getElementById('status_baru_input').value = statusBaru;
        document.getElementById('form_toggle_status').submit();
    }
}
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
