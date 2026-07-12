<?php
require_once __DIR__ . '/../layout/header.php';
require_admin();

// ── HANDLE DELETE ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)($_POST['id_lembur'] ?? 0);
    $ok = mysqli_query($conn, "DELETE FROM lembur WHERE id_lembur=$id");
    set_flash($ok ? 'success' : 'danger', $ok ? 'Data lembur berhasil dihapus.' : 'Data lembur gagal dihapus.');
    redirect('master/lembur.php');
}

// ── HANDLE SAVE / UPDATE ─────────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM lembur WHERE id_lembur=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id           = (int)($_POST['id_lembur'] ?? 0);
    $idKaryawan   = (int)($_POST['id_karyawan'] ?? 0);
    $tanggal      = trim($_POST['tanggal_lembur'] ?? '');
    $jam          = max(0, (int)($_POST['jam_lembur'] ?? 0));
    $userId       = (int)$_SESSION['id_user'];

    if ($idKaryawan < 1 || $tanggal === '' || $jam < 1) {
        set_flash('danger', 'Data lembur gagal disimpan. Pilih karyawan, isi tanggal dan jumlah jam (min. 1).');
    } elseif ($id > 0) {
        // Update
        $stmt = mysqli_prepare($conn, 'UPDATE lembur SET id_karyawan=?,tanggal_lembur=?,jam_lembur=? WHERE id_lembur=?');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isii', $idKaryawan, $tanggal, $jam, $id);
            $ok = mysqli_stmt_execute($stmt);
        }
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data lembur berhasil diperbarui.' : 'Data lembur gagal diperbarui. ' . mysqli_error($conn));
        if (!$ok) app_log('Update lembur: ' . mysqli_error($conn));
    } else {
        // Insert
        $stmt = mysqli_prepare($conn, 'INSERT INTO lembur (id_karyawan,tanggal_lembur,jam_lembur,dibuat_oleh) VALUES (?,?,?,?)');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isii', $idKaryawan, $tanggal, $jam, $userId);
            $ok = mysqli_stmt_execute($stmt);
        }
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data lembur berhasil disimpan.' : 'Data lembur gagal disimpan. ' . mysqli_error($conn));
        if (!$ok) app_log('Insert lembur: ' . mysqli_error($conn));
    }
    redirect('master/lembur.php');
}

// ── QUERIES ──────────────────────────────────────────────────────────────────
$karyawan = mysqli_query($conn, "SELECT id_karyawan,nip,nama_karyawan FROM karyawan WHERE status_karyawan IN ('Tetap','Kontrak') ORDER BY nama_karyawan");
$tarifLembur = get_setting($conn, 'tarif_lembur_per_jam', 15000);

// Data lembur: join karyawan, order by tanggal DESC
$data = mysqli_query($conn, "SELECT l.*, k.nip, k.nama_karyawan, j.nama_jabatan
    FROM lembur l
    JOIN karyawan k ON k.id_karyawan = l.id_karyawan
    JOIN jabatan j ON j.id_jabatan = k.id_jabatan
    ORDER BY l.tanggal_lembur DESC, k.nama_karyawan");
?>
<div class="card p-4 mb-4">
<div class="section-header">
  <i class="bi bi-clock-history"></i>
  <h2 class="h5"><?= $edit ? 'Edit Data Lembur' : 'Tambah Data Lembur Harian' ?></h2>
</div>
<p class="section-desc">Catat jam lembur harian karyawan. Data ini akan otomatis dihitung saat proses payroll.</p>
<form method="post" class="row g-3">
<input type="hidden" name="id_lembur" value="<?= e($edit['id_lembur'] ?? '') ?>">
<div class="col-md-4">
  <label class="form-label">Karyawan <span class="text-danger">*</span></label>
  <select name="id_karyawan" class="form-select" required>
    <option value="">— Pilih Karyawan —</option>
    <?php if($karyawan): while($k=mysqli_fetch_assoc($karyawan)): ?>
    <option value="<?= $k['id_karyawan'] ?>" <?= (int)($edit['id_karyawan']??0)===(int)$k['id_karyawan']?'selected':'' ?>>
      <?= e($k['nip'].' - '.$k['nama_karyawan']) ?>
    </option>
    <?php endwhile; endif; ?>
  </select>
</div>
<div class="col-md-3">
  <label class="form-label">Tanggal Lembur <span class="text-danger">*</span></label>
  <input type="date" name="tanggal_lembur" class="form-control"
    value="<?= e($edit['tanggal_lembur'] ?? date('Y-m-d')) ?>"
    max="<?= date('Y-m-d') ?>" required>
</div>
<div class="col-md-2">
  <label class="form-label">Jumlah Jam <span class="text-danger">*</span></label>
  <input type="number" name="jam_lembur" min="1" max="12" class="form-control"
    value="<?= e($edit['jam_lembur'] ?? '') ?>" required placeholder="Contoh: 3">
  <div class="form-text">@ <?= rupiah($tarifLembur) ?>/jam</div>
</div>
<div class="col-12 mt-4 pt-3 border-top">
  <button name="simpan" class="btn btn-primary px-5">
    <?= $edit ? 'Update Data' : 'Simpan Data' ?>
  </button>
  <?php if($edit): ?>
  <a href="<?= url('master/lembur.php') ?>" class="btn btn-secondary px-4 ms-2">Batal</a>
  <?php endif; ?>
</div>
</form>
</div>

<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Lembur Harian</h2>
</div>
<div class="table-responsive">
<table class="table table-striped dt-table" style="width:100%">
<thead><tr>
  <th>No</th><th>NIP</th><th>Nama Karyawan</th><th>Jabatan</th>
  <th>Tanggal Lembur</th><th>Jam</th><th>Nilai Lembur</th><th>Aksi</th>
</tr></thead>
<tbody>
<?php
$no=1;
if($data): while($row=mysqli_fetch_assoc($data)):
  $nilai = (int)$row['jam_lembur'] * $tarifLembur;
?>
<tr>
  <td><?= $no++ ?></td>
  <td><?= e($row['nip']) ?></td>
  <td><?= e($row['nama_karyawan']) ?></td>
  <td><?= e($row['nama_jabatan']) ?></td>
  <td><?= e(date('d M Y', strtotime($row['tanggal_lembur']))) ?></td>
  <td><span class="badge bg-info text-dark"><?= (int)$row['jam_lembur'] ?> jam</span></td>
  <td><strong class="text-success"><?= rupiah($nilai) ?></strong></td>
  <td>
    <a class="btn btn-sm btn-warning" href="?edit=<?= $row['id_lembur'] ?>">Edit</a>
    <form class="d-inline hapus-form" method="post" data-confirm="Hapus data lembur ini?">
      <input type="hidden" name="id_lembur" value="<?= $row['id_lembur'] ?>">
      <input type="hidden" name="hapus" value="1">
      <button type="button" class="btn btn-sm btn-danger btn-hapus">Hapus</button>
    </form>
  </td>
</tr>
<?php endwhile; endif; ?>

</tbody>
</table>
</div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
