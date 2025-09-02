<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Order.php';
require_once 'classes/PaymentSettings.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$paymentSettings = new PaymentSettings($db);

// Get cart items and calculate total
$cartItems = [];
$totalAmount = 0;

foreach ($_SESSION['cart'] as $item) {
    // Assuming cart structure is array of items with product_id and quantity
    $productId = isset($item['product_id']) ? $item['product_id'] : $item;
    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
    
    $productData = $product->readOne($productId);
    if ($productData && $productData['is_active'] == 1) {
        $itemTotal = $productData['price'] * $quantity;
        $cartItems[] = [
            'id' => $productId,
            'name' => $productData['name'],
            'price' => $productData['price'],
            'quantity' => $quantity,
            'total' => $itemTotal,
            'stock_available' => $productData['stock_quantity']
        ];
        $totalAmount += $itemTotal;
    }
}

// Get payment settings
// Ganti dengan metode yang sudah ada
$paymentMethods = [
    ['id' => 1, 'method_name' => 'Transfer Bank', 'description' => 'Transfer ke rekening bank kami'],
    ['id' => 2, 'method_name' => 'E-Wallet', 'description' => 'Bayar dengan e-wallet'],
    ['id' => 3, 'method_name' => 'COD', 'description' => 'Cash on Delivery']
];

// Handle form submission
// At the top after session_start()
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form validation
if ($_POST && isset($_POST['place_order'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $shipping_address = trim($_POST['shipping_address']);
        $city = trim($_POST['city']);
        $postal_code = trim($_POST['postal_code']);
        $phone = trim($_POST['phone']);
        $payment_method = $_POST['payment_method'];
        $notes = trim($_POST['notes']);
        
        $errors = [];
        
        // Validation
        if (empty($shipping_address)) $errors[] = "Shipping address is required";
        if (empty($city)) $errors[] = "City is required";
        if (empty($postal_code)) $errors[] = "Postal code is required";
        if (empty($phone)) $errors[] = "Phone number is required";
        if (empty($payment_method)) $errors[] = "Payment method is required";
        
        if (empty($errors)) {
            // Validate stock before processing
            foreach ($cartItems as $item) {
                $currentProduct = $product->readOne($item['id']);
                if (!$currentProduct || $currentProduct['stock_quantity'] < $item['quantity']) {
                    $errors[] = "Insufficient stock for product: " . $item['name'];
                }
            }
            
            if (empty($errors)) {
                try {
                    $db->beginTransaction();
                    
                    // Create order
                    $order = new Order($db);
                    $order->user_id = $_SESSION['user_id'];
                    $order->total_amount = $totalAmount;
                    $order->status = 'pending';
                    $order->payment_method = $payment_method;
                    $order->payment_status = 'pending';
                    $order->shipping_address = $shipping_address;
                    $order->city = $city;
                    $order->postal_code = $postal_code;
                    $order->phone = $phone;
                    $order->notes = $notes;
                    
                    if ($order->create()) {
                        $orderId = $db->lastInsertId();
                        
                        // Create order items
                        foreach ($cartItems as $item) {
                            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
                            
                            // Update product stock
                            $updateStock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                            $stockStmt = $db->prepare($updateStock);
                            $stockStmt->execute([$item['quantity'], $item['id']]);
                        }
                        
                        $db->commit();
                        
                        // Clear cart
                        unset($_SESSION['cart']);
                        
                        // Redirect to success page
                        $_SESSION['order_success'] = $orderId;
                        header('Location: order_success.php?order_id=' . $orderId);
                        exit();
                    } else {
                        throw new Exception("Failed to create order");
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Checkout error for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
                    $errors[] = "Error processing order. Please try again or contact support.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Digital Store</title>
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
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-credit-card"></i> Checkout</h2>
                
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                    </ol>
                </nav>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Shipping Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-shipping-fast"></i> Shipping Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="shipping_address" class="form-label">Shipping Address *</label>
                                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : ''; ?></textarea>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City *</label>
                                            <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="postal_code" class="form-label">Postal Code *</label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>" required>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label for="phone" class="form-label">Phone Number *</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="notes" class="form-label">Order Notes (Optional)</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions for your order..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Method</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($paymentMethods)): ?>
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="payment_method" id="payment_<?php echo $method['id']; ?>" value="<?php echo htmlspecialchars($method['method_name']); ?>" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == $method['method_name']) ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="payment_<?php echo $method['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($method['method_name']); ?></strong>
                                                    <?php if (!empty($method['description'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($method['description']); ?></small>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            No payment methods available. Please contact administrator.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Order Summary -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <small><?php echo htmlspecialchars($item['name']); ?></small>
                                                <br><small class="text-muted">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                                            </div>
                                            <small>$<?php echo number_format($item['total'], 2); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <hr>
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
                                    
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" name="place_order" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-check"></i> Place Order
                                    </button>
                                    
                                    <a href="cart.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-arrow-left"></i> Back to Cart
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const submitBtn = document.querySelector('button[name="place_order"]');
        
        form.addEventListener('submit', function(e) {
            const requiredFields = ['shipping_address', 'city', 'postal_code', 'phone'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                alert('Please select a payment method');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });
    </script>
</body>
</html>