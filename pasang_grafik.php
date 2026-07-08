<?php
/*
|--------------------------------------------------------------------------
| PATCH OTOMATIS GRAFIK LAPORAN GAJI
|--------------------------------------------------------------------------
| Cara pakai:
| 1. Letakkan file ini di folder utama project payroll_star_final.
| 2. Jalankan: http://localhost/payroll_star_final/pasang_grafik.php
| 3. Setelah berhasil, hapus file pasang_grafik.php.
*/

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo '<pre style="font-family:Consolas,monospace;line-height:1.6;">';

$root = __DIR__;
$laporanDir = $root . '/laporan';
$laporanFile = $laporanDir . '/laporan.php';
$grafikFile = $laporanDir . '/grafik_laporan.php';

if (!is_dir($laporanDir)) {
    die("GAGAL: Folder laporan tidak ditemukan.\nPastikan file pasang_grafik.php berada di folder utama project payroll_star_final.");
}

if (!file_exists($laporanFile)) {
    die("GAGAL: File laporan/laporan.php tidak ditemukan.");
}

$grafikContent = <<<'PHPFILE'
<?php
/*
|--------------------------------------------------------------------------
| GRAFIK LAPORAN GAJI - OFFLINE
|--------------------------------------------------------------------------
| Tidak membutuhkan Chart.js atau koneksi internet.
*/

if (!isset($conn) || !$conn) {
    echo '<div class="alert alert-warning">Grafik belum dapat ditampilkan karena koneksi database tidak tersedia.</div>';
    return;
}

if (!function_exists('grafik_h')) {
    function grafik_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$urutanBulan = [
    'Januari' => 1,
    'Februari' => 2,
    'Maret' => 3,
    'April' => 4,
    'Mei' => 5,
    'Juni' => 6,
    'Juli' => 7,
    'Agustus' => 8,
    'September' => 9,
    'Oktober' => 10,
    'November' => 11,
    'Desember' => 12
];

$cekStatus = mysqli_query($conn, "SHOW COLUMNS FROM payroll LIKE 'status_pembayaran'");
$punyaStatus = $cekStatus && mysqli_num_rows($cekStatus) > 0;

if ($punyaStatus) {
    $sql = "
        SELECT
            bulan,
            tahun,
            SUM(total_gaji_bersih) AS total_gaji,
            SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(status_pembayaran, ''))) IN
                        ('sudah dibayar', 'dibayar', 'lunas')
                    THEN total_gaji_bersih
                    ELSE 0
                END
            ) AS total_dibayar,
            SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(status_pembayaran, ''))) IN
                        ('sudah dibayar', 'dibayar', 'lunas')
                    THEN 0
                    ELSE total_gaji_bersih
                END
            ) AS total_belum_dibayar,
            SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(status_pembayaran, ''))) IN
                        ('sudah dibayar', 'dibayar', 'lunas')
                    THEN 1
                    ELSE 0
                END
            ) AS jumlah_sudah,
            SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(status_pembayaran, ''))) IN
                        ('sudah dibayar', 'dibayar', 'lunas')
                    THEN 0
                    ELSE 1
                END
            ) AS jumlah_belum
        FROM payroll
        GROUP BY tahun, bulan
    ";
} else {
    $sql = "
        SELECT
            bulan,
            tahun,
            SUM(total_gaji_bersih) AS total_gaji,
            0 AS total_dibayar,
            SUM(total_gaji_bersih) AS total_belum_dibayar,
            0 AS jumlah_sudah,
            COUNT(*) AS jumlah_belum
        FROM payroll
        GROUP BY tahun, bulan
    ";
}

$query = mysqli_query($conn, $sql);
$dataGrafik = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $nomorBulan = $urutanBulan[$row['bulan']] ?? 0;
        $tahun = (int)$row['tahun'];

        $dataGrafik[] = [
            'bulan' => $row['bulan'],
            'tahun' => $tahun,
            'nomor_bulan' => $nomorBulan,
            'urutan' => ($tahun * 100) + $nomorBulan,
            'total_gaji' => (float)$row['total_gaji'],
            'total_dibayar' => (float)$row['total_dibayar'],
            'total_belum_dibayar' => (float)$row['total_belum_dibayar'],
            'jumlah_sudah' => (int)$row['jumlah_sudah'],
            'jumlah_belum' => (int)$row['jumlah_belum']
        ];
    }
}

usort($dataGrafik, function ($a, $b) {
    return $a['urutan'] <=> $b['urutan'];
});

$rentang = $_GET['rentang']
    ?? $_GET['filter']
    ?? $_GET['periode_filter']
    ?? '1tahun';

