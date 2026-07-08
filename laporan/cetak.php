<?php
require_once __DIR__ . '/../config/koneksi.php';
require_login();
if (is_admin()) {
    redirect('dashboard.php');
}

function cetak_bulan_list()
{
    return array(
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    );
}

function cetak_bulan_nomor($bulan)
{
    foreach (cetak_bulan_list() as $nomor => $nama) {
        if (strcasecmp(trim((string)$bulan), $nama) === 0) {
            return (int)$nomor;
        }
    }
    return 0;
}

function cetak_latest_period($conn)
{
    $latest = array('bulan' => cetak_bulan_list()[(int)date('n')], 'tahun' => (int)date('Y'));
    $q = mysqli_query($conn, "SELECT bulan, tahun FROM payroll");
    $latestKey = 0;
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $m = cetak_bulan_nomor($row['bulan']);
            $key = ((int)$row['tahun'] * 100) + $m;
            if ($m > 0 && $key > $latestKey) {
                $latestKey = $key;
                $latest = array('bulan' => $row['bulan'], 'tahun' => (int)$row['tahun']);
            }
        }
    }
    return $latest;
}

function cetak_range($filter, $bulan, $tahun)
{
    $m = cetak_bulan_nomor($bulan);
    if ($m < 1) $m = (int)date('n');
    $end = new DateTime(sprintf('%04d-%02d-01', (int)$tahun, $m));
    $start = clone $end;
    if ($filter === '2bulan') $start->modify('-1 month');
    if ($filter === '1tahun') $start->modify('-11 months');
    return array($start, $end);
}

$latest = cetak_latest_period($conn);
$filter = isset($_GET['filter']) && in_array($_GET['filter'], array('1bulan', '2bulan', '1tahun'), true) ? $_GET['filter'] : '1bulan';
$bulan = isset($_GET['bulan']) && trim($_GET['bulan']) !== '' ? trim($_GET['bulan']) : $latest['bulan'];
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)$latest['tahun'];
list($start, $end) = cetak_range($filter, $bulan, $tahun);

$sql = "SELECT p.*, k.nip, k.nama_karyawan, j.nama_jabatan
        FROM payroll p
        INNER JOIN karyawan k ON k.id_karyawan=p.id_karyawan
        INNER JOIN jabatan j ON j.id_jabatan=k.id_jabatan
        ORDER BY p.tahun DESC, p.id_payroll DESC";
$result = mysqli_query($conn, $sql);
$rows = array();
$summary = array('paid_count'=>0,'unpaid_count'=>0,'paid_total'=>0,'unpaid_total'=>0,'grand_total'=>0);
$error = '';

if (!$result) {
    $error = 'Laporan belum dapat dibaca. Jalankan install_or_upgrade.php satu kali.';
    if (function_exists('app_log')) app_log('Cetak laporan query gagal: ' . mysqli_error($conn));
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $m = cetak_bulan_nomor($row['bulan']);
        if ($m < 1) continue;
        $date = new DateTime(sprintf('%04d-%02d-01', (int)$row['tahun'], $m));
        if ($date < $start || $date > $end) continue;
        $rows[] = $row;
        $amount = (float)$row['total_gaji_bersih'];
        $summary['grand_total'] += $amount;
        if ($row['status_pembayaran'] === 'Sudah Dibayar') {
            $summary['paid_count']++;
            $summary['paid_total'] += $amount;
        } else {
            $summary['unpaid_count']++;
            $summary['unpaid_total'] += $amount;
        }
    }
}
$query = http_build_query(array('filter'=>$filter,'bulan'=>$bulan,'tahun'=>$tahun));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Gaji</title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="container-fluid py-4 px-4">
    <div class="text-end no-print mb-3">
        <button onclick="window.print()" class="btn btn-dark">Cetak</button>
        <a href="<?= url('laporan/laporan.php?' . $query) ?>" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="card print-card shadow-sm p-4">
        <div class="text-center mb-4">
            <img src="<?= asset('img/logo.png') ?>" style="height:80px" alt="Logo">
            <h1 class="h3 mt-3 mb-1">LAPORAN PENGGAJIAN KARYAWAN</h1>
            <div>PT Star Samudera Logistik</div>
            <div class="text-muted">Periode <?= e($start->format('m/Y') . ' s.d. ' . $end->format('m/Y')) ?></div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="row g-2 mb-3">
            <div class="col-3"><div class="border p-2"><small>Sudah Dibayar</small><br><strong><?= (int)$summary['paid_count'] ?> karyawan</strong></div></div>
            <div class="col-3"><div class="border p-2"><small>Belum Dibayar</small><br><strong><?= (int)$summary['unpaid_count'] ?> karyawan</strong></div></div>
            <div class="col-3"><div class="border p-2"><small>Total Sudah Dibayar</small><br><strong><?= rupiah($summary['paid_total']) ?></strong></div></div>
            <div class="col-3"><div class="border p-2"><small>Total Belum Dibayar</small><br><strong><?= rupiah($summary['unpaid_total']) ?></strong></div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th>No</th><th>Periode</th><th>Karyawan</th>
                        <th>Gaji Pokok</th><th>Lembur</th>
                        <th>Tunjangan</th><th>Potongan Alpha</th><th>Gaji Bersih</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($row['bulan'] . ' ' . $row['tahun']) ?></td>
                                <td><strong><?= e($row['nip']) ?></strong><br><?= e($row['nama_karyawan']) ?><br><span class="small text-muted"><?= e($row['nama_jabatan']) ?></span></td>
                                <td><?= rupiah($row['gaji_pokok']) ?></td>
                                <td><?= (int)$row['jam_lembur'] ?> jam<br><strong><?= rupiah($row['total_lembur']) ?></strong></td>
                                <td><strong><?= rupiah($row['total_tunjangan'] ?? 0) ?></strong></td>
                                <td><?= (int)$row['jumlah_alpha'] ?> hari<br><strong>-<?= rupiah($row['total_potongan_alpha']) ?></strong></td>
                                <td><strong><?= rupiah($row['total_gaji_bersih']) ?></strong></td>
                                <td><?= e($row['status_pembayaran']) ?><br><small><?= e(!empty($row['tanggal_pembayaran']) ? $row['tanggal_pembayaran'] : '-') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">Belum ada data pada periode ini.</td></tr>
                    <?php endif; ?>
                    <tr>
                        <th colspan="7" class="text-end">Total Seluruh Payroll</th>
                        <th colspan="2"><?= rupiah($summary['grand_total']) ?></th>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="row text-center mt-5">
            <div class="col-6"><p>Mengetahui,</p><div style="height:70px"></div><strong>Pimpinan</strong></div>
            <div class="col-6"><p>Admin Payroll,</p><div style="height:70px"></div><strong>Administrator</strong></div>
        </div>
    </div>
</div>
</body>
</html>
