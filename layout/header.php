<?php
require_once __DIR__ . '/../config/koneksi.php';
require_login();
db_or_redirect($conn);
$flash = get_flash();
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

function nav_item(string $label, string $href, string $currentPath, string $icon = 'circle'): string
{
    $active = str_contains($currentPath, $href) ? 'active' : '';
    return '<a class="nav-link ' . $active . '" href="' . e(url($href)) . '">'
         . '<i class="bi bi-' . $icon . '"></i> ' . e($label)
         . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll PT Star Samudera Logistik</title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
<div class="app-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar no-print d-none d-lg-flex">
        <!-- Brand -->
        <div class="sidebar-brand">
            <img src="<?= asset('img/logo.png') ?>" alt="Logo" class="logo-mini">
            <div>
                <div class="sidebar-brand-name">PT Star Samudera</div>
                <div class="sidebar-brand-sub">Payroll System</div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <?= nav_item('Dashboard', is_admin() ? 'dashboard_admin.php' : 'dashboard_pimpinan.php', $currentPath, 'speedometer2') ?>
            <?php if (is_admin()): ?>
                <?= nav_item('Data Jabatan', 'master/jabatan.php', $currentPath, 'tag') ?>
                <?= nav_item('Data Karyawan', 'master/karyawan.php', $currentPath, 'people') ?>
                <?= nav_item('Rekap Absensi', 'master/absensi.php', $currentPath, 'calendar-check') ?>
                <?= nav_item('Proses Payroll', 'transaksi/payroll.php', $currentPath, 'cash-stack') ?>
            <?php else: ?>
                <?= nav_item('Approval Absensi', 'approval/absensi.php', $currentPath, 'check2-circle') ?>
                <?= nav_item('Laporan Gaji', 'laporan/laporan.php', $currentPath, 'file-earmark-bar-graph') ?>
            <?php endif; ?>
        </nav>

        <!-- Info box -->
        <div class="sidebar-info">
            <i class="bi bi-info-circle"></i>
            Lembur: Rp15.000/jam &middot; Alpha: Rp25.000/hari
        </div>

        <!-- Logout at bottom -->
        <div class="sidebar-logout">
            <a href="<?= url('auth/logout.php') ?>">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar no-print">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= asset('img/logo.png') ?>" alt="Logo" class="brand-logo">
                <div>
                    <h1 class="topbar-title">Payroll PT Star Samudera Logistik</h1>
                    <div class="topbar-sub">Jl. Kapten Sumarsono No.32, Sunggal, Deli Serdang, Sumatera Utara</div>
                </div>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-label">Login sebagai</div>
                <div class="topbar-user-name"><?= e($_SESSION['nama'] ?? '') ?> <span class="topbar-role">(<?= e($_SESSION['role'] ?? '') ?>)</span></div>
            </div>
        </header>

        <!-- Page content starts here -->
        <div class="page-content">

        <?php if ($flash): ?>
            <script>
                window.flashMessage = {
                    type: '<?= e($flash['type']) === 'danger' ? 'error' : (e($flash['type']) === 'warning' ? 'warning' : 'success') ?>',
                    text: '<?= addslashes(e($flash['message'])) ?>'
                };
            </script>
        <?php endif; ?>
