<?php
require_once __DIR__ . '/config/koneksi.php';
redirect(is_logged_in() ? 'dashboard.php' : 'auth/login.php');
