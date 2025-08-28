<?php
session_start();
require_once '../config/database.php';
require_once '../classes/PaymentSettings.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$payment = new PaymentSettings($db);

$message = '';
$message_type = '';

if($_POST) {
    if(isset($_POST['action']) && $_POST['action'] == 'update_tripay') {
        $api_key = $_POST['api_key'];
        $private_key = $_POST['private_key'];
        $merchant_code = $_POST['merchant_code'];
        $is_sandbox = isset($_POST['is_sandbox']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if($payment->updateTripaySettings($api_key, $private_key, $merchant_code, $is_sandbox, $is_active)) {
            $message = 'Pengaturan Tripay berhasil diupdate!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengupdate pengaturan Tripay!';
            $message_type = 'danger';
        }
    }
}

$tripay_settings = $payment->getTripaySettings();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Admin Panel</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="products.php">
                                <i class="fas fa-box"></i> Kelola Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Kelola Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="payment-settings.php">
                                <i class="fas fa-credit-card"></i> Payment Gateway
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users"></i> Kelola User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../index.php">
                                <i class="fas fa-home"></i> Lihat Website
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Payment Gateway Settings</h1>
                </div>

                <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Tripay Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-credit-card"></i> Pengaturan Tripay Payment Gateway
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_tripay">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_key" class="form-label">API Key</label>
                                        <input type="text" class="form-control" name="api_key" 
                                               value="<?php echo $tripay_settings['api_key'] ?? ''; ?>" 
                                               placeholder="Masukkan API Key Tripay">
                                        <small class="form-text text-muted">Dapatkan dari dashboard Tripay</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="private_key" class="form-label">Private Key</label>
                                        <input type="text" class="form-control" name="private_key" 
                                               value="<?php echo $tripay_settings['private_key'] ?? ''; ?>" 
                                               placeholder="Masukkan Private Key Tripay">
                                        <small class="form-text text-muted">Jangan bagikan private key ke siapapun</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="merchant_code" class="form-label">Merchant Code</label>
                                        <input type="text" class="form-control" name="merchant_code" 
                                               value="<?php echo $tripay_settings['merchant_code'] ?? ''; ?>" 
                                               placeholder="Masukkan Merchant Code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="is_sandbox" 
                                                       <?php echo ($tripay_settings['is_sandbox'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Sandbox Mode</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="is_active" 
                                                       <?php echo ($tripay_settings['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Aktifkan Tripay</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Pengaturan
                                </button>
                                <button type="button" class="btn btn-info" onclick="testConnection()">
                                    <i class="fas fa-plug"></i> Test Koneksi
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="font-weight-bold">Informasi Tripay:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Status:</strong> 
                                        <span class="badge bg-<?php echo ($tripay_settings['is_active'] ?? 0) ? 'success' : 'danger'; ?>">
                                            <?php echo ($tripay_settings['is_active'] ?? 0) ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </li>
                                    <li><strong>Mode:</strong> 
                                        <span class="badge bg-<?php echo ($tripay_settings['is_sandbox'] ?? 1) ? 'warning' : 'success'; ?>">
                                            <?php echo ($tripay_settings['is_sandbox'] ?? 1) ? 'Sandbox' : 'Production'; ?>
                                        </span>
                                    </li>
                                    <li><strong>Last Updated:</strong> <?php echo $tripay_settings['updated_at'] ?? 'Belum diatur'; ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Panduan Setup Tripay:</h6>
                            <ol>
                                <li>Daftar akun di <a href="https://tripay.co.id" target="_blank">https://tripay.co.id</a></li>
                                <li>Verifikasi akun dan lengkapi data merchant</li>
                                <li>Dapatkan API Key dan Private Key dari dashboard</li>
                                <li>Masukkan kredensial di form di atas</li>
                                <li>Test koneksi untuk memastikan konfigurasi benar</li>
                                <li>Aktifkan payment gateway setelah testing berhasil</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testConnection() {
            // Implement AJAX call to test Tripay connection
            alert('Fitur test koneksi akan diimplementasikan dengan AJAX call ke Tripay API');
        }
    </script>
</body>
</html>