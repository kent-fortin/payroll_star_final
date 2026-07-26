<?php
/**
 * ============================================================================
 * NAMA FILE: karyawan.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman pengelola master data SDM atau karyawan perusahaan.
 *
 * ALUR & FITUR UTAMA:
 * 1. Create, Read, Update, Delete (CRUD) data profil karyawan lengkap.
 * 2. Pembuatan Nomor Induk Pegawai (NIP) otomatis yang berurutan mulai dari SSL001.
 * 3. Pengaturan status karyawan (Tetap, Kontrak, Nonaktif) dan relasi ke jabatan.
 *
 * HAK AKSES / PENGGUNA: Admin
 * ============================================================================
 */

require_once __DIR__ . '/../layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan pengguna yang mengakses halaman ini adalah Admin yang sah.
require_admin();
$edit = null;
// --- SECTION 2: PENGAMBILAN DATA UNTUK FORM EDIT (GET REQUEST) ---
// Mengecek apakah ada parameter '?edit=ID' di URL untuk mengambil data karyawan yang akan diedit.
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}
// --- SECTION 3: PEMROSESAN FORM SIMPAN / UPDATE DATA (POST REQUEST) ---
// Menangani pengiriman form saat tombol 'Simpan' ditekan (tambah baru dengan NIP otomatis atau edit data lama).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id_karyawan'] ?? 0);
    $nama = trim($_POST['nama_karyawan'] ?? '');
    $jk = ($_POST['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L';
    $jabatan = (int)($_POST['id_jabatan'] ?? 0);
    $s = $_POST['status_karyawan'] ?? 'Tetap';
    $status = in_array($s, ['Tetap','Kontrak','Resign']) ? $s : 'Tetap';
    $tanggal = trim($_POST['tanggal_masuk'] ?? '');
    $noKtp = trim($_POST['no_ktp'] ?? '');
    $noKk = trim($_POST['no_kk'] ?? '');
    $today = date('Y-m-d');
    if ($nama === '' || $jabatan < 1 || $tanggal === '') {
        set_flash('danger', 'Data karyawan gagal disimpan. Lengkapi seluruh data wajib.');
    } elseif ($tanggal > $today) {
        set_flash('danger', 'Data karyawan gagal disimpan. Tanggal masuk tidak boleh lebih dari tanggal hari ini.');
    } elseif ($id > 0) {
        $stmt = mysqli_prepare($conn, 'UPDATE karyawan SET nama_karyawan=?,jenis_kelamin=?,id_jabatan=?,status_karyawan=?,tanggal_masuk=?,no_ktp=?,no_kk=? WHERE id_karyawan=?');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssissssi', $nama, $jk, $jabatan, $status, $tanggal, $noKtp, $noKk, $id);
            $ok = mysqli_stmt_execute($stmt);
        }
        set_flash($ok ? 'success':'danger', $ok ? 'Data karyawan berhasil diperbarui.':'Data karyawan gagal diperbarui.');
        if (!$ok) app_log('Update karyawan: '.mysqli_error($conn));
    } else {
        $placeholder = 'TMP' . bin2hex(random_bytes(5));
        $stmt = mysqli_prepare($conn, 'INSERT INTO karyawan (nip,nama_karyawan,jenis_kelamin,id_jabatan,status_karyawan,tanggal_masuk,no_ktp,no_kk) VALUES (?,?,?,?,?,?,?,?)');
        $ok = false;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sssissss', $placeholder, $nama, $jk, $jabatan, $status, $tanggal, $noKtp, $noKk);
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
// --- SECTION 4: PEMROSESAN AKSI RESIGN / NONAKTIF ---
// Mengubah status karyawan menjadi 'Resign' tanpa menghapus riwayat datanya di database.
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
<div class="col-md-3"><label class="form-label">No. KTP (Opsional)</label><input type="text" name="no_ktp" class="form-control" value="<?= e($edit['no_ktp']??'') ?>" placeholder="16 digit NIK"></div>
<div class="col-md-3"><label class="form-label">No. KK (Opsional)</label><input type="text" name="no_kk" class="form-control" value="<?= e($edit['no_kk']??'') ?>" placeholder="16 digit KK"></div>
<div class="col-12 mt-4 pt-3 border-top"><button name="simpan" class="btn btn-primary px-5"><?= $edit?'Update Data':'Simpan Data' ?></button> <?php if($edit): ?><a class="btn btn-secondary px-4 ms-2" href="<?= url('master/karyawan.php') ?>">Batal</a><?php endif; ?></div>
</form></div>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-table"></i>
  <h2 class="h5">Daftar Karyawan</h2>
</div>
<div class="table-responsive">
  <table class="table table-hover dt-table align-middle" style="width:100%">
    <thead>
      <tr>
        <th style="width: 5%">No</th>
        <th style="width: 12%">NIP</th>
        <th style="width: 25%">Nama</th>
        <th style="width: 5%" class="text-center">JK</th>
        <th style="width: 20%">Jabatan</th>
        <th style="width: 10%" class="text-center">Status</th>
        <th style="width: 10%">Tanggal Masuk</th>
        <th style="width: 13%" class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php $no=1;if($data):while($row=mysqli_fetch_assoc($data)): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($row['nip']) ?></span></td>
        <td class="fw-bold text-dark"><?= e($row['nama_karyawan']) ?></td>
        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= e($row['jenis_kelamin']) ?></span></td>
        <td><?= e($row['nama_jabatan']) ?></td>
        <td class="text-center"><span class="badge <?= $row['status_karyawan'] === 'Resign' ? 'bg-danger' : ($row['status_karyawan'] === 'Kontrak' ? 'bg-info text-dark' : 'bg-success') ?> px-2 py-1"><?= e($row['status_karyawan']) ?></span></td>
        <td class="small text-muted"><?= e(date('d-m-Y', strtotime($row['tanggal_masuk']))) ?></td>
        <td class="text-center">
          <div class="d-flex justify-content-center gap-1">
            <a class="btn btn-sm btn-outline-primary px-3 fw-semibold" href="?edit=<?= $row['id_karyawan'] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</a>
            <?php if($row['status_karyawan'] !== 'Resign'): ?>
              <form class="d-inline hapus-form" method="post" data-confirm="Apakah Anda yakin karyawan ini telah resign?">
                <input type="hidden" name="id_karyawan" value="<?= $row['id_karyawan'] ?>">
                <input type="hidden" name="resign" value="1">
                <button type="button" class="btn btn-sm btn-outline-danger px-2 fw-semibold btn-hapus"><i class="bi bi-person-x me-1"></i>Resign</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endwhile;endif; ?>
    </tbody>
  </table>
</div></div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
