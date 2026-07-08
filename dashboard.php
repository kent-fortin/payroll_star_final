<?php
require_once __DIR__ . '/layout/header.php';
if (is_admin()) {
    redirect('dashboard_admin.php');
} else {
    redirect('dashboard_pimpinan.php');
}
?>
