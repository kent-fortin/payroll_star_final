<?php
/**
 * ============================================================================
 * NAMA FILE: seed_dummy.php
 * ============================================================================
 * TUJUAN & FUNGSI FILE:
 * Skrip otomatis untuk mengisi database dengan data sampel (dummy data) yang realistis.
 *
 * ALUR & FITUR UTAMA:
 * 1. Memasukkan 5 karyawan dummy (NIP SSL001 - SSL005) beserta jabatannya.
 * 2. Memasukkan data presensi hari ini, rekap absensi bulanan, lembur, dan payroll.
 * 3. Memasukkan data sampel riwayat edit absensi untuk menguji fitur approval.
 *
 * HAK AKSES / PENGGUNA: Administrator / Maintenance
 * ============================================================================
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../helpers/functions.php';

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error() . PHP_EOL);
}

echo "=========================================================\n";
echo "   SEEDING DUMMY DATA PAYROLL STAR (COMPREHENSIVE)\n";
echo "=========================================================\n\n";

echo "[1] Membersihkan data transaksi dan karyawan (Reset NIP ke SSL001)...\n";
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
$tables = ['permintaan_edit_absensi', 'payroll', 'lembur', 'presensi_harian', 'absensi', 'karyawan'];
foreach ($tables as $tbl) {
    mysqli_query($conn, "TRUNCATE TABLE `$tbl`");
    mysqli_query($conn, "ALTER TABLE `$tbl` AUTO_INCREMENT = 1");
}
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
echo "    -> Bersih!\n\n";

echo "[2] Memastikan Master Jabatan & Pengaturan Payroll ada...\n";
// Cek jabatan
$qJab = mysqli_query($conn, "SELECT COUNT(*) c FROM jabatan");
if (mysqli_fetch_assoc($qJab)['c'] == 0) {
    mysqli_query($conn, "INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `status_jabatan`) VALUES
    ('1', 'JBT001', 'Manager Operasional', '8000000.00', 'Tidak Aktif'),
    ('2', 'JBT002', 'Supervisor Administrasi', '6500000.00', 'Aktif'),
    ('3', 'JBT003', 'Staff Administrasi', '4500000.00', 'Aktif'),
    ('4', 'JBT004', 'Staff Keuangan', '5000000.00', 'Aktif'),
    ('5', 'JBT005', 'Driver Logistik', '4000000.00', 'Aktif'),
    ('6', 'JBT006', 'Staff Gudang', '3800000.00', 'Aktif')");
}
// Cek pengaturan
$qPeng = mysqli_query($conn, "SELECT COUNT(*) c FROM pengaturan_payroll");
if (mysqli_fetch_assoc($qPeng)['c'] == 0) {
    mysqli_query($conn, "INSERT INTO `pengaturan_payroll` (`id_pengaturan`, `nama_pengaturan`, `nilai`, `keterangan`) VALUES
    ('1', 'tarif_lembur_per_jam', '15000.00', 'Tarif lembur setiap satu jam'),
    ('2', 'potongan_alpha_per_hari', '25000.00', 'Potongan untuk setiap satu hari alpha'),
    ('3', 'total_hari_kerja', '26.00', 'Standar total hari kerja dalam sebulan')");
}
echo "    -> Siap!\n\n";

echo "[3] Menambahkan 5 Data Karyawan Dummy (NIP SSL001 - SSL005)...\n";
$karyawanList = [
    ['Budi Santoso', 'L', 2, 'Tetap', '2023-01-15', '3171012301900001', '3171010101900001'],
    ['Siti Aminah', 'P', 3, 'Tetap', '2023-05-10', '3171021005930002', '3171020101930002'],
    ['Ahmad Fauzi', 'L', 4, 'Tetap', '2024-02-01', '3171031502950003', '3171030101950003'],
    ['Dewi Lestari', 'P', 5, 'Kontrak', '2024-06-20', '3171042006980004', '3171040101980004'],
    ['Eko Prasetyo', 'L', 6, 'Tetap', '2025-01-05', '3171050501990005', '3171050101990005']
];

$idKaryawanMap = [];
foreach ($karyawanList as $idx => $k) {
    $stmt = mysqli_prepare($conn, "INSERT INTO karyawan (nip, nama_karyawan, jenis_kelamin, id_jabatan, status_karyawan, tanggal_masuk, no_ktp, no_kk) VALUES ('TMP', ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssissss', $k[0], $k[1], $k[2], $k[3], $k[4], $k[5], $k[6]);
    mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    $nip = generate_nip($newId);
    mysqli_query($conn, "UPDATE karyawan SET nip='$nip' WHERE id_karyawan=$newId");
    $idKaryawanMap[$idx + 1] = $newId;
    echo "    -> Inserted: $nip - {$k[0]}\n";
}
echo "\n";

echo "[4] Menambahkan Presensi Harian (Hari Ini: " . date('Y-m-d') . ") untuk Widget Dashboard...\n";
$today = date('Y-m-d');
$presensiHariIni = [
    [1, 'Hadir'],
    [2, 'Hadir'],
    [3, 'Sakit'],
    [4, 'Izin'],
    [5, 'Alpha']
];
foreach ($presensiHariIni as $pr) {
    $idK = $idKaryawanMap[$pr[0]];
    $st = $pr[1];
    mysqli_query($conn, "INSERT INTO presensi_harian (id_karyawan, tanggal, status_kehadiran) VALUES ($idK, '$today', '$st')");
}
echo "    -> 5 data kehadiran hari ini berhasil dimasukkan!\n\n";

echo "[5] Menambahkan Rekap Absensi Bulanan (" . current_month_name() . " " . date('Y') . ")...\n";
$bulan = current_month_name();
$tahun = (int)date('Y');
$absensiList = [
    [1, 24, 0, 0, 0], // Budi
    [2, 23, 1, 0, 0], // Siti
    [3, 22, 1, 0, 1], // Ahmad (Alpha 1)
    [4, 20, 0, 2, 2], // Dewi (Alpha 2)
    [5, 21, 0, 0, 3]  // Eko (Alpha 3)
];
$idAbsensiMap = [];
foreach ($absensiList as $idx => $ab) {
    $idK = $idKaryawanMap[$ab[0]];
    mysqli_query($conn, "INSERT INTO absensi (id_karyawan, bulan, tahun, hadir, sakit, izin, alpha, dibuat_oleh) VALUES ($idK, '$bulan', $tahun, {$ab[1]}, {$ab[2]}, {$ab[3]}, {$ab[4]}, 1)");
    $idAbsensiMap[$idx + 1] = mysqli_insert_id($conn);
}
echo "    -> 5 data rekap absensi berhasil dimasukkan!\n\n";

echo "[6] Menambahkan Data Lembur...\n";
$lemburList = [
    [1, '2026-07-10', 5],
    [1, '2026-07-15', 5], // Budi total 10 jam
    [2, '2026-07-12', 5], // Siti total 5 jam
    [5, '2026-07-18', 8]  // Eko total 8 jam
];
foreach ($lemburList as $lm) {
    $idK = $idKaryawanMap[$lm[0]];
    mysqli_query($conn, "INSERT INTO lembur (id_karyawan, tanggal_lembur, jam_lembur, dibuat_oleh) VALUES ($idK, '{$lm[1]}', {$lm[2]}, 1)");
}
echo "    -> 4 catatan lembur berhasil dimasukkan!\n\n";

echo "[7] Mengkalkulasi dan Memasukkan Data Payroll...\n";
// Budi (Disetujui & Sudah Dibayar)
$calc1 = calculate_payroll($conn, $idKaryawanMap[1], $bulan, $tahun, 500000);
if ($calc1) {
    mysqli_query($conn, "INSERT INTO payroll (id_karyawan, bulan, tahun, gaji_pokok, jam_lembur, tarif_lembur, total_lembur, total_tunjangan, jumlah_alpha, tarif_alpha, total_potongan_alpha, total_gaji_bersih, status_pembayaran, status_validasi, tanggal_pembayaran, diproses_oleh) VALUES ({$idKaryawanMap[1]}, '$bulan', $tahun, {$calc1['gaji_pokok']}, {$calc1['lembur_jam']}, {$calc1['tarif_lembur']}, {$calc1['total_lembur']}, 500000, 0, {$calc1['tarif_alpha']}, 0, {$calc1['gaji_bersih']}, 'Sudah Dibayar', 'Disetujui', CURDATE(), 1)");
    echo "    -> Payroll Budi Santoso (Disetujui & Sudah Dibayar)\n";
}
// Siti (Disetujui & Belum Dibayar)
$calc2 = calculate_payroll($conn, $idKaryawanMap[2], $bulan, $tahun, 250000);
if ($calc2) {
    mysqli_query($conn, "INSERT INTO payroll (id_karyawan, bulan, tahun, gaji_pokok, jam_lembur, tarif_lembur, total_lembur, total_tunjangan, jumlah_alpha, tarif_alpha, total_potongan_alpha, total_gaji_bersih, status_pembayaran, status_validasi, diproses_oleh) VALUES ({$idKaryawanMap[2]}, '$bulan', $tahun, {$calc2['gaji_pokok']}, {$calc2['lembur_jam']}, {$calc2['tarif_lembur']}, {$calc2['total_lembur']}, 250000, 0, {$calc2['tarif_alpha']}, 0, {$calc2['gaji_bersih']}, 'Belum Dibayar', 'Disetujui', 1)");
    echo "    -> Payroll Siti Aminah (Disetujui & Belum Dibayar)\n";
}
// Ahmad (Menunggu - Untuk tes Edit Tunjangan)
$calc3 = calculate_payroll($conn, $idKaryawanMap[3], $bulan, $tahun, 300000);
if ($calc3) {
    mysqli_query($conn, "INSERT INTO payroll (id_karyawan, bulan, tahun, gaji_pokok, jam_lembur, tarif_lembur, total_lembur, total_tunjangan, jumlah_alpha, tarif_alpha, total_potongan_alpha, total_gaji_bersih, status_pembayaran, status_validasi, diproses_oleh) VALUES ({$idKaryawanMap[3]}, '$bulan', $tahun, {$calc3['gaji_pokok']}, {$calc3['lembur_jam']}, {$calc3['tarif_lembur']}, {$calc3['total_lembur']}, 300000, 1, {$calc3['tarif_alpha']}, {$calc3['potongan_alpha']}, {$calc3['gaji_bersih']}, 'Belum Dibayar', 'Menunggu', 1)");
    echo "    -> Payroll Ahmad Fauzi (Menunggu Validasi - Bisa Edit Tunjangan)\n";
}
// Dewi (Menunggu - Untuk tes Hapus Payroll)
$calc4 = calculate_payroll($conn, $idKaryawanMap[4], $bulan, $tahun, 150000);
if ($calc4) {
    mysqli_query($conn, "INSERT INTO payroll (id_karyawan, bulan, tahun, gaji_pokok, jam_lembur, tarif_lembur, total_lembur, total_tunjangan, jumlah_alpha, tarif_alpha, total_potongan_alpha, total_gaji_bersih, status_pembayaran, status_validasi, diproses_oleh) VALUES ({$idKaryawanMap[4]}, '$bulan', $tahun, {$calc4['gaji_pokok']}, {$calc4['lembur_jam']}, {$calc4['tarif_lembur']}, {$calc4['total_lembur']}, 150000, 2, {$calc4['tarif_alpha']}, {$calc4['potongan_alpha']}, {$calc4['gaji_bersih']}, 'Belum Dibayar', 'Menunggu', 1)");
    echo "    -> Payroll Dewi Lestari (Menunggu Validasi)\n";
}
echo "\n";

echo "[8] Menambahkan Riwayat Pengajuan Edit Absensi (Termasuk Ditolak & Menunggu)...\n";
// Pengajuan 1: Ahmad Fauzi (Ditolak oleh Pimpinan - Ada Catatan)
$idAbsAhmad = $idAbsensiMap[3];
$catatanTolak = "Ditolak karena tidak melampirkan surat keterangan dokter resmi untuk klaim sakit.";
mysqli_query($conn, "INSERT INTO permintaan_edit_absensi (id_absensi, hadir_baru, sakit_baru, izin_baru, alpha_baru, alasan_perubahan, data_lama, status, id_pengaju, id_penyetuju, tanggal_pengajuan, tanggal_keputusan, catatan_pimpinan) VALUES
($idAbsAhmad, 23, 1, 0, 0, 'Mengubah 1 alpha menjadi hadir karena lupa absen datang', '{\"hadir\":22,\"sakit\":1,\"izin\":0,\"alpha\":1}', 'Ditolak', 1, 2, NOW(), NOW(), '$catatanTolak')");
echo "    -> Pengajuan Edit Absensi Ahmad Fauzi (Status: Ditolak - Untuk alert & riwayat)\n";

// Pengajuan 2: Dewi Lestari (Menunggu Persetujuan - Untuk tes Pimpinan menolak dengan catatan)
$idAbsDewi = $idAbsensiMap[4];
mysqli_query($conn, "INSERT INTO permintaan_edit_absensi (id_absensi, hadir_baru, sakit_baru, izin_baru, alpha_baru, alasan_perubahan, data_lama, status, id_pengaju, tanggal_pengajuan) VALUES
($idAbsDewi, 21, 0, 2, 1, 'Koreksi 1 hari alpha menjadi hadir karena ada tugas luar kantor', '{\"hadir\":20,\"sakit\":0,\"izin\":2,\"alpha\":2}', 'Menunggu', 1, NOW())");
echo "    -> Pengajuan Edit Absensi Dewi Lestari (Status: Menunggu - Untuk tes persetujuan Pimpinan)\n\n";

echo "=========================================================\n";
echo "   SEEDING DUMMY DATA BERHASIL DISLESAIKAN!\n";
echo "=========================================================\n";
