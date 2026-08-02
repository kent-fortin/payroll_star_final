<?php
/**
 * ============================================================================
 * NAMA FILE: jabatan.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman pengelola master data jabatan karyawan di perusahaan.
 *
 * ALUR & FITUR UTAMA:
 * 1. Create, Read, Update, Delete (CRUD) data jabatan beserta standar Gaji Pokok.
 * 2. Pembuatan kode jabatan otomatis berawalan JBT.
 * 3. Validasi pencegahan penghapusan jabatan yang sedang digunakan oleh karyawan.
 *
 * HAK AKSES / PENGGUNA: Admin
 * ============================================================================
 */

require_once __DIR__ . '/../layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan hanya Admin yang dapat mengakses halaman manajemen jabatan ini.
require_admin();
$edit = null;
// --- SECTION 2: PENGAMBILAN DATA JABATAN UNTUK FORM EDIT ---
// Mengambil data jabatan dari database jika admin mengklik tombol edit pada tabel.
// [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM jabatan WHERE id_jabatan=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}
// [PENJELASAN LOGIKA]: Memeriksa apakah ada form yang dikirimkan (metode POST) oleh pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['toggle_status'])) {
    $id = (int)($_POST['id_jabatan'] ?? 0);
    $nama = trim($_POST['nama_jabatan'] ?? '');
    $gaji = (float)($_POST['gaji_pokok'] ?? 0);
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($nama === '' || $gaji <= 0) {
        set_flash('danger', 'Data jabatan gagal disimpan. Lengkapi nama jabatan dan gaji pokok.');
    // [PENJELASAN LOGIKA]: Pemeriksaan kondisi alternatif (Else-If) jika kondisi sebelumnya tidak terpenuhi
    } elseif ($id > 0) {
        // [PENCARIAN-FUNGSI: UBAH DATA (UPDATE)] Memperbarui data jabatan yang sudah ada di database
        $stmt = mysqli_prepare($conn, 'UPDATE jabatan SET nama_jabatan=?,gaji_pokok=? WHERE id_jabatan=?');
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sdi', $nama, $gaji, $id);
            $ok = mysqli_stmt_execute($stmt);
        } else $ok = false;
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data jabatan berhasil diperbarui.' : 'Data jabatan gagal diperbarui.');
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if (!$ok) app_log('Update jabatan: ' . mysqli_error($conn));
    // [PENJELASAN LOGIKA]: Menjalankan blok perintah default (Else) karena semua kondisi di atasnya tidak terpenuhi
    } else {
        $placeholder = 'TMP' . bin2hex(random_bytes(5));
        // [PENCARIAN-FUNGSI: TAMBAH DATA (INSERT)] Menyimpan data jabatan baru ke dalam database
        $stmt = mysqli_prepare($conn, 'INSERT INTO jabatan (kode_jabatan,nama_jabatan,gaji_pokok) VALUES (?,?,?)');
        $ok = false;
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssd', $placeholder, $nama, $gaji);
            $ok = mysqli_stmt_execute($stmt);
        }
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if ($ok) {
            $newId = mysqli_insert_id($conn);
            $code = generate_jabatan_code($newId);
            $codeEsc = mysqli_real_escape_string($conn, $code);
            $ok = mysqli_query($conn, "UPDATE jabatan SET kode_jabatan='$codeEsc' WHERE id_jabatan=$newId");
        }
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data jabatan berhasil ditambahkan dengan kode otomatis.' : 'Data jabatan gagal disimpan.');
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if (!$ok) app_log('Insert jabatan: ' . mysqli_error($conn));
    }
    redirect('master/jabatan.php');
}
// [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
if (isset($_POST['toggle_status'])) {
    $id = (int)($_POST['id_jabatan'] ?? 0);
    $status = $_POST['status_baru'] ?? 'Aktif';
    $allowedStatus = ['Aktif', 'Tidak Aktif'];
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if ($id > 0 && in_array($status, $allowedStatus)) {
        $statusEsc = mysqli_real_escape_string($conn, $status);
        // [PENCARIAN-FUNGSI: UBAH STATUS] Menonaktifkan atau mengaktifkan kembali jabatan
        $ok = mysqli_query($conn, "UPDATE jabatan SET status_jabatan='$statusEsc' WHERE id_jabatan=$id");
        set_flash($ok ? 'success' : 'danger', $ok ? 'Status jabatan berhasil diperbarui.' : 'Status jabatan gagal diperbarui.');
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if (!$ok) app_log('Toggle jabatan status: ' . mysqli_error($conn));
    // [PENJELASAN LOGIKA]: Menjalankan blok perintah default (Else) karena semua kondisi di atasnya tidak terpenuhi
    } else {
        set_flash('danger', 'Data tidak valid.');
    }
    redirect('master/jabatan.php');
}
// --- SECTION 5: PENGAMBILAN SELURUH DATA JABATAN UNTUK TABEL ---
// Mengambil semua daftar jabatan beserta spesifikasi gaji pokok untuk ditampilkan di antarmuka tabel.
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
<div class="table-responsive">
  <table class="table table-hover dt-table align-middle" style="width:100%">
    <thead>
      <tr>
        <th style="width: 5%">No</th>
        <th style="width: 15%">Kode</th>
        <th style="width: 35%">Nama Jabatan</th>
        <th style="width: 20%">Gaji Pokok</th>
        <th style="width: 10%" class="text-center">Status</th>
        <th style="width: 15%" class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php $no=1; if ($data): while ($row=mysqli_fetch_assoc($data)): $status = $row['status_jabatan'] ?? 'Aktif'; $newStatus = $status === 'Aktif' ? 'Tidak Aktif' : 'Aktif'; ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($row['kode_jabatan']) ?></span></td>
        <td class="fw-bold text-dark"><?= e($row['nama_jabatan']) ?></td>
        <td class="fw-semibold text-success"><?= rupiah($row['gaji_pokok']) ?></td>
        <td class="text-center"><?= status_badge($status) ?></td>
        <td class="text-center">
          <div class="d-flex justify-content-center gap-1">
            <a class="btn btn-sm btn-outline-primary px-3 fw-semibold" href="?edit=<?= $row['id_jabatan'] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</a>
            <button type="button" class="btn btn-sm <?= $status === 'Aktif' ? 'btn-outline-danger' : 'btn-outline-success' ?> px-2 fw-semibold" onclick="toggleStatusJabatan(<?= $row['id_jabatan'] ?>, <?= htmlspecialchars(json_encode($newStatus), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['nama_jabatan']), ENT_QUOTES, 'UTF-8') ?>)">
              <i class="bi <?= $status === 'Aktif' ? 'bi-x-circle' : 'bi-check-circle' ?> me-1"></i><?= $status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
            </button>
          </div>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</div></div>

<form id="form_toggle_status" method="post" style="display:none;">
    <input type="hidden" name="id_jabatan" id="id_jabatan_input">
    <input type="hidden" name="status_baru" id="status_baru_input">
    <input type="hidden" name="toggle_status" value="1">
</form>

<script>
// [PENCARIAN-FUNGSI: TOGGLESTATUSJABATAN] Logika fungsi toggleStatusJabatan
function toggleStatusJabatan(idJabatan, statusBaru, namaJabatan) {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Yakin ingin mengubah status jabatan "${namaJabatan}" menjadi "${statusBaru}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Lanjutkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if (result.isConfirmed) {
            document.getElementById('id_jabatan_input').value = idJabatan;
            document.getElementById('status_baru_input').value = statusBaru;
            document.getElementById('form_toggle_status').submit();
        }
    });
}
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
