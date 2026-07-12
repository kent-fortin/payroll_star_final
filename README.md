# Payroll PT Star Samudera Logistik - Revisi Dosen

Sistem ini adalah aplikasi Payroll (Penggajian) berbasis web yang telah direvisi dan disesuaikan dengan permintaan dosen penguji. 

## 🌟 Fitur Utama
1. **Otomatisasi Kode:** Kode Jabatan dan NIP (Nomor Induk Pegawai) dibuat otomatis oleh sistem (contoh: JBT001, SSL001).
2. **Rekap Absensi:** Sistem mengelola data *rekap bulanan* untuk absensi (bukan presensi harian).
3. **Approval (Persetujuan):**
   - **Edit Absensi:** Jika Admin mengajukan perubahan absensi, perubahan ini *wajib* disetujui oleh Pimpinan.
   - **Validasi Payroll:** Hasil hitungan payroll tidak dapat dicetak/dibayar sebelum Pimpinan melakukan validasi dan klik "Setujui".
4. **Rumus Penggajian Otomatis:**
   - Lembur dihitung per jam terpisah (Admin cukup menginput tanggal & jumlah jam).
   - Tarif Lembur: **Rp 15.000 / jam**
   - Potongan Alpha: **Rp 25.000 / hari**
   - Rumus Gaji Bersih: `Gaji Pokok + (Jam Lembur × Tarif) + Tunjangan - (Jumlah Alpha × Tarif)`
5. **Preview Gaji:** Admin dapat meninjau rincian hitungan secara real-time sebelum menyimpannya ke database.
6. **Laporan & Grafik Terintegrasi:** Laporan dilengkapi dengan *Grafik Chart* offline, dan bisa difilter (1 bulan, 2 bulan terakhir, 1 tahun terakhir).

## 🛠️ Cara Instalasi & Menjalankan Project (Local)
Sistem ini dibangun menggunakan PHP Native dan MySQL. Ada dua cara untuk menjalankannya di lokal Anda:

### Cara 1: Menggunakan XAMPP / Laragon (Disarankan)
1. Salin/pindahkan seluruh folder project ini (`payroll_star_final`) ke dalam direktori web server Anda:
   - Jika **XAMPP**: `C:\xampp\htdocs\`
   - Jika **Laragon**: `C:\laragon\www\`
2. Aktifkan modul **Apache** dan **MySQL** pada panel kontrol web server Anda.
3. Buka browser dan akses **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Buat database baru bernama: `db_payroll_star_samudera`.
5. Import file `database/database.sql` yang sudah disediakan ke dalam database tersebut.
6. Buka aplikasi di browser melalui URL: `http://localhost/payroll_star_final/`

### Cara 2: Menggunakan PHP Built-in Server (Tanpa memindahkan folder)
Jika Anda tidak ingin memindahkan folder dari lokasi saat ini (misal di Drive D), Anda bisa menggunakan built-in server bawaan PHP:
1. Pastikan **MySQL** sudah berjalan (bisa nyalakan MySQL dari XAMPP/Laragon).
2. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) dan import file `database/database.sql` ke database `db_payroll_star_samudera`.
3. Buka Terminal / Command Prompt / PowerShell di folder project ini (`payroll_star_final`).
4. Jalankan perintah berikut:
   ```bash
   php -S localhost:8000
   ```
5. Buka aplikasi di browser melalui URL: `http://localhost:8000/`

## 👤 Akun Demo
Sistem ini memisahkan hak akses antara Admin (HR/Keuangan) dan Pimpinan.
- **Admin**
  - Username: `admin`
  - Password: `admin123`
- **Pimpinan**
  - Username: `pimpinan`
  - Password: `pimpinan123`

## 📁 Struktur Direktori
Sistem ini mengadopsi pola MVC sederhana agar kode terstruktur dengan baik:
- `/approval/` : Modul Pimpinan untuk menyetujui pengajuan absensi dan memvalidasi hitungan payroll.
- `/assets/` : Menyimpan aset statis seperti CSS (termasuk style yang dikustomisasi), gambar, logo.
- `/auth/` : Modul otentikasi (login, logout, proses login).
- `/config/` : Pengaturan koneksi ke database.
- `/database/` : Menyimpan file `.sql` berisi skema dan dummy data.
- `/docs/` : Berisi dokumentasi atau catatan (*misal: catatan revisi*).
- `/helpers/` : Kumpulan fungsi penolong (`functions.php`) yang mempermudah pemanggilan fungsi berulang.
- `/laporan/` : Modul untuk menampilkan tabel dan grafik riwayat penggajian.
- `/layout/` : *Header* dan *Footer* global yang dipanggil di setiap halaman.
- `/master/` : Modul Admin untuk mengelola data inti (Karyawan, Jabatan, Absensi, Lembur).
- `/transaksi/` : Modul operasional utama untuk Admin, khususnya memproses `payroll.php` dan cetak rinciannya.

## 📝 Catatan Penting
- **Keamanan:** Setiap menu sudah dilindungi secara fungsional. Admin tidak bisa mengakses halaman persetujuan/laporan pimpinan, begitu pula sebaliknya pimpinan tidak bisa menginput/mengubah data pada master/transaksi.
- **Batalkan Bayar:** Admin dapat membatalkan status pembayaran gaji yang sudah ditandai. Namun perhitungan ulang (Update) hanya bisa dilakukan jika status payroll dikembalikan (Ditolak) oleh Pimpinan, atau belum pernah divalidasi.
