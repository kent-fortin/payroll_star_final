<?php
require_once __DIR__ . '/../config/koneksi.php';
require_login();
if (is_admin()) {
    redirect('dashboard_admin.php');
}
require_once __DIR__ . '/../layout/header.php';

/*
 * Laporan dibuat mandiri agar tetap tampil walaupun helper laporan versi lama
 * belum ikut tertimpa saat project disalin ke htdocs.
 */

function rpt_bulan_list()
{
    return array(
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    );
}

function rpt_bulan_nomor($bulan)
{
    foreach (rpt_bulan_list() as $nomor => $nama) {
        if (strcasecmp(trim((string) $bulan), $nama) === 0) {
            return (int) $nomor;
        }
    }
    return 0;
}

function rpt_bulan_options($selected)
{
    $html = '';
    foreach (rpt_bulan_list() as $nama) {
        $sel = strcasecmp((string) $selected, $nama) === 0 ? ' selected' : '';
        $html .= '<option value="' . e($nama) . '"' . $sel . '>' . e($nama) . '</option>';
    }
    return $html;
}

function rpt_latest_period($conn)
{
    $latest = array('bulan' => rpt_bulan_list()[(int) date('n')], 'tahun' => (int) date('Y'));
    $q = mysqli_query($conn, "SELECT bulan, tahun FROM payroll");
    if (!$q) {
        return $latest;
    }

    $latestKey = 0;
    while ($row = mysqli_fetch_assoc($q)) {
        $m = rpt_bulan_nomor($row['bulan']);
        $y = (int) $row['tahun'];
        $key = ($y * 100) + $m;
        if ($m > 0 && $key > $latestKey) {
            $latestKey = $key;
            $latest = array('bulan' => $row['bulan'], 'tahun' => $y);
        }
    }
    return $latest;
}

function rpt_period_range($filter, $bulan, $tahun)
{
    $month = rpt_bulan_nomor($bulan);
    if ($month < 1) {
        $month = (int) date('n');
    }

    $end = new DateTime(sprintf('%04d-%02d-01', (int) $tahun, $month));
    $start = clone $end;

    if ($filter === '2bulan') {
        $start->modify('-1 month');
    } elseif ($filter === '1tahun') {
        $start->modify('-11 months');
    }

    return array($start, $end);
}

function rpt_load($conn, $filter, $bulan, $tahun)
{
    list($start, $end) = rpt_period_range($filter, $bulan, $tahun);

    $sql = "SELECT
                p.id_payroll,
                p.bulan,
                p.tahun,
                p.gaji_pokok,
                p.jam_lembur,
                p.tarif_lembur,
                p.total_lembur,
                p.total_tunjangan,
                p.jumlah_alpha,
                p.tarif_alpha,
                p.total_potongan_alpha,
                p.total_gaji_bersih,
                p.status_pembayaran,
                p.tanggal_pembayaran,
                p.tanggal_proses,
                k.nip,
                k.nama_karyawan,
                j.nama_jabatan
            FROM payroll p
            INNER JOIN karyawan k ON k.id_karyawan = p.id_karyawan
            INNER JOIN jabatan j ON j.id_jabatan = k.id_jabatan
            ORDER BY p.tahun DESC, p.id_payroll DESC";

    $result = mysqli_query($conn, $sql);
    $rows = array();
    $groups = array();
    $summary = array(
        'paid_count' => 0,
        'unpaid_count' => 0,
        'paid_total' => 0,
        'unpaid_total' => 0,
        'grand_total' => 0
    );
    $error = '';

    if (!$result) {
        $error = 'Data laporan belum dapat dibaca. Jalankan install_or_upgrade.php satu kali, lalu muat ulang halaman.';
        if (function_exists('app_log')) {
            app_log('Laporan gaji query gagal: ' . mysqli_error($conn));
        }
        return array(
            'rows' => $rows,
            'groups' => $groups,
            'summary' => $summary,
            'start' => $start,
            'end' => $end,
            'error' => $error
        );
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $monthNo = rpt_bulan_nomor($row['bulan']);
        if ($monthNo < 1) {
            continue;
        }

        $date = new DateTime(sprintf('%04d-%02d-01', (int) $row['tahun'], $monthNo));
        if ($date < $start || $date > $end) {
            continue;
        }

        $row['_sort_date'] = $date->format('Y-m-d');
        $rows[] = $row;

        $key = $date->format('Y-m');
        if (!isset($groups[$key])) {
            $groups[$key] = array(
                'label' => $row['bulan'] . ' ' . $row['tahun'],
                'paid' => 0,
                'unpaid' => 0,
                'total' => 0
            );
        }

        $amount = (float) $row['total_gaji_bersih'];
        $groups[$key]['total'] += $amount;
        $summary['grand_total'] += $amount;

        if ($row['status_pembayaran'] === 'Sudah Dibayar') {
            $summary['paid_count']++;
            $summary['paid_total'] += $amount;
            $groups[$key]['paid'] += $amount;
        } else {
            $summary['unpaid_count']++;
            $summary['unpaid_total'] += $amount;
            $groups[$key]['unpaid'] += $amount;
        }
    }

    usort($rows, function ($a, $b) {
        $left = $a['_sort_date'] . '-' . $a['nip'];
        $right = $b['_sort_date'] . '-' . $b['nip'];
        return strcmp($right, $left);
    });
    ksort($groups);

    return array(
        'rows' => $rows,
        'groups' => $groups,
        'summary' => $summary,
        'start' => $start,
        'end' => $end,
        'error' => $error
    );
}

