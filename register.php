<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error_message = '';
$success_message = '';

if($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    // Validation
    if($password !== $confirm_password) {
        $error_message = 'Password dan konfirmasi password tidak sama!';
    } elseif(strlen($password) < 6) {
        $error_message = 'Password minimal 6 karakter!';
    } elseif($user->emailExists($email)) {
        $error_message = 'Email sudah terdaftar!';
    } elseif($user->usernameExists($username)) {
        $error_message = 'Username sudah digunakan!';
    } else {
        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        $user->full_name = $full_name;
        $user->phone = $phone;
        $user->role = 'customer';
        
        if($user->register()) {
            $success_message = 'Registrasi berhasil! Silakan login.';
        } else {
            $error_message = 'Terjadi kesalahan saat registrasi!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Digital Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <div class="row">
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Bergabung dengan Kami!</h1>
                                <img src="images/register-image.jpg" class="img-fluid" alt="Register">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Buat Akun Baru</h1>
                            </div>
                            
                            <?php if($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                                <br><a href="login.php">Klik di sini untuk login</a>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="user">
                                <div class="form-group row mb-3">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" 
                                               name="full_name" placeholder="Nama Lengkap" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" 
                                               name="username" placeholder="Username" required>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <input type="email" class="form-control form-control-user" 
                                           name="email" placeholder="Email" required>
                                </div>
                                <div class="form-group mb-3">
                                    <input type="tel" class="form-control form-control-user" 
                                           name="phone" placeholder="Nomor Telepon">
                                </div>
                                <div class="form-group row mb-3">
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control form-control-user" 
                                               name="password" placeholder="Password" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control form-control-user" 
                                               name="confirm_password" placeholder="Konfirmasi Password" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-user btn-block w-100">
                                    Daftar Akun
                                </button>
                            </form>
                            
                            <hr>
                            <div class="text-center">
                                <a class="small" href="login.php">Sudah punya akun? Login!</a>
                            </div>
                            <div class="text-center">
                                <a class="small" href="index.php">← Kembali ke Beranda</a>
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