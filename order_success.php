<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Order.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || !isset($_SESSION['order_success'])) {
    header('Location: index.php');
    exit();
}

$orderId = $_GET['order_id'];

// Verify this order belongs to current user
$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$orderData = $order->getById($orderId);
if (!$orderData || $orderData['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

// Clear the success session
unset($_SESSION['order_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Digital Store</title>
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="my_orders.php">My Orders</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-success mb-3">Order Placed Successfully!</h2>
                        <p class="lead">Thank you for your purchase. Your order has been received and is being processed.</p>
                        
                        <div class="alert alert-info">
                            <strong>Order ID:</strong> #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?><br>
                            <strong>Total Amount:</strong> $<?php echo number_format($orderData['total_amount'], 2); ?><br>
                            <strong>Payment Method:</strong> <?php echo htmlspecialchars($orderData['payment_method']); ?><br>
                            <strong>Status:</strong> <span class="badge bg-warning"><?php echo ucfirst($orderData['status']); ?></span>
                        </div>
                        
                        <p>You will receive an email confirmation shortly. You can track your order status in your account.</p>
                        
                        <div class="mt-4">
                            <a href="my_orders.php" class="btn btn-primary me-2">
                                <i class="fas fa-list"></i> View My Orders
                            </a>
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>