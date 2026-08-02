<?php
/**
 * ============================================================================
 * NAMA FILE: validasi_payroll.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Halaman bagi Pimpinan untuk memeriksa, menyetujui, atau menolak perhitungan gaji (payroll) bulanan yang diproses oleh Admin.
 *
 * ALUR & FITUR UTAMA:
 * 1. Filter data payroll berdasarkan bulan dan tahun.
 * 2. Aksi persetujuan massal (atau per item) untuk memvalidasi payroll.
 * 3. Fitur tolak payroll agar Admin dapat menghitung ulang jika ada kesalahan.
 *
 * HAK AKSES / PENGGUNA: Pimpinan
 * ============================================================================
 */

require_once __DIR__ . '/../layout/header.php';
// --- SECTION 1: OTENTIKASI & KONTROL HAK AKSES ---
// Memastikan hanya Pimpinan yang berhak memvalidasi atau menolak payroll.
require_pimpinan();

// [PENJELASAN LOGIKA]: Memeriksa apakah ada form yang dikirimkan (metode POST) oleh pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keputusan'])) {
    $id = (int)($_POST['id_payroll'] ?? 0);
    $keputusan = $_POST['keputusan'] === 'setujui' ? 'Disetujui' : 'Ditolak';
    
    $statusEsc = mysqli_real_escape_string($conn, $keputusan);
    // [PENCARIAN-FUNGSI: UBAH STATUS] Memperbarui status validasi payroll menjadi Disetujui / Ditolak
    $ok = mysqli_query($conn, "UPDATE payroll SET status_validasi='$statusEsc' WHERE id_payroll=$id");
    
    set_flash($ok ? 'success' : 'danger', $ok ? 'Validasi payroll berhasil disimpan.' : 'Validasi payroll gagal disimpan.');
    redirect('approval/validasi_payroll.php');
}

// [PENCARIAN-FUNGSI: AMBIL DATA (SELECT) JOIN] Mengambil daftar payroll yang statusnya 'Menunggu' validasi
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
    <table class="table table-hover dt-table align-middle" style="width:100%">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 25%">Karyawan & Periode</th>
                <th style="width: 15%">Gaji Pokok</th>
                <th style="width: 18%">Lembur & Tunjangan</th>
                <th style="width: 14%">Potongan (Alpha)</th>
                <th style="width: 13%">Gaji Bersih</th>
                <th style="width: 10%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
<?php $no = 1; if($data): while($row = mysqli_fetch_assoc($data)): ?>
<tr>
  <td><?= $no++ ?></td>
  <td>
    <div class="fw-bold fs-6 text-dark"><?= e($row['nama_karyawan']) ?></div>
    <div class="small text-muted mb-1">NIP: <strong><?= e($row['nip']) ?></strong> | <?= e($row['nama_jabatan']) ?></div>
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-calendar3 me-1"></i><?= e($row['bulan'] . ' ' . $row['tahun']) ?></span>
  </td>
  <td><?= rupiah($row['gaji_pokok']) ?></td>
  <td class="small">
    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Lembur (<?= $row['jam_lembur'] ?>j):</span> <strong><?= rupiah($row['total_lembur']) ?></strong></div>
    <div class="d-flex justify-content-between"><span class="text-muted">Tunjangan:</span> <strong class="text-success"><?= rupiah($row['total_tunjangan'] ?? 0) ?></strong></div>
  </td>
  <td class="small">
    <div class="text-danger fw-bold"><?= rupiah($row['total_potongan_alpha']) ?></div>
    <div class="text-muted" style="font-size: 0.75rem;">(<?= $row['jumlah_alpha'] ?> hari Alpha × <?= rupiah($row['tarif_alpha']) ?>)</div>
  </td>
  <td><span class="fs-6 fw-bold text-primary"><?= rupiah($row['total_gaji_bersih']) ?></span></td>
  <td class="text-center">
    <form method="post" class="m-0">
      <input type="hidden" name="id_payroll" value="<?= $row['id_payroll'] ?>">
      <div class="d-flex justify-content-center gap-1">
        <button type="button" class="btn btn-sm btn-success fw-bold px-3 btn-setujui-payroll" title="Setujui Payroll"><i class="bi bi-check-lg me-1"></i>Setujui</button>
        <button type="button" class="btn btn-sm btn-danger fw-bold px-3 btn-tolak-payroll" title="Tolak Payroll"><i class="bi bi-x-lg me-1"></i>Tolak</button>
      </div>
    </form>
  </td>
</tr>
<?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-setujui-payroll').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = this.closest('form');
            Swal.fire({
                title: 'Setujui Payroll ini?',
                text: 'Perhitungan gaji bulan ini untuk karyawan terpilih akan disetujui dan siap dibayarkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Ya, Setujui',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
                if (result.isConfirmed) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'keputusan';
                    input.value = 'setujui';
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('.btn-tolak-payroll').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = this.closest('form');
            Swal.fire({
                title: 'Tolak Payroll ini?',
                text: 'Perhitungan gaji akan ditolak sehingga Admin dapat melakukan pengecekan atau perhitungan ulang.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-x-lg me-1"></i> Ya, Tolak',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // [PENJELASAN LOGIKA]: Melakukan pengecekan kondisi (If) untuk menentukan alur program yang akan dijalankan
                if (result.isConfirmed) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'keputusan';
                    input.value = 'tolak';
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    });
});
</script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
