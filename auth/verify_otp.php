<?php
require_once __DIR__ . '/../config/koneksi.php';
$error = '';
if (!isset($_SESSION['reset_user_id'], $_SESSION['reset_user_pk'], $_SESSION['testing_otp'])) {
    redirect('auth/forgot_password.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    if ($otp === '') {
        $error = 'Kode OTP wajib diisi.';
    } elseif (hash_equals((string)$_SESSION['testing_otp'], $otp)) {
        $_SESSION['otp_verified'] = true;
        redirect('auth/reset_password.php');
    } else {
        $error = 'Kode OTP tidak sesuai.';
    }
}
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verifikasi OTP</title>
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head><body class="login-page">
<div class="container"><div class="row justify-content-center"><div class="col-md-5 col-lg-4">
<div class="card border-0 shadow-sm rounded-4 p-4">
<h1 class="h3 text-center mb-3">Verifikasi OTP</h1>
<div class="alert alert-info text-center">Mode uji coba aktif.<br>Kode OTP Anda:<div class="fs-3 fw-bold letter-spacing mt-1"><?= e($_SESSION['testing_otp']) ?></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><label class="form-label">Masukkan Kode OTP</label><input class="form-control form-control-lg text-center" maxlength="6" name="otp" required><button class="btn btn-primary btn-lg w-100 mt-3">Verifikasi</button></form>
<div class="text-center mt-3"><a href="forgot_password.php">Kirim ulang OTP</a></div>
</div></div></div></div></body></html>
