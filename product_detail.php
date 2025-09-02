<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$product_id = $_GET['id'] ?? 0;

// Get product details
$query = "SELECT p.*, c.name as category_name FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.id = :id AND p.is_active = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product_data = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product_data) {
    header('Location: products.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product_data['name']); ?> - Digital Store</title>
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
                        <a class="nav-link" href="products.php">Produk</a>
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

    <!-- Product Detail -->
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="products.php">Produk</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product_data['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo $product_data['image_url'] ?? 'https://via.placeholder.com/500x400'; ?>" 
                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($product_data['name']); ?>">
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product_data['name']); ?></h1>
                <p class="text-muted mb-3">
                    <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($product_data['category_name']); ?></span>
                    <small>Stok tersedia: <?php echo $product_data['stock_quantity']; ?></small>
                </p>
                
                <h3 class="text-primary mb-4">Rp <?php echo number_format($product_data['price'], 0, ',', '.'); ?></h3>
                
                <div class="mb-4">
                    <h5>Deskripsi Produk</h5>
                    <p><?php echo nl2br(htmlspecialchars($product_data['description'])); ?></p>
                </div>

                <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Jumlah:</label>
                        <div class="input-group" style="width: 150px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?php echo $product_data['stock_quantity']; ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button onclick="addToCart(<?php echo $product_data['id']; ?>)" class="btn btn-primary btn-lg me-md-2">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                        <button onclick="buyNow(<?php echo $product_data['id']; ?>)" class="btn btn-success btn-lg">
                            <i class="fas fa-bolt"></i> Beli Sekarang
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <a href="login.php">Login</a> untuk dapat membeli produk ini.
                    </div>
                <?php endif; ?>
            </div>
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
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseInt(quantityInput.value);
            let newValue = currentValue + change;
            
            if(newValue >= 1 && newValue <= <?php echo $product_data['stock_quantity']; ?>) {
                quantityInput.value = newValue;
            }
        }

        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            fetch('cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId + '&quantity=' + quantity
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
        }

        function buyNow(productId) {
            const quantity = document.getElementById('quantity').value;
            // Redirect to checkout with product info
            window.location.href = 'checkout.php?product_id=' + productId + '&quantity=' + quantity;
        }
    </script>
</body>
</html>