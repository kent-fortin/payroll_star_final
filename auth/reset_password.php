<?php
require_once __DIR__ . '/../config/koneksi.php';
$error = '';
$success = '';
if (!isset($_SESSION['reset_user_id'], $_SESSION['reset_user_pk']) || empty($_SESSION['otp_verified'])) {
    redirect('auth/forgot_password.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');
    if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $hashEsc = mysqli_real_escape_string($conn, $hash);
        $pk = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_SESSION['reset_user_pk']);
        $id = (int)$_SESSION['reset_user_id'];
        $ok = mysqli_query($conn, "UPDATE users SET password='$hashEsc',reset_otp=NULL,reset_otp_expired_at=NULL WHERE `$pk`=$id");
        if ($ok) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_user_pk'], $_SESSION['testing_otp'], $_SESSION['otp_verified']);
            $success = 'Password berhasil diubah.';
        } else {
            app_log('Reset password failed: ' . mysqli_error($conn));
            $error = 'Password gagal diubah. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password</title>
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head><body class="login-page"><div class="container"><div class="row justify-content-center"><div class="col-md-5 col-lg-4"><div class="card border-0 shadow-sm rounded-4 p-4">
<h1 class="h3 text-center mb-3">Reset Password</h1>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?><br><a href="login.php">Login sekarang</a></div><?php else: ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><label class="form-label">Password Baru</label><input type="password" name="password" class="form-control" required><label class="form-label mt-3">Konfirmasi Password</label><input type="password" name="confirm" class="form-control" required><button class="btn btn-primary w-100 mt-3">Simpan Password Baru</button></form>
<?php endif; ?><div class="text-center mt-3"><a href="login.php">Kembali ke Login</a></div>
</div></div></div></div></body></html>
