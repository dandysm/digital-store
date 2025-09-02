<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$cartItems = [];
$totalAmount = 0;

// Get cart items from database if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Ubah query untuk mengambil cart_id
    $query = "SELECT id as cart_id, product_id, quantity FROM cart WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    while ($cartItem = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productData = $product->readOne($cartItem['product_id']);
        if ($productData) {
            $itemTotal = $productData['price'] * $cartItem['quantity'];
            $cartItems[] = [
                'cart_id' => $cartItem['cart_id'],   // ID item di tabel cart
                'id' => $cartItem['product_id'],     // ID produk
                'name' => $productData['name'],
                'price' => $productData['price'],
                'image_url' => $productData['image_url'],
                'stock_quantity' => $productData['stock_quantity'],
                'quantity' => $cartItem['quantity'],
                'total' => $itemTotal
            ];
            $totalAmount += $itemTotal;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Digital Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> Digital Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart 
                            <span class="badge bg-light text-dark">
                                <?php 
                                if (isset($_SESSION['user_id'])) {
                                    $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $result['count'];
                                } else {
                                    echo 0;
                                }
                                ?>
                            </span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_orders.php">My Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
                
                <?php if (empty($cartItems)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <h4>Your cart is empty</h4>
                        <p>Start shopping to add items to your cart!</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Cart Items</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="cart-item border-bottom p-3" data-cart-id="<?php echo $item['cart_id']; ?>">
                                            <div class="row align-items-center">
                                                <div class="col-md-2">
                                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/100'); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         class="img-fluid rounded">
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <p class="text-muted mb-0">$<?php echo number_format($item['price'], 2); ?> each</p>
                                                    <small class="text-muted">Stock: <?php echo $item['stock_quantity']; ?></small>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="number" class="form-control text-center" 
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                               onchange="updateQuantityDirect(<?php echo $item['cart_id']; ?>, this.value)">
                                                        <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <strong>$<?php echo number_format($item['total'], 2); ?></strong>
                                                </div>
                                                <div class="col-md-1">
                                                    <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <strong>$<?php echo number_format($totalAmount, 2); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping:</span>
                                        <span class="text-success">Free</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>Total:</strong>
                                        <strong class="text-primary">$<?php echo number_format($totalAmount, 2); ?></strong>
                                    </div>
                                    
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="checkout.php" class="btn btn-primary w-100 mb-2">
                                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                                        </a>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <small>Please <a href="login.php">login</a> to proceed with checkout</small>
                                        </div>
                                        <a href="login.php" class="btn btn-primary w-100 mb-2">
                                            <i class="fas fa-sign-in-alt"></i> Login to Checkout
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="products.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(productId, change) {
            const input = document.querySelector(`[data-product-id="${productId}"] input[type="number"]`);
            const currentValue = parseInt(input.value);
            const newValue = currentValue + change;
            const maxValue = parseInt(input.max);
            
            if (newValue >= 1 && newValue <= maxValue) {
                updateQuantityDirect(productId, newValue);
            }
        }
        
        function updateQuantityDirect(productId, quantity) {
            if (quantity < 1) return;
            
            fetch('cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        }
        
        function removeFromCart(productId) {
            if (confirm('Are you sure you want to remove this item from cart?')) {
                fetch('cart_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error removing item');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }
        
        function updateQuantity(cartId, change) {
            const input = document.querySelector(`[data-cart-id="${cartId}"] input[type="number"]`);
            const currentValue = parseInt(input.value);
            const newValue = currentValue + change;
            const maxValue = parseInt(input.max);
            
            if (newValue >= 1 && newValue <= maxValue) {
                updateQuantityDirect(cartId, newValue);
            }
        }
        
        function updateQuantityDirect(cartId, quantity) {
            if (quantity < 1) return;
            
            fetch('cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&cart_id=${cartId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        }
        
        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from cart?')) {
                fetch('cart_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error removing item');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }
    </script>
</body>
</html>