<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$valid_token = false;

if($token) {
    $token_data = $user->verifyResetToken($token);
    if($token_data) {
        $valid_token = true;
    } else {
        $message = 'Token tidak valid atau sudah expired!';
        $message_type = 'danger';
    }
} else {
    $message = 'Token tidak ditemukan!';
    $message_type = 'danger';
}

if($_POST && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password) {
        $message = 'Password dan konfirmasi password tidak sama!';
        $message_type = 'danger';
    } elseif(strlen($password) < 6) {
        $message = 'Password minimal 6 karakter!';
        $message_type = 'danger';
    } else {
        if($user->resetPassword($token, $password)) {
            $message = 'Password berhasil direset! Silakan login dengan password baru.';
            $message_type = 'success';
            $valid_token = false;
        } else {
            $message = 'Terjadi kesalahan saat reset password!';
            $message_type = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Digital Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="p-5 text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Reset Password</h1>
                                    <p class="text-muted">Masukkan password baru untuk akun Anda.</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Password Baru</h1>
                                    </div>
                                    
                                    <?php if($message): ?>
                                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                        <?php echo $message; ?>
                                        <?php if($message_type == 'success'): ?>
                                        <br><a href="login.php">Klik di sini untuk login</a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if($valid_token): ?>
                                    <form method="POST" class="user">
                                        <div class="form-group mb-3">
                                            <input type="password" class="form-control form-control-user" 
                                                   name="password" placeholder="Password Baru" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <input type="password" class="form-control form-control-user" 
                                                   name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block w-100">
                                            Reset Password
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="login.php">← Kembali ke Login</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>