<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Order.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$order = new Order($db); // ← BARIS 14

// Get user's orders
$orders = $order->getOrdersByUserId($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Digital Store</title>
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
                            <span class="badge bg-light text-dark"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_orders.php">My Orders</a>
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
                <h2><i class="fas fa-list-alt"></i> My Orders</h2>
                
                <?php if ($orders && $orders->rowCount() > 0): ?>
                    <div class="row">
                        <?php while ($orderData = $orders->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">Order #<?php echo str_pad($orderData['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                            <small class="text-muted">Placed on <?php echo date('M d, Y', strtotime($orderData['created_at'])); ?></small>
                                        </div>
                                        <div>
                                            <?php
                                            $statusClass = '';
                                            switch($orderData['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'bg-info';
                                                    break;
                                                case 'shipped':
                                                    $statusClass = 'bg-primary';
                                                    break;
                                                case 'delivered':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($orderData['status']); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6>Order Details:</h6>
                                                <?php
                                                // Get order items
                                                $orderItems = $order->getOrderItems($orderData['id']);
                                                if ($orderItems && $orderItems->rowCount() > 0):
                                                ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Product</th>
                                                                    <th>Quantity</th>
                                                                    <th>Price</th>
                                                                    <th>Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php while ($item = $orderItems->fetch(PDO::FETCH_ASSOC)): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                                        <td><?php echo $item['quantity']; ?></td>
                                                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($orderData['shipping_address'])): ?>
                                                    <h6 class="mt-3">Shipping Address:</h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($orderData['shipping_address']); ?></p>
                                                    <p class="mb-1"><?php echo htmlspecialchars($orderData['city']); ?>, <?php echo htmlspecialchars($orderData['postal_code']); ?></p>
                                                    <p class="mb-0">Phone: <?php echo htmlspecialchars($orderData['phone']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($orderData['notes'])): ?>
                                                    <h6 class="mt-3">Order Notes:</h6>
                                                    <p class="mb-0"><?php echo htmlspecialchars($orderData['notes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6>Order Summary</h6>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Total Amount:</span>
                                                            <strong>$<?php echo number_format($orderData['total_amount'], 2); ?></strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Payment Method:</span>
                                                            <span><?php echo htmlspecialchars($orderData['payment_method']); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Payment Status:</span>
                                                            <span class="badge <?php echo $orderData['payment_status'] == 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                                <?php echo ucfirst($orderData['payment_status']); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <?php if ($orderData['status'] == 'pending' || $orderData['status'] == 'processing'): ?>
                                                            <div class="mt-3">
                                                                <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $orderData['id']; ?>)">
                                                                    <i class="fas fa-times"></i> Cancel Order
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($orderData['status'] == 'delivered'): ?>
                                                            <div class="mt-3">
                                                                <button class="btn btn-outline-primary btn-sm" onclick="reorder(<?php echo $orderData['id']; ?>)">
                                                                    <i class="fas fa-redo"></i> Reorder
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-shopping-bag fa-3x mb-3"></i>
                        <h4>No Orders Yet</h4>
                        <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('order_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancel&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error cancelling order');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling order');
                });
            }
        }
        
        function reorder(orderId) {
            if (confirm('Add all items from this order to your cart?')) {
                fetch('order_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reorder&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Items added to cart successfully!');
                        window.location.href = 'cart.php';
                    } else {
                        alert(data.message || 'Error adding items to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error processing reorder');
                });
            }
        }
    </script>
</body>
</html>