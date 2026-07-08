<?php
require_once __DIR__ . '/../config/koneksi.php';
session_unset();
session_destroy();
session_start();
set_flash('success', 'Anda telah logout.');
redirect('auth/login.php');
