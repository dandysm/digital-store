<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$stmt = $product->readAll();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Digital Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-digital-tachograph"></i> Digital Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Produk</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Keranjang
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_orders.php">
                                <i class="fas fa-list"></i> Pesanan Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Products Section -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Semua Produk Digital</h2>
            </div>
        </div>
        
        <div class="row">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $prod): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $prod['image_url'] ?? 'https://via.placeholder.com/300x200'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($prod['name']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($prod['name']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($prod['description'], 0, 100)); ?>...</p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                                    <span class="text-muted">Stok: <?php echo $prod['stock_quantity']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-primary mb-0">Rp <?php echo number_format($prod['price'], 0, ',', '.'); ?></h5>
                                    <div>
                                        <a href="product_detail.php?id=<?php echo $prod['id']; ?>" class="btn btn-outline-primary btn-sm me-1">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                                            <button onclick="addToCart(<?php echo $prod['id']; ?>)" class="btn btn-primary btn-sm">
                                                <i class="fas fa-cart-plus"></i> Beli
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>Belum ada produk tersedia</h4>
                        <p>Silakan kembali lagi nanti.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p>&copy; 2024 Digital Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            <?php if(isset($_SESSION['user_id'])): ?>
                fetch('cart_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add&product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Produk berhasil ditambahkan ke keranjang!');
                    } else {
                        alert('Gagal menambahkan produk: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menambahkan produk.');
                });
            <?php else: ?>
                alert('Silakan login terlebih dahulu!');
                window.location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>