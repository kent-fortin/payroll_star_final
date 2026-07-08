<?php
require_once __DIR__ . '/../config/koneksi.php';
require_admin();
set_flash('info','Menu tunjangan telah dihapus sesuai revisi.');
redirect('master/jabatan.php');
