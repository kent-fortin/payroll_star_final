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

## 🛠️ Panduan Lengkap Instalasi & Menjalankan Project (Untuk Pemula)

Jika Anda awam atau baru pertama kali menjalankan project PHP, ikuti langkah-langkah detail di bawah ini dari awal sampai akhir:

### Tahap 1: Persiapan (Yang Perlu Didownload & Diinstall)
1. **XAMPP**: Software ini berisi web server (Apache) dan database (MySQL) yang wajib digunakan untuk menjalankan aplikasi PHP.
   - Download XAMPP di situs resminya: [apachefriends.org](https://www.apachefriends.org/download.html).
   - Pilih versi PHP yang kompatibel (seperti versi PHP 7.4 atau PHP 8.x).
   - Install XAMPP seperti biasa (tinggal di-*next-next* saja) dan pastikan diinstall di folder utama `C:\xampp`.
2. **Git (Opsional)**: Digunakan untuk mendownload (clone) project dari GitHub menggunakan command line.
   - Download Git di [git-scm.com](https://git-scm.com/downloads) dan install.

### Tahap 2: Mendapatkan Source Code Project
Anda bisa mendownload project ini dengan dua cara:

**Cara A: Menggunakan Git Clone (Disarankan)**
1. Buka folder tempat Anda ingin mendownload project.
2. Klik kanan di folder tersebut, lalu pilih **Open Git Bash here** (opsi ini muncul jika Git sudah diinstall).
3. Ketik perintah berikut dan tekan Enter:
   ```bash
   git clone https://github.com/USERNAME/REPOSITORY.git
   ```
   *(Catatan: Ganti link di atas dengan link repository GitHub project ini)*
4. Folder project bernama `payroll_star_final` akan otomatis terdownload.

**Cara B: Download Manual via ZIP**
1. Di halaman GitHub repository ini, klik tombol hijau **Code**, lalu pilih **Download ZIP**.
2. Setelah file terdownload, ekstrak (unzip) file tersebut.
3. Ubah nama foldernya menjadi `payroll_star_final` (jika nama bawaannya adalah `nama-repo-main`).

### Tahap 3: Memindahkan Project ke XAMPP
Aplikasi berbasis PHP harus diletakkan di dalam folder server lokal (XAMPP).
1. Copy atau Cut folder `payroll_star_final` yang sudah didapatkan dari Tahap 2.
2. Masuk ke Local Disk C: (tempat XAMPP diinstall).
3. Buka folder `C:\xampp\htdocs`.
4. **Paste** folder `payroll_star_final` di dalam folder `htdocs` tersebut.

### Tahap 4: Menjalankan Database & Import Data
1. Buka aplikasi **XAMPP Control Panel** (bisa dicari dari menu Start Windows).
2. Klik tombol **Start** pada tulisan **Apache** dan **MySQL** sampai background teksnya berwarna **Hijau**.
3. Buka browser (Google Chrome, Mozilla Firefox, dll).
4. Ketik di pencarian url (address bar): `http://localhost/phpmyadmin` dan tekan Enter.
5. Pada panel sebelah kiri, klik tulisan **New** (Baru) untuk membuat database.
6. Isi kolom nama basis data (database name) dengan tepat: **`db_payroll_star_samudera`**. Lalu klik tombol **Create** (Buat).
7. Klik database `db_payroll_star_samudera` yang baru saja dibuat di menu sebelah kiri.
8. Klik tab **Import** (Impor) di bagian atas halaman.
9. Klik tombol **Choose File** (Pilih File) atau **Browse**.
10. Arahkan ke folder project Anda di `C:\xampp\htdocs\payroll_star_final\database\` dan pilih file bernama `database.sql`.
11. Scroll ke paling bawah halaman dan klik tombol **Import** (atau Go). Tunggu sebentar hingga muncul pesan hijau berhasil *(success)*.

### Tahap 5: Mulai Menjalankan Aplikasi
1. Buka tab baru di browser Anda.
2. Ketik alamat URL berikut di address bar:
   `http://localhost/payroll_star_final/`
3. Tekan Enter. Halaman Login aplikasi Payroll akan muncul dan siap digunakan!

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
