<?php
/**
 * ============================================================================
 * NAMA FILE: lembur.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman pencatatan dan pengelolaan aktivitas lembur karyawan.
 *
 * ALUR & FITUR UTAMA:
 * 1. Admin mencatat tanggal dan jumlah jam lembur per karyawan.
 * 2. Akumulasi jam lembur bulanan otomatis dikalikan tarif lembur saat proses hitung payroll.
 * 3. Fitur pencarian, edit, dan hapus catatan lembur.
 *
 * HAK AKSES / PENGGUNA: Admin
 * ============================================================================
 */

require_once __DIR__ . '/../layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan pengguna berhak mengelola catatan lembur karyawan.
require_admin();

// ── HANDLE DELETE ────────────────────────────────────────────────────────────
// [PENJELASAN LOGIKA]: Memeriksa apakah ada form yang dikirimkan (metode POST) oleh pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)($_POST['id_lembur'] ?? 0);
    // [PENCARIAN-FUNGSI: HAPUS DATA (DELETE)] Menghapus record lembur berdasarkan ID
    $ok = mysqli_query($conn, "DELETE FROM lembur WHERE id_lembur=$id");
    set_flash($ok ? 'success' : 'danger', $ok ? 'Data lembur berhasil dihapus.' : 'Data lembur gagal dihapus.');
    redirect('master/lembur.php');
}

// ── HANDLE SAVE / UPDATE ─────────────────────────────────────────────────────
$edit = null;
// [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM lembur WHERE id_lembur=$id LIMIT 1");
    $edit = $result ? mysqli_fetch_assoc($result) : null;
}

// [PENJELASAN LOGIKA]: Memeriksa apakah ada form yang dikirimkan (metode POST) oleh pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id           = (int)($_POST['id_lembur'] ?? 0);
    $idKaryawan   = (int)($_POST['id_karyawan'] ?? 0);
    $tanggal      = trim($_POST['tanggal_lembur'] ?? '');
    $jam          = max(0, (int)($_POST['jam_lembur'] ?? 0));
    $userId       = (int)$_SESSION['id_user'];

    // --- VALIDASI BLOKIR LEMBUR JIKA SUDAH DIBAYAR ---
    $isValid = true;
    if ($idKaryawan > 0 && $tanggal !== '') {
        $bulanNomor = (int)date('n', strtotime($tanggal));
        $tahunLembur = (int)date('Y', strtotime($tanggal));
        $namaBulan = bulan_list()[$bulanNomor];
        $namaBulanEsc = mysqli_real_escape_string($conn, $namaBulan);
        
        $cekPayroll = mysqli_query($conn, "SELECT status_pembayaran FROM payroll WHERE id_karyawan=$idKaryawan AND bulan='$namaBulanEsc' AND tahun=$tahunLembur LIMIT 1");
        $rowPayroll = $cekPayroll ? mysqli_fetch_assoc($cekPayroll) : null;
        
        if ($rowPayroll && $rowPayroll['status_pembayaran'] === 'Sudah Dibayar') {
            set_flash('danger', "Gagal: Gaji bulan $namaBulan $tahunLembur sudah dibayar. Tidak dapat menambah/mengubah lembur di periode ini.");
            $isValid = false;
        }
    }

    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if (!$isValid) {
        // flash dipasang di atas
    } elseif ($idKaryawan < 1 || $tanggal === '' || $jam < 1) {
        set_flash('danger', 'Data lembur gagal disimpan. Pilih karyawan, isi tanggal dan jumlah jam (min. 1).');
    // [PENJELASAN LOGIKA]: Pemeriksaan kondisi alternatif (Else-If) jika kondisi sebelumnya tidak terpenuhi
    } elseif ($id > 0) {
        // [PENCARIAN-FUNGSI: UBAH DATA (UPDATE)] Memperbarui durasi jam lembur yang sudah ada
        $stmt = mysqli_prepare($conn, 'UPDATE lembur SET id_karyawan=?,tanggal_lembur=?,jam_lembur=? WHERE id_lembur=?');
        $ok = false;
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isii', $idKaryawan, $tanggal, $jam, $id);
            $ok = mysqli_stmt_execute($stmt);
        }
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data lembur berhasil diperbarui.' : 'Data lembur gagal diperbarui. ' . mysqli_error($conn));
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if (!$ok) app_log('Update lembur: ' . mysqli_error($conn));
    // [PENJELASAN LOGIKA]: Menjalankan blok perintah default (Else) karena semua kondisi di atasnya tidak terpenuhi
    } else {
        // Insert
        $stmt = mysqli_prepare($conn, 'INSERT INTO lembur (id_karyawan,tanggal_lembur,jam_lembur,dibuat_oleh) VALUES (?,?,?,?)');
        $ok = false;
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isii', $idKaryawan, $tanggal, $jam, $userId);
            $ok = mysqli_stmt_execute($stmt);
        }
        set_flash($ok ? 'success' : 'danger', $ok ? 'Data lembur berhasil disimpan.' : 'Data lembur gagal disimpan. ' . mysqli_error($conn));
        // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
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
  <table class="table table-hover dt-table align-middle" style="width:100%">
    <thead>
      <tr>
        <th style="width: 5%">No</th>
        <th style="width: 12%">NIP</th>
        <th style="width: 25%">Nama Karyawan</th>
        <th style="width: 18%">Jabatan</th>
        <th style="width: 12%">Tanggal Lembur</th>
        <th style="width: 8%" class="text-center">Jam</th>
        <th style="width: 12%">Nilai Lembur</th>
        <th style="width: 8%" class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $no=1;
    // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
    if($data): while($row=mysqli_fetch_assoc($data)):
      $nilai = (int)$row['jam_lembur'] * $tarifLembur;
    ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= e($row['nip']) ?></span></td>
        <td class="fw-bold text-dark"><?= e($row['nama_karyawan']) ?></td>
        <td><?= e($row['nama_jabatan']) ?></td>
        <td class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= e(date('d M Y', strtotime($row['tanggal_lembur']))) ?></td>
        <td class="text-center"><span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><?= (int)$row['jam_lembur'] ?> jam</span></td>
        <td><strong class="text-success"><?= rupiah($nilai) ?></strong></td>
        <td class="text-center">
          <div class="d-flex justify-content-center gap-1">
            <a class="btn btn-sm btn-outline-primary px-3 fw-semibold" href="?edit=<?= $row['id_lembur'] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</a>
            <form class="d-inline hapus-form" method="post" data-confirm="Hapus data lembur ini?">
              <input type="hidden" name="id_lembur" value="<?= $row['id_lembur'] ?>">
              <input type="hidden" name="hapus" value="1">
              <button type="button" class="btn btn-sm btn-outline-danger px-2 fw-semibold btn-hapus"><i class="bi bi-trash me-1"></i>Hapus</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
