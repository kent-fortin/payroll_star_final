<?php
require_once __DIR__ . '/../layout/header.php';
require_pimpinan();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keputusan'])) {
    $id = (int)($_POST['id_payroll'] ?? 0);
    $keputusan = $_POST['keputusan'] === 'setujui' ? 'Disetujui' : 'Ditolak';
    
    $statusEsc = mysqli_real_escape_string($conn, $keputusan);
    $ok = mysqli_query($conn, "UPDATE payroll SET status_validasi='$statusEsc' WHERE id_payroll=$id");
    
    set_flash($ok ? 'success' : 'danger', $ok ? 'Validasi payroll berhasil disimpan.' : 'Validasi payroll gagal disimpan.');
    redirect('approval/validasi_payroll.php');
}

$data = mysqli_query($conn, "SELECT p.*, k.nip, k.nama_karyawan, j.nama_jabatan 
FROM payroll p 
JOIN karyawan k ON k.id_karyawan = p.id_karyawan 
JOIN jabatan j ON j.id_jabatan = k.id_jabatan 
WHERE p.status_validasi = 'Menunggu' 
ORDER BY p.tahun DESC, FIELD(p.bulan,'Desember','November','Oktober','September','Agustus','Juli','Juni','Mei','April','Maret','Februari','Januari'), k.nama_karyawan");
?>
<div class="card p-4">
<div class="section-header">
  <i class="bi bi-check2-square"></i>
  <h2 class="h5">Validasi Payroll</h2>
</div>
<p class="section-desc">Pimpinan menyetujui atau menolak hitungan payroll yang telah diproses oleh Admin sebelum pembayaran dilakukan.</p>
<div class="table-responsive">
    <table class="table table-striped dt-table" style="width:100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Karyawan</th>
                <th>Periode</th>
                <th>Gaji Pokok</th>
                <th>Lembur</th>
                <th>Tunjangan</th>
                <th>Potongan Alpha</th>
                <th>Gaji Bersih</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
<?php $no = 1; if($data): while($row = mysqli_fetch_assoc($data)): ?>
<tr>
  <td><?= $no++ ?></td>
  <td><strong><?= e($row['nip']) ?></strong><br><?= e($row['nama_karyawan']) ?><br><span class="small text-muted"><?= e($row['nama_jabatan']) ?></span></td>
  <td><?= e($row['bulan'] . ' ' . $row['tahun']) ?></td>
  <td><?= rupiah($row['gaji_pokok']) ?></td>
  <td><?= $row['jam_lembur'] ?> × <?= rupiah($row['tarif_lembur']) ?><br><strong><?= rupiah($row['total_lembur']) ?></strong></td>
  <td><strong><?= rupiah($row['total_tunjangan'] ?? 0) ?></strong></td>
  <td><?= $row['jumlah_alpha'] ?> × <?= rupiah($row['tarif_alpha']) ?><br><strong><?= rupiah($row['total_potongan_alpha']) ?></strong></td>
  <td><strong><?= rupiah($row['total_gaji_bersih']) ?></strong></td>
  <td>
    <form method="post" class="d-grid gap-2">
      <input type="hidden" name="id_payroll" value="<?= $row['id_payroll'] ?>">
      <button name="keputusan" value="setujui" class="btn btn-sm btn-success">Setujui</button>
      <button name="keputusan" value="tolak" class="btn btn-sm btn-danger">Tolak</button>
    </form>
  </td>
</tr>
<?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
