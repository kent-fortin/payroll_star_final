<?php
require_once __DIR__ . '/../config/koneksi.php';
if (is_logged_in() && $conn) {
    redirect('dashboard.php');
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Payroll</title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body class="login-page">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <div class="text-center mb-4">
                    <img src="<?= asset('img/logo.png') ?>" alt="Logo" style="height:90px;object-fit:contain">
                    <h1 class="h2 mt-3 mb-1">Login Payroll</h1>
                    <div class="text-muted">PT Star Samudera Logistik</div>
                </div>
                <?php if ($flash): ?>
                <script>
                    window.flashMessage = {
                        type: '<?= e($flash['type']) === 'danger' ? 'error' : (e($flash['type']) === 'warning' ? 'warning' : 'success') ?>',
                        text: '<?= addslashes(e($flash['message'])) ?>'
                    };
                </script>
                <?php endif; ?>
                <form action="proses_login.php" method="post">
                    <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control form-control-lg" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control form-control-lg" required></div>
                    <button class="btn btn-primary btn-lg w-100" type="submit">Login</button>
                </form>
                <div class="text-center mt-3"><a href="forgot_password.php">Lupa Password?</a></div>
                <div class="small text-muted mt-4">Demo: admin/admin123 atau pimpinan/pimpinan123</div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"></script>
<script>
    if (window.flashMessage) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
        Toast.fire({
            icon: window.flashMessage.type,
            title: window.flashMessage.text
        });
    }
</script>
</body>
</html>
