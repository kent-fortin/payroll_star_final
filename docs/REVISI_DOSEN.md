# Ringkasan Revisi Dosen

## Ruang Lingkup Absensi
Data absensi yang dikelola dalam aplikasi merupakan rekapitulasi absensi bulanan setiap karyawan yang meliputi jumlah hadir, sakit, izin, alpha, dan jam lembur. Aplikasi tidak mencatat presensi harian, jam masuk, jam keluar, maupun lokasi presensi karyawan. Perubahan terhadap data rekapitulasi absensi yang telah disimpan harus melalui proses pengajuan oleh admin dan persetujuan pimpinan.

## Rumus Payroll
- Total lembur = jumlah jam lembur × Rp15.000.
- Potongan alpha = jumlah hari alpha × Rp25.000.
- Gaji bersih = gaji pokok + total lembur − potongan alpha.

## Use Case Admin
1. Mengelola data jabatan, termasuk menambah, melihat, mengedit, dan menghapus data.
2. Mengelola data karyawan, termasuk menambah, melihat, mengedit, dan menghapus data.
3. Mengelola rekap absensi bulanan.
4. Mengajukan edit rekap absensi kepada pimpinan.
5. Mengelola payroll dan status pembayaran.
6. Mengelola laporan gaji.
7. Mencetak rincian perhitungan gaji dan laporan.

## Use Case Pimpinan
1. Melihat dashboard.
2. Menyetujui atau menolak pengajuan edit absensi.
3. Mengelola laporan gaji.
4. Mencetak laporan.

## Alternative Flow Lupa Password
Pengguna memilih Lupa Password, memasukkan username, email, atau nomor WhatsApp, memperoleh kode OTP uji coba, memverifikasi OTP, kemudian menetapkan password baru.
