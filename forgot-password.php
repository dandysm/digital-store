<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$message_type = '';

if($_POST) {
    $email = $_POST['email'];
    
    $token = $user->createPasswordResetToken($email);
    
    if($token) {
        // In real application, send email with reset link
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/digitalweb/reset-password.php?token=" . $token;
        
        $message = "Link reset password telah dikirim ke email Anda. <br><br>";
        $message .= "<strong>Demo Link:</strong> <a href='" . $reset_link . "'>" . $reset_link . "</a>";
        $message_type = 'success';
    } else {
        $message = 'Email tidak ditemukan!';
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Digital Store</title>
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
                                    <h1 class="h4 text-gray-900 mb-4">Lupa Password?</h1>
                                    <p class="text-muted">Masukkan email Anda dan kami akan mengirimkan link untuk reset password.</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">Reset Password</h1>
                                        <p class="mb-4">Masukkan email Anda di bawah ini</p>
                                    </div>
                                    
                                    <?php if($message): ?>
                                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                        <?php echo $message; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="user">
                                        <div class="form-group mb-3">
                                            <input type="email" class="form-control form-control-user" 
                                                   name="email" placeholder="Masukkan Email..." required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block w-100">
                                            Kirim Link Reset
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Buat Akun Baru!</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Sudah ingat password? Login!</a>
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