$latest = rpt_latest_period($conn);
$filter = isset($_GET['filter']) && in_array($_GET['filter'], array('1bulan', '2bulan', '1tahun'), true)
    ? $_GET['filter']
    : '1bulan';
$bulan = isset($_GET['bulan']) && trim($_GET['bulan']) !== '' ? trim($_GET['bulan']) : $latest['bulan'];
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) $latest['tahun'];

$report = rpt_load($conn, $filter, $bulan, $tahun);
$summary = $report['summary'];
$groups = $report['groups'];
$rows = $report['rows'];
$max = 0;
foreach ($groups as $group) {
    if ($group['total'] > $max) {
        $max = $group['total'];
    }
}
$query = http_build_query(array('filter' => $filter, 'bulan' => $bulan, 'tahun' => $tahun));
?>

<div class="card p-4 mb-4 no-print">
    <div class="section-header">
        <i class="bi bi-funnel"></i>
        <h2 class="h5">Filter Laporan Gaji</h2>
    </div>
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Rentang Laporan</label>
            <select name="filter" class="form-select">
                <option value="1bulan" <?= $filter === '1bulan' ? 'selected' : '' ?>>1 Bulan</option>
                <option value="2bulan" <?= $filter === '2bulan' ? 'selected' : '' ?>>2 Bulan Terakhir</option>
                <option value="1tahun" <?= $filter === '1tahun' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Bulan Akhir</label>
            <select name="bulan" class="form-select"><?= rpt_bulan_options($bulan) ?></select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= e($tahun) ?>" min="2000" max="2100">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Tampilkan</button>
        </div>
        <div class="col-md-2">
            <a class="btn btn-dark w-100" href="<?= url('laporan/cetak.php?' . $query) ?>">Cetak Laporan</a>
        </div>
    </form>
</div>

