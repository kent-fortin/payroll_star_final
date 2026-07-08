<?php
function base_path(): string
{
    // Determine the base path based on the location of index.php
    $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // If the script being accessed is inside a subdirectory (e.g., /auth/login.php),
    // we need to find the actual project root.
    // We can use the difference between the document root and the project directory.
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $projRoot = str_replace('\\', '/', dirname(__DIR__));
    
    if (strpos($projRoot, $docRoot) === 0) {
        $path = substr($projRoot, strlen($docRoot));
        return $path === '/' ? '' : $path;
    }
    
    return '';
}

function url(string $path = ''): string
{
    $base = rtrim(base_path(), '/');
    $path = ltrim($path, '/');
    return $path === '' ? ($base ?: '/') : $base . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['id_user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function is_pimpinan(): bool
{
    return ($_SESSION['role'] ?? '') === 'pimpinan';
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('warning', 'Menu tersebut hanya dapat diakses oleh admin.');
        redirect('dashboard.php');
    }
}

function require_pimpinan(): void
{
    require_login();
    if (!is_pimpinan()) {
        set_flash('warning', 'Menu tersebut hanya dapat diakses oleh pimpinan.');
        redirect('dashboard.php');
    }
}

function app_log(string $message): void
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents($dir . '/app.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function db_or_redirect($conn): void
{
    if (!$conn) {
        set_flash('danger', 'Aplikasi tidak dapat terhubung ke database.');
        redirect('auth/login.php');
    }
}

function bulan_list(): array
{
    return [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
}

function bulan_nomor(string $bulan): int
{
    foreach (bulan_list() as $nomor => $nama) {
        if (strcasecmp($nama, $bulan) === 0) {
            return $nomor;
        }
    }
    return 0;
}

function bulan_options(string $selected = ''): string
{
    $html = '';
    foreach (bulan_list() as $nama) {
        $sel = strcasecmp($selected, $nama) === 0 ? ' selected' : '';
        $html .= '<option value="' . e($nama) . '"' . $sel . '>' . e($nama) . '</option>';
    }
    return $html;
}

function current_month_name(): string
{
    return bulan_list()[(int)date('n')];
}

function get_setting(mysqli $conn, string $key, float $default = 0): float
{
    $keyEsc = mysqli_real_escape_string($conn, $key);
    $result = mysqli_query($conn, "SELECT nilai FROM pengaturan_payroll WHERE nama_pengaturan='$keyEsc' LIMIT 1");
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        return (float)$row['nilai'];
    }
    return $default;
}

function calculate_payroll(mysqli $conn, int $idKaryawan, string $bulan, int $tahun, float $tunjangan = 0): ?array
{
    $bulanEsc = mysqli_real_escape_string($conn, $bulan);
    $sql = "SELECT k.id_karyawan, k.nip, k.nama_karyawan, j.nama_jabatan, j.gaji_pokok,
                   a.id_absensi, a.hadir, a.sakit, a.izin, a.alpha, a.lembur_jam
            FROM karyawan k
            JOIN jabatan j ON j.id_jabatan = k.id_jabatan
            LEFT JOIN absensi a ON a.id_karyawan = k.id_karyawan
                AND a.bulan = '$bulanEsc' AND a.tahun = $tahun
            WHERE k.id_karyawan = $idKaryawan LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        app_log('calculate_payroll query: ' . mysqli_error($conn));
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    if (!$row || empty($row['id_absensi'])) {
        return null;
    }

    $tarifLembur = get_setting($conn, 'tarif_lembur_per_jam', 15000);
    $tarifAlpha = get_setting($conn, 'potongan_alpha_per_hari', 25000);
    $jamLembur = (int)$row['lembur_jam'];
    $jumlahAlpha = (int)$row['alpha'];
    $totalLembur = $jamLembur * $tarifLembur;
    $potonganAlpha = $jumlahAlpha * $tarifAlpha;
    $gajiPokok = (float)$row['gaji_pokok'];
    $gajiBersih = $gajiPokok + $totalLembur + $tunjangan - $potonganAlpha;

    return array_merge($row, [
        'bulan' => $bulan,
        'tahun' => $tahun,
        'tarif_lembur' => $tarifLembur,
        'total_lembur' => $totalLembur,
        'total_tunjangan' => $tunjangan,
        'tarif_alpha' => $tarifAlpha,
        'potongan_alpha' => $potonganAlpha,
        'gaji_bersih' => $gajiBersih,
    ]);
}

function generate_jabatan_code(int $id): string
{
    return 'JBT' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

function generate_nip(int $id): string
{
    return 'SSL' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

function status_badge(string $status): string
{
    $class = match ($status) {
        'Sudah Dibayar', 'Disetujui' => 'success',
        'Menunggu' => 'warning',
        'Ditolak' => 'danger',
        default => 'secondary',
    };
    return '<span class="badge text-bg-' . $class . '">' . e($status) . '</span>';
}

function filter_period(string $filter, string $bulan, int $tahun): array
{
    $month = bulan_nomor($bulan) ?: (int)date('n');
    $end = new DateTime(sprintf('%04d-%02d-01', $tahun, $month));
    if ($filter === '2bulan') {
        $start = (clone $end)->modify('-1 month');
    } elseif ($filter === '1tahun') {
        $start = (clone $end)->modify('-11 months');
    } else {
        $start = clone $end;
    }
    return [$start, $end];
}

function load_payroll_report(mysqli $conn, string $filter, string $bulan, int $tahun): array
{
    [$start, $end] = filter_period($filter, $bulan, $tahun);
    $sql = "SELECT p.*,k.nip,k.nama_karyawan,j.nama_jabatan
            FROM payroll p
            JOIN karyawan k ON k.id_karyawan=p.id_karyawan
            JOIN jabatan j ON j.id_jabatan=k.id_jabatan";
    $result = mysqli_query($conn, $sql);
    $rows = [];
    $groups = [];
    $summary = ['paid_count'=>0,'unpaid_count'=>0,'paid_total'=>0.0,'unpaid_total'=>0.0,'grand_total'=>0.0];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $monthNo = bulan_nomor($row['bulan']);
            if ($monthNo === 0) continue;
            $date = new DateTime(sprintf('%04d-%02d-01', (int)$row['tahun'], $monthNo));
            if ($date < $start || $date > $end) continue;
            $row['_date'] = $date->format('Y-m-d');
            $rows[] = $row;
            $key = $date->format('Y-m');
            if (!isset($groups[$key])) {
                $groups[$key] = ['label'=>$row['bulan'].' '.$row['tahun'],'paid'=>0.0,'unpaid'=>0.0,'total'=>0.0];
            }
            $amount = (float)$row['total_gaji_bersih'];
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
    }
    usort($rows, fn($a,$b) => strcmp($b['_date'].$b['nip'], $a['_date'].$a['nip']));
    ksort($groups);
    return ['rows'=>$rows,'groups'=>$groups,'summary'=>$summary,'start'=>$start,'end'=>$end];
}
