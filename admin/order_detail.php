<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Order.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$order_id = $_GET['id'];
$order_detail = $order->getById($order_id);
$order_items = $order->getOrderItems($order_id);

if (!$order_detail) {
    header('Location: orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order_detail['id']; ?> - Digital Store</title>
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
                            <a class="nav-link active text-white" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Kelola Pesanan
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
                    <h1 class="h2">Detail Pesanan #<?php echo $order_detail['id']; ?></h1>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan
                    </a>
                </div>

                <div class="row">
                    <!-- Order Information -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Informasi Pesanan</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>ID Pesanan:</strong> #<?php echo $order_detail['id']; ?></p>
                                        <p><strong>Tanggal Pesanan:</strong> <?php echo date('d/m/Y H:i:s', strtotime($order_detail['created_at'])); ?></p>
                                        <p><strong>Total Amount:</strong> Rp <?php echo number_format($order_detail['total_amount'], 0, ',', '.'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status Pesanan:</strong> 
                                            <span class="badge bg-<?php echo $order_detail['status'] == 'completed' ? 'success' : ($order_detail['status'] == 'pending' ? 'warning' : ($order_detail['status'] == 'paid' ? 'info' : 'danger')); ?>">
                                                <?php echo ucfirst($order_detail['status']); ?>
                                            </span>
                                        </p>
                                        <p><strong>Status Pembayaran:</strong> 
                                            <span class="badge bg-<?php echo $order_detail['payment_status'] == 'paid' ? 'success' : ($order_detail['payment_status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($order_detail['payment_status']); ?>
                                            </span>
                                        </p>
                                        <p><strong>Metode Pembayaran:</strong> <?php echo $order_detail['payment_method'] ?? 'Belum dipilih'; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Item Pesanan</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Harga</th>
                                                <th>Jumlah</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($item['image_url']): ?>
                                                            <img src="../<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-active">
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th>Rp <?php echo number_format($order_detail['total_amount'], 0, ',', '.'); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Informasi Customer</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Nama:</strong> <?php echo htmlspecialchars($order_detail['customer_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order_detail['customer_email']); ?></p>
                                <p><strong>Telepon:</strong> <?php echo htmlspecialchars($order_detail['customer_phone'] ?? 'Tidak tersedia'); ?></p>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Aksi Cepat</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="orders.php">
                                    <input type="hidden" name="order_id" value="<?php echo $order_detail['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Update Status Pesanan:</label>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?php echo $order_detail['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $order_detail['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="completed" <?php echo $order_detail['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order_detail['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" value="1" class="btn btn-primary btn-sm mt-2 w-100">
                                            <i class="fas fa-save"></i> Update Status
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="orders.php">
                                    <input type="hidden" name="order_id" value="<?php echo $order_detail['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Update Status Pembayaran:</label>
                                        <select name="payment_status" class="form-select">
                                            <option value="pending" <?php echo $order_detail['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $order_detail['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="failed" <?php echo $order_detail['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                        <button type="submit" name="update_payment_status" value="1" class="btn btn-success btn-sm mt-2 w-100">
                                            <i class="fas fa-credit-card"></i> Update Pembayaran
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>