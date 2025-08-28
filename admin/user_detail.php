<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Order.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$order = new Order($db);

$user_id = $_GET['id'];
$user_data = $user->getUserById($user_id);

if (!$user_data) {
    header('Location: users.php');
    exit();
}

// Get user's orders
// Baris 30 - Method ini tidak ada di class Order
$user_orders = $order->getOrdersByUserId($user_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User - <?php echo htmlspecialchars($user_data['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h5 class="sidebar-heading px-3 mt-4 mb-1 text-muted">Admin Panel</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box"></i> Kelola Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Kelola Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="users.php">
                                <i class="fas fa-users"></i> Kelola User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detail User</h1>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="row">
                    <!-- User Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user"></i> Informasi User</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>ID:</strong></td>
                                        <td><?php echo $user_data['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td><?php echo htmlspecialchars($user_data['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nama Lengkap:</strong></td>
                                        <td><?php echo htmlspecialchars($user_data['full_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Telepon:</strong></td>
                                        <td><?php echo htmlspecialchars($user_data['phone'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user_data['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                                <?php echo ucfirst($user_data['role']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user_data['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $user_data['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Terdaftar:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user_data['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- User Statistics -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Statistik</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <h3 class="text-primary"><?php echo count($user_orders); ?></h3>
                                            <p class="text-muted mb-0">Total Pesanan</p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="text-success">
                                            Rp <?php echo number_format(array_sum(array_column($user_orders, 'total_amount')), 0, ',', '.'); ?>
                                        </h3>
                                        <p class="text-muted mb-0">Total Belanja</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs"></i> Aksi Cepat</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-warning" onclick="toggleUserStatus(<?php echo $user_data['id']; ?>, <?php echo $user_data['is_active'] ? 0 : 1; ?>)">
                                        <i class="fas fa-<?php echo $user_data['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        <?php echo $user_data['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?> User
                                    </button>
                                    <button class="btn btn-info" onclick="changeUserRole(<?php echo $user_data['id']; ?>, '<?php echo $user_data['role'] === 'admin' ? 'customer' : 'admin'; ?>')">
                                        <i class="fas fa-user-cog"></i>
                                        Ubah ke <?php echo $user_data['role'] === 'admin' ? 'Customer' : 'Admin'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Orders -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-shopping-cart"></i> Riwayat Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">User ini belum memiliki pesanan</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Pesanan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Pembayaran</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_orders as $order_item): ?>
                                    <tr>
                                        <td>#<?php echo $order_item['id']; ?></td>
                                        <td>Rp <?php echo number_format($order_item['total_amount'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order_item['status'] === 'completed' ? 'success' : 
                                                    ($order_item['status'] === 'pending' ? 'warning' : 
                                                    ($order_item['status'] === 'processing' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($order_item['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $order_item['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order_item['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order_item['created_at'])); ?></td>
                                        <td>
                                            <a href="order_detail.php?id=<?php echo $order_item['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'mengaktifkan' : 'menonaktifkan';
            if (confirm(`Apakah Anda yakin ingin ${action} user ini?`)) {
                fetch('users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_status&user_id=${userId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal mengupdate status user!');
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan!');
                });
            }
        }

        function changeUserRole(userId, newRole) {
            if (confirm(`Apakah Anda yakin ingin mengubah role user ini menjadi ${newRole}?`)) {
                fetch('users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_role&user_id=${userId}&role=${newRole}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal mengupdate role user!');
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan!');
                });
            }
        }
    </script>
</body>
</html>