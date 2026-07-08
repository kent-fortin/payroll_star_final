ALTER TABLE jabatan ADD status_jabatan ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif';
ALTER TABLE karyawan MODIFY status_karyawan ENUM('Tetap','Kontrak','Resign') NOT NULL;
