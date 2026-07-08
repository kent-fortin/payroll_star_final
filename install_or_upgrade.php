<?php
require_once __DIR__ . '/config/koneksi.php';
header('Content-Type: text/html; charset=UTF-8');
if (!$conn) die('<h3>Koneksi database gagal.</h3><p>Pastikan MySQL aktif dan database db_payroll_star_samudera tersedia.</p>');

function table_exists(mysqli $conn,string $table):bool{$t=mysqli_real_escape_string($conn,$table);$q=mysqli_query($conn,"SHOW TABLES LIKE '$t'");return $q&&mysqli_num_rows($q)>0;}
function column_exists(mysqli $conn,string $table,string $column):bool{$t=mysqli_real_escape_string($conn,$table);$c=mysqli_real_escape_string($conn,$column);$q=mysqli_query($conn,"SHOW COLUMNS FROM `$t` LIKE '$c'");return $q&&mysqli_num_rows($q)>0;}
function run_sql(mysqli $conn,string $sql,string $success,array &$messages):bool{$ok=mysqli_query($conn,$sql);if($ok)$messages[]=['ok',$success];else{$messages[]=['fail','Langkah gagal dan dicatat pada log.'];app_log('Upgrade SQL: '.mysqli_error($conn).' | '.$sql);}return $ok;}
function add_column(mysqli $conn,string $table,string $column,string $definition,array &$messages):void{if(!column_exists($conn,$table,$column))run_sql($conn,"ALTER TABLE `$table` ADD COLUMN $definition","Kolom $table.$column ditambahkan.",$messages);}
function drop_column(mysqli $conn,string $table,string $column,array &$messages):void{if(column_exists($conn,$table,$column))run_sql($conn,"ALTER TABLE `$table` DROP COLUMN `$column`","Kolom lama $table.$column dihapus.",$messages);}
$messages=[];

add_column($conn,'users','email','`email` VARCHAR(100) NULL',$messages);
add_column($conn,'users','no_whatsapp','`no_whatsapp` VARCHAR(30) NULL',$messages);
add_column($conn,'users','reset_otp','`reset_otp` VARCHAR(10) NULL',$messages);
add_column($conn,'users','reset_otp_expired_at','`reset_otp_expired_at` DATETIME NULL',$messages);
run_sql($conn,"UPDATE users SET email='angelinocttt@gmail.com',no_whatsapp='6287738565119' WHERE username='admin'",'Kontak admin diperbarui.',$messages);
run_sql($conn,"UPDATE users SET email='kentfh206@gmail.com',no_whatsapp='6281933630535' WHERE username='pimpinan'",'Kontak pimpinan diperbarui.',$messages);

run_sql($conn,"CREATE TABLE IF NOT EXISTS pengaturan_payroll (id_pengaturan INT AUTO_INCREMENT PRIMARY KEY,nama_pengaturan VARCHAR(60) NOT NULL UNIQUE,nilai DECIMAL(12,2) NOT NULL,keterangan VARCHAR(255) NULL) ENGINE=InnoDB",'Tabel pengaturan payroll siap.',$messages);
run_sql($conn,"INSERT INTO pengaturan_payroll (nama_pengaturan,nilai,keterangan) VALUES ('tarif_lembur_per_jam',15000,'Tarif lembur setiap satu jam'),('potongan_alpha_per_hari',25000,'Potongan setiap satu hari alpha') ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),keterangan=VALUES(keterangan)",'Tarif lembur dan alpha ditetapkan.',$messages);

drop_column($conn,'jabatan','tunjangan_jabatan',$messages);
drop_column($conn,'karyawan','npwp_status',$messages);
drop_column($conn,'karyawan','ptkp',$messages);
if(table_exists($conn,'tunjangan'))run_sql($conn,'DROP TABLE tunjangan','Tabel tunjangan dihapus.',$messages);
if(table_exists($conn,'potongan'))run_sql($conn,'DROP TABLE potongan','Tabel potongan dihapus.',$messages);
if(table_exists($conn,'laporan_penggajian'))run_sql($conn,'DROP TABLE laporan_penggajian','Tabel laporan lama dihapus; laporan sekarang dihitung dinamis.',$messages);

