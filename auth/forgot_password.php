<?php
require_once __DIR__ . '/../config/koneksi.php';
if (!$conn) {
    set_flash('danger', 'Fitur lupa password belum dapat digunakan.');
    redirect('auth/login.php');
}
$error = '';

function user_primary_key(mysqli $conn): ?string
{
    $result = mysqli_query($conn, 'SHOW COLUMNS FROM users');
    if (!$result) return null;
    while ($row = mysqli_fetch_assoc($result)) {
        if (($row['Key'] ?? '') === 'PRI') return $row['Field'];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    if ($identifier === '') {
        $error = 'Username wajib diisi.';
    } else {
        $identifierEsc = mysqli_real_escape_string($conn, $identifier);
        $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$identifierEsc' LIMIT 1");
        $user = $result ? mysqli_fetch_assoc($result) : null;
        $pk = user_primary_key($conn);
        if (!$user || !$pk) {
            $error = 'Akun tidak ditemukan.';
        } else {
            $otp = (string)random_int(100000, 999999);
            $expired = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $id = (int)$user[$pk];
            $otpEsc = mysqli_real_escape_string($conn, $otp);
            $expiredEsc = mysqli_real_escape_string($conn, $expired);
            $ok = mysqli_query($conn, "UPDATE users SET reset_otp='$otpEsc', reset_otp_expired_at='$expiredEsc' WHERE `$pk`=$id");
            if ($ok) {
                $_SESSION['reset_user_id'] = $id;
                $_SESSION['reset_user_pk'] = $pk;
                $_SESSION['testing_otp'] = $otp;
                header('Location: verify_otp.php');
                exit;
            }
            app_log('OTP update failed: ' . mysqli_error($conn));
            $error = 'Kode OTP gagal dibuat. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lupa Password</title>
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="login-page">
<div class="container"><div class="row justify-content-center"><div class="col-md-5 col-lg-4">
<div class="card border-0 shadow-sm rounded-4 p-4">
<h1 class="h3 text-center mb-3">Lupa Password</h1>
<div class="alert alert-info small">Mode uji coba aktif. Kode OTP akan ditampilkan pada halaman verifikasi.</div>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<label class="form-label">Username</label>
<input class="form-control form-control-lg" name="identifier" required placeholder="Masukkan username Anda">
<button class="btn btn-primary btn-lg w-100 mt-3">Buat Kode OTP</button>
</form>
<div class="text-center mt-3"><a href="login.php">Kembali ke Login</a></div>
</div></div></div></div>
</body></html>