<?php if ($report['error'] !== ''): ?>
    <div class="alert alert-danger"><?= e($report['error']) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white p-4 h-100">
            <div class="small fw-semibold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9;">Sudah Dibayar
            </div>
            <div class="fs-4 fw-bold mt-2"><?= (int) $summary['paid_count'] ?> <span
                    class="fs-6 fw-normal">karyawan</span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-dark p-4 h-100">
            <div class="small fw-semibold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9;">Belum Dibayar
            </div>
            <div class="fs-4 fw-bold mt-2"><?= (int) $summary['unpaid_count'] ?> <span
                    class="fs-6 fw-normal">karyawan</span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white p-4 h-100">
            <div class="small fw-semibold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9;">Total Sudah
                Dibayar</div>
            <div class="fs-4 fw-bold mt-2"><?= rupiah($summary['paid_total']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-dark text-white p-4 h-100">
            <div class="small fw-semibold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.9;">Total Seluruh
                Payroll</div>
            <div class="fs-4 fw-bold mt-2"><?= rupiah($summary['grand_total']) ?></div>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="section-header mb-0" style="border:none;padding:0;">
            <i class="bi bi-bar-chart"></i>
            <div>
                <h2 class="h5 mb-0">Grafik Pembayaran Gaji</h2>
                <div class="text-muted" style="font-size:.78rem;">
                    <?= e($report['start']->format('m/Y') . ' sampai ' . $report['end']->format('m/Y')) ?></div>
            </div>
        </div>
        <div class="chart-legend small">
            <span><i class="chart-dot chart-paid"></i>Sudah Dibayar</span>
            <span><i class="chart-dot chart-unpaid"></i>Belum Dibayar</span>
        </div>
    </div>

    <?php if (!empty($groups)): ?>
        <div class="chart-wrap">
            <?php foreach ($groups as $group): ?>
                <?php
                $paidHeight = $max > 0 ? max(3, round($group['paid'] / $max * 180)) : 3;
                $unpaidHeight = $max > 0 ? max(3, round($group['unpaid'] / $max * 180)) : 3;
                ?>
                <div class="chart-item">
                    <div class="chart-bars">
                        <div class="chart-bar chart-paid" style="height:<?= (int) $paidHeight ?>px"
                            title="<?= e(rupiah($group['paid'])) ?>"></div>
                        <div class="chart-bar chart-unpaid" style="height:<?= (int) $unpaidHeight ?>px"
                            title="<?= e(rupiah($group['unpaid'])) ?>"></div>
                    </div>
                    <div class="chart-label"><?= e($group['label']) ?></div>
                    <div class="small fw-semibold"><?= e(rupiah($group['total'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            Belum ada data payroll pada periode ini. Pilih bulan payroll terakhir atau gunakan filter yang lebih panjang.
        </div>
    <?php endif; ?>
</div>

<div class="card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="section-header mb-0" style="border:none;padding:0;">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <h2 class="h5 mb-0">Detail Pembayaran Gaji</h2>
        </div>
        <div>
            <span class="badge bg-success">Dibayar: <?= rupiah($summary['paid_total']) ?></span>
            <span class="badge bg-warning ms-1">Belum: <?= rupiah($summary['unpaid_total']) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-striped dt-table" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Periode</th>
                    <th>Karyawan</th>
                    <th>Gaji Pokok</th>
                    <th>Lembur</th>
                    <th>Tunjangan</th>
                    <th>Potongan Alpha</th>
                    <th>Gaji Bersih</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($row['bulan'] . ' ' . $row['tahun']) ?></td>
                            <td><strong><?= e($row['nip']) ?></strong><br><?= e($row['nama_karyawan']) ?><br><span
                                    class="small text-muted"><?= e($row['nama_jabatan']) ?></span></td>
                            <td><?= rupiah($row['gaji_pokok']) ?></td>
                            <td><?= (int) $row['jam_lembur'] ?> jam<br><strong><?= rupiah($row['total_lembur']) ?></strong></td>
                            <td><strong><?= rupiah($row['total_tunjangan'] ?? 0) ?></strong></td>
                            <td><?= (int) $row['jumlah_alpha'] ?> hari<br><strong
                                    class="text-danger">-<?= rupiah($row['total_potongan_alpha']) ?></strong></td>
                            <td><strong><?= rupiah($row['total_gaji_bersih']) ?></strong></td>
                            <td><?= status_badge($row['status_pembayaran']) ?>
                                <div class="small text-muted mt-1">
                                    <?= e(!empty($row['tanggal_pembayaran']) ? $row['tanggal_pembayaran'] : '-') ?></div>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-dark"
                                    href="<?= url('transaksi/cetak_rincian.php?id=' . (int) $row['id_payroll']) ?>">Cetak</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php require __DIR__ . '/grafik_laporan.php'; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>