$rentangNormal = strtolower(str_replace([' ', '-', '_'], '', (string)$rentang));

if (in_array($rentangNormal, ['1bulan', 'bulanini', 'satu bulan'], true)) {
    $dataGrafik = array_slice($dataGrafik, -1);
} elseif (in_array($rentangNormal, ['2bulan', '2bulanterakhir', 'duabulan'], true)) {
    $dataGrafik = array_slice($dataGrafik, -2);
} else {
    $dataGrafik = array_slice($dataGrafik, -12);
}

$totalSemua = 0;
$totalSudah = 0;
$totalBelum = 0;
$jumlahSudah = 0;
$jumlahBelum = 0;
$nilaiTerbesar = 0;

foreach ($dataGrafik as $item) {
    $totalSemua += $item['total_gaji'];
    $totalSudah += $item['total_dibayar'];
    $totalBelum += $item['total_belum_dibayar'];
    $jumlahSudah += $item['jumlah_sudah'];
    $jumlahBelum += $item['jumlah_belum'];

    if ($item['total_gaji'] > $nilaiTerbesar) {
        $nilaiTerbesar = $item['total_gaji'];
    }
}

if ($nilaiTerbesar <= 0) {
    $nilaiTerbesar = 1;
}
?>

<style>
.grafik-gaji-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    margin-top: 22px;
    box-shadow: 0 4px 18px rgba(15, 23, 42, .08);
}

.grafik-gaji-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #172033;
}

.grafik-gaji-subtitle {
    margin: 5px 0 22px;
    color: #64748b;
    font-size: 13px;
}

.grafik-gaji-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}

.grafik-summary-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
}

.grafik-summary-label {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 6px;
}

.grafik-summary-value {
    color: #172033;
    font-size: 19px;
    font-weight: 700;
}

.grafik-count {
    color: #64748b;
    font-size: 12px;
    margin-top: 5px;
}

.grafik-gaji-area {
    border-top: 1px solid #edf1f5;
    padding-top: 20px;
}

.grafik-gaji-row {
    display: grid;
    grid-template-columns: 135px minmax(200px, 1fr) 165px;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}

.grafik-gaji-period {
    font-size: 13px;
    font-weight: 600;
    color: #263043;
}

.grafik-gaji-track {
    height: 30px;
    border-radius: 8px;
    background: #eef2f7;
    overflow: hidden;
}

.grafik-gaji-total {
    height: 100%;
    display: flex;
    border-radius: 8px;
    overflow: hidden;
    min-width: 4px;
}

.grafik-gaji-paid {
    height: 100%;
    background: #16a34a;
}

.grafik-gaji-unpaid {
    height: 100%;
    background: #ef4444;
}

.grafik-gaji-nominal {
    text-align: right;
    font-size: 13px;
    font-weight: 600;
    color: #263043;
}

.grafik-gaji-legend {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 20px;
    font-size: 13px;
    color: #475569;
}

.grafik-legend-item {
    display: flex;
    align-items: center;
    gap: 7px;
}

.grafik-legend-box {
    width: 13px;
    height: 13px;
    border-radius: 3px;
}

.grafik-legend-paid {
    background: #16a34a;
}

.grafik-legend-unpaid {
    background: #ef4444;
}

.grafik-gaji-empty {
    padding: 30px;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
    border-radius: 12px;
}

@media (max-width: 850px) {
    .grafik-gaji-summary {
        grid-template-columns: 1fr;
    }

    .grafik-gaji-row {
        grid-template-columns: 1fr;
    }

    .grafik-gaji-nominal {
        text-align: left;
    }
}

@media print {
    .grafik-gaji-card {
        box-shadow: none;
        border: 1px solid #ccc;
    }
}
</style>

