<?php
ob_start();
require_once __DIR__ . '/../config/koneksi.php';
require_login();
db_or_redirect($conn);
$flash = get_flash();
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

function nav_item(string $label, string $href, string $currentPath, string $icon = 'circle', int $badgeCount = 0): string
{
    $active = str_contains($currentPath, $href) ? 'active' : '';
    $badgeHtml = $badgeCount > 0 ? '<span class="badge bg-danger rounded-pill ms-auto" style="font-size:0.7rem; box-shadow:0 0 0 2px var(--sidebar-bg-start);">' . $badgeCount . '</span>' : '';
    return '<a class="nav-link ' . $active . '" href="' . e(url($href)) . '">'
         . '<i class="bi bi-' . $icon . '"></i> <span>' . e($label) . '</span>' . $badgeHtml
         . '</a>';
}

function nav_heading(string $label): string
{
    return '<div class="sidebar-heading">' . e($label) . '</div>';
}

$pendingEditAbsensi = 0;
$pendingValidasiPayroll = 0;
if (is_logged_in() && is_pimpinan()) {
    $q1 = mysqli_query($conn, "SELECT COUNT(*) total FROM permintaan_edit_absensi WHERE status='Menunggu'");
    $pendingEditAbsensi = $q1 ? (int)mysqli_fetch_assoc($q1)['total'] : 0;
    
    $q2 = mysqli_query($conn, "SELECT COUNT(*) total FROM payroll WHERE status_validasi='Menunggu'");
    $pendingValidasiPayroll = $q2 ? (int)mysqli_fetch_assoc($q2)['total'] : 0;
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

    <div class="sidebar-backdrop"></div>
    <!-- SIDEBAR -->
    <aside class="sidebar no-print">
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

            <!-- Dashboard (semua role) -->
            <?= nav_item('Dashboard', is_admin() ? 'dashboard_admin.php' : 'dashboard_pimpinan.php', $currentPath, 'speedometer2') ?>

            <?php if (is_admin()): ?>

                <!-- MASTER DATA -->
                <?= nav_heading('Master Data') ?>
                <?= nav_item('Data Karyawan', 'master/karyawan.php', $currentPath, 'people-fill') ?>
                <?= nav_item('Data Jabatan', 'master/jabatan.php', $currentPath, 'tag-fill') ?>

                <!-- TRANSAKSI -->
                <?= nav_heading('Transaksi') ?>
                <?= nav_item('Presensi Harian', 'master/presensi_harian.php', $currentPath, 'calendar3-week-fill') ?>
                <?= nav_item('Rekap Absensi', 'master/absensi.php', $currentPath, 'calendar-check-fill') ?>
                <?= nav_item('Data Lembur', 'master/lembur.php', $currentPath, 'clock-history') ?>
                <?= nav_item('Proses Payroll', 'transaksi/payroll.php', $currentPath, 'cash-stack') ?>

            <?php else: ?>

                <!-- MENU PIMPINAN -->
                <?= nav_heading('Approval') ?>
                <?= nav_item('Approval Absensi', 'approval/absensi.php', $currentPath, 'check2-circle', $pendingEditAbsensi) ?>
                <?= nav_item('Validasi Payroll', 'approval/validasi_payroll.php', $currentPath, 'check2-square', $pendingValidasiPayroll) ?>

                <?= nav_heading('Laporan') ?>
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
            <div class="d-flex align-items-center gap-2 gap-md-3 flex-grow-1" style="min-width: 0;">
                <button type="button" class="btn btn-light d-lg-none flex-shrink-0" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <img src="<?= asset('img/logo.png') ?>" alt="Logo" class="brand-logo d-none d-sm-block flex-shrink-0">
                <div class="text-truncate">
                    <h1 class="topbar-title text-truncate mb-0 d-none d-md-block">Payroll PT Star Samudera Logistik</h1>
                    <h1 class="topbar-title text-truncate mb-0 d-md-none">Payroll System</h1>
                    <div class="topbar-sub d-none d-md-block text-truncate">Jl. Kapten Sumarsono No.32, Sunggal, Deli Serdang, Sumatera Utara</div>
                </div>
            </div>
            <div class="topbar-user text-end flex-shrink-0 ms-2" style="max-width: 45%;">
                <div class="topbar-user-label d-none d-sm-block">Login sebagai</div>
                <div class="topbar-user-name text-truncate">
                    <span class="d-none d-sm-inline"><?= e($_SESSION['nama'] ?? '') ?></span>
                    <span class="topbar-role">(<?= e($_SESSION['role'] ?? '') ?>)</span>
                </div>
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

