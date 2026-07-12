<?php
require_once __DIR__ . '/config/koneksi.php';

echo "Memeriksa database...\n";

// 1. Tambah status_jabatan di jabatan
$q = mysqli_query($conn, "SHOW COLUMNS FROM jabatan LIKE 'status_jabatan'");
if (mysqli_num_rows($q) == 0) {
    mysqli_query($conn, "ALTER TABLE jabatan ADD status_jabatan ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif'");
    echo "Added status_jabatan to jabatan\n";
}

// 2. Ubah enum status_karyawan
mysqli_query($conn, "ALTER TABLE karyawan MODIFY status_karyawan ENUM('Tetap','Kontrak','Resign') NOT NULL");
echo "Updated status_karyawan enum\n";

// 3. Drop lembur_jam dari absensi
$q = mysqli_query($conn, "SHOW COLUMNS FROM absensi LIKE 'lembur_jam'");
if (mysqli_num_rows($q) > 0) {
    mysqli_query($conn, "ALTER TABLE absensi DROP COLUMN lembur_jam");
    echo "Dropped lembur_jam from absensi\n";
}

// 4. Drop lembur_jam_baru dari permintaan_edit_absensi
$q = mysqli_query($conn, "SHOW COLUMNS FROM permintaan_edit_absensi LIKE 'lembur_jam_baru'");
if (mysqli_num_rows($q) > 0) {
    mysqli_query($conn, "ALTER TABLE permintaan_edit_absensi DROP COLUMN lembur_jam_baru");
    echo "Dropped lembur_jam_baru from permintaan_edit_absensi\n";
}

// 5. Tambah kolom di payroll
$q = mysqli_query($conn, "SHOW COLUMNS FROM payroll LIKE 'total_tunjangan'");
if (mysqli_num_rows($q) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll ADD total_tunjangan DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_lembur");
    echo "Added total_tunjangan to payroll\n";
}
$q = mysqli_query($conn, "SHOW COLUMNS FROM payroll LIKE 'status_validasi'");
if (mysqli_num_rows($q) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll ADD status_validasi ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu' AFTER status_pembayaran");
    echo "Added status_validasi to payroll\n";
}

// 6. Buat tabel lembur
$sql = "CREATE TABLE IF NOT EXISTS lembur (
    id_lembur INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    tanggal_lembur DATE NOT NULL,
    jam_lembur INT NOT NULL DEFAULT 0,
    dibuat_oleh INT NULL,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lembur_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lembur_user FOREIGN KEY (dibuat_oleh) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Data lembur harian karyawan'";
mysqli_query($conn, $sql);
if (mysqli_error($conn)) {
    echo "Error creating lembur: " . mysqli_error($conn) . "\n";
} else {
    echo "Created table lembur\n";
}

echo "Selesai.\n";