<div class="grafik-gaji-card">
    <h3 class="grafik-gaji-title">Grafik Pembayaran Gaji</h3>
    <div class="grafik-gaji-subtitle">
        Perbandingan nilai gaji yang sudah dibayar dan belum dibayar berdasarkan periode payroll.
    </div>

    <div class="grafik-gaji-summary">
        <div class="grafik-summary-item">
            <div class="grafik-summary-label">Total Seluruh Penggajian</div>
            <div class="grafik-summary-value">
                Rp<?= number_format($totalSemua, 0, ',', '.') ?>
            </div>
        </div>

        <div class="grafik-summary-item">
            <div class="grafik-summary-label">Total Sudah Dibayar</div>
            <div class="grafik-summary-value">
                Rp<?= number_format($totalSudah, 0, ',', '.') ?>
            </div>
            <div class="grafik-count">
                <?= number_format($jumlahSudah, 0, ',', '.') ?> data payroll
            </div>
        </div>

        <div class="grafik-summary-item">
            <div class="grafik-summary-label">Total Belum Dibayar</div>
            <div class="grafik-summary-value">
                Rp<?= number_format($totalBelum, 0, ',', '.') ?>
            </div>
            <div class="grafik-count">
                <?= number_format($jumlahBelum, 0, ',', '.') ?> data payroll
            </div>
        </div>
    </div>

    <div class="grafik-gaji-area">
        <?php if (empty($dataGrafik)): ?>
            <div class="grafik-gaji-empty">
                Belum ada data payroll yang dapat ditampilkan pada grafik.
            </div>
        <?php else: ?>
            <?php foreach ($dataGrafik as $item): ?>
                <?php
                $total = $item['total_gaji'];
                $sudah = $item['total_dibayar'];
                $belum = $item['total_belum_dibayar'];

                $lebarTotal = ($total / $nilaiTerbesar) * 100;
                $persenSudah = $total > 0 ? ($sudah / $total) * 100 : 0;
                $persenBelum = $total > 0 ? ($belum / $total) * 100 : 0;
                ?>

                <div class="grafik-gaji-row">
                    <div class="grafik-gaji-period">
                        <?= grafik_h($item['bulan']) ?>
                        <?= grafik_h($item['tahun']) ?>
                    </div>

                    <div class="grafik-gaji-track">
                        <div
                            class="grafik-gaji-total"
                            style="width:<?= number_format($lebarTotal, 2, '.', '') ?>%;"
                        >
                            <div
                                class="grafik-gaji-paid"
                                title="Sudah dibayar"
                                style="width:<?= number_format($persenSudah, 2, '.', '') ?>%;"
                            ></div>

                            <div
                                class="grafik-gaji-unpaid"
                                title="Belum dibayar"
                                style="width:<?= number_format($persenBelum, 2, '.', '') ?>%;"
                            ></div>
                        </div>
                    </div>

                    <div class="grafik-gaji-nominal">
                        Rp<?= number_format($total, 0, ',', '.') ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="grafik-gaji-legend">
                <div class="grafik-legend-item">
                    <span class="grafik-legend-box grafik-legend-paid"></span>
                    Sudah Dibayar
                </div>

                <div class="grafik-legend-item">
                    <span class="grafik-legend-box grafik-legend-unpaid"></span>
                    Belum Dibayar
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
PHPFILE;

if (file_put_contents($grafikFile, $grafikContent) === false) {
    die("GAGAL: Tidak dapat membuat laporan/grafik_laporan.php");
}

echo "OK: File laporan/grafik_laporan.php berhasil dibuat.\n";

$isiLaporan = file_get_contents($laporanFile);

if ($isiLaporan === false) {
    die("GAGAL: Tidak dapat membaca laporan/laporan.php");
}

if (strpos($isiLaporan, "grafik_laporan.php") === false) {
    $backupFile = $laporanDir . '/laporan_backup_sebelum_grafik_' . date('Ymd_His') . '.php';

    if (!copy($laporanFile, $backupFile)) {
        die("GAGAL: Tidak dapat membuat backup laporan.php");
    }

    echo "OK: Backup laporan.php berhasil dibuat.\n";

    $includeGrafik = "\n<?php require __DIR__ . '/grafik_laporan.php'; ?>\n";

    $polaFooter = '/<\?php\s+require(?:_once)?\s+__DIR__\s*\.\s*[\'"]\/\.\.\/layout\/footer\.php[\'"]\s*;\s*\?>/i';

    if (preg_match($polaFooter, $isiLaporan)) {
        $isiBaru = preg_replace(
            $polaFooter,
            $includeGrafik . "\n$0",
            $isiLaporan,
            1
        );
    } else {
        $isiBaru = $isiLaporan . $includeGrafik;
    }

    if (file_put_contents($laporanFile, $isiBaru) === false) {
        die("GAGAL: Tidak dapat memperbarui laporan/laporan.php");
    }

    echo "OK: Grafik berhasil dihubungkan ke halaman Laporan Gaji.\n";
} else {
    echo "INFO: Grafik sudah pernah dihubungkan ke halaman laporan.\n";
}

echo "\n============================================================\n";
echo "PEMASANGAN GRAFIK SELESAI\n";
echo "============================================================\n\n";
echo "Buka halaman berikut:\n";
echo "http://localhost/payroll_star_final/laporan/laporan.php\n\n";
echo "Tekan Ctrl + F5 jika grafik belum langsung terlihat.\n";
echo "Setelah grafik tampil, hapus file pasang_grafik.php dari folder project.\n";

echo '</pre>';
?>