add_column($conn,'absensi','dibuat_oleh','`dibuat_oleh` INT NULL',$messages);
add_column($conn,'absensi','dibuat_pada','`dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',$messages);
add_column($conn,'absensi','diperbarui_pada','`diperbarui_pada` DATETIME NULL',$messages);

run_sql($conn,"CREATE TABLE IF NOT EXISTS permintaan_edit_absensi (
 id_permintaan INT AUTO_INCREMENT PRIMARY KEY,id_absensi INT NOT NULL,hadir_baru INT NOT NULL,sakit_baru INT NOT NULL,izin_baru INT NOT NULL,alpha_baru INT NOT NULL,lembur_jam_baru INT NOT NULL,alasan_perubahan VARCHAR(255) NOT NULL,data_lama TEXT NOT NULL,status ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',id_pengaju INT NOT NULL,id_penyetuju INT NULL,tanggal_pengajuan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,tanggal_keputusan DATETIME NULL,catatan_pimpinan VARCHAR(255) NULL,INDEX(id_absensi),INDEX(status)
) ENGINE=InnoDB",'Tabel persetujuan edit absensi siap.',$messages);

add_column($conn,'payroll','jam_lembur','`jam_lembur` INT NOT NULL DEFAULT 0',$messages);
add_column($conn,'payroll','tarif_lembur','`tarif_lembur` DECIMAL(12,2) NOT NULL DEFAULT 15000',$messages);
add_column($conn,'payroll','total_lembur','`total_lembur` DECIMAL(12,2) NOT NULL DEFAULT 0',$messages);
add_column($conn,'payroll','jumlah_alpha','`jumlah_alpha` INT NOT NULL DEFAULT 0',$messages);
add_column($conn,'payroll','tarif_alpha','`tarif_alpha` DECIMAL(12,2) NOT NULL DEFAULT 25000',$messages);
add_column($conn,'payroll','total_potongan_alpha','`total_potongan_alpha` DECIMAL(12,2) NOT NULL DEFAULT 0',$messages);
add_column($conn,'payroll','status_pembayaran',"`status_pembayaran` ENUM('Belum Dibayar','Sudah Dibayar') NOT NULL DEFAULT 'Belum Dibayar'",$messages);
add_column($conn,'payroll','tanggal_pembayaran','`tanggal_pembayaran` DATE NULL',$messages);
add_column($conn,'payroll','diproses_oleh','`diproses_oleh` INT NULL',$messages);
run_sql($conn,"UPDATE payroll p LEFT JOIN absensi a ON a.id_karyawan=p.id_karyawan AND a.bulan=p.bulan AND a.tahun=p.tahun SET p.jam_lembur=COALESCE(a.lembur_jam,0),p.tarif_lembur=15000,p.total_lembur=COALESCE(a.lembur_jam,0)*15000,p.jumlah_alpha=COALESCE(a.alpha,0),p.tarif_alpha=25000,p.total_potongan_alpha=COALESCE(a.alpha,0)*25000,p.total_gaji_bersih=p.gaji_pokok+(COALESCE(a.lembur_jam,0)*15000)-(COALESCE(a.alpha,0)*25000)",'Data payroll lama dihitung ulang dengan rumus baru.',$messages);
run_sql($conn,"ALTER TABLE payroll MODIFY tanggal_proses DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",'Kolom tanggal proses payroll disesuaikan.',$messages);
drop_column($conn,'payroll','total_tunjangan',$messages);
drop_column($conn,'payroll','lembur',$messages);
drop_column($conn,'payroll','total_potongan',$messages);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Upgrade Payroll</title><style>body{font-family:Arial;background:#f1f5f9;padding:30px}.box{max-width:800px;margin:auto;background:white;padding:25px;border-radius:16px}.ok{color:#166534}.fail{color:#b91c1c}li{margin:8px 0}</style></head><body><div class="box"><h2>Upgrade Aplikasi Payroll</h2><ul><?php foreach($messages as [$type,$message]):?><li class="<?= $type ?>"><?= e($message) ?></li><?php endforeach;?></ul><h3>Proses selesai</h3><p>Buka <a href="<?= url('dashboard.php') ?>">Dashboard</a> lalu uji setiap menu. Setelah berhasil, hapus file <strong>install_or_upgrade.php</strong>.</p></div></body></html>
