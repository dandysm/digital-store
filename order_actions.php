<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Order.php';
require_once 'classes/Product.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_POST && isset($_POST['action'])) {
    $database = new Database();
    $db = $database->getConnection();
    $order = new Order($db);
    
    $action = $_POST['action'];
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit();
    }
    
    // Verify order belongs to current user
    $orderData = $order->getById($orderId);
    if (!$orderData || $orderData['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit();
    }
    
    switch ($action) {
        case 'cancel':
            // Only allow cancellation for pending or processing orders
            if ($orderData['status'] != 'pending' && $orderData['status'] != 'processing') {
                echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
                exit();
            }
            
            try {
                $db->beginTransaction();
                
                // Update order status to cancelled
                $query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$orderId]);
                
                // Restore product stock
                $orderItems = $order->getOrderItems($orderId);
                if ($orderItems) {
                    while ($item = $orderItems->fetch(PDO::FETCH_ASSOC)) {
                        $updateStock = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                        $stockStmt = $db->prepare($updateStock);
                        $stockStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
            }
            break;
            
        case 'reorder':
            try {
                $product = new Product($db);
                $orderItems = $order->getOrderItems($orderId);
                
                if (!$orderItems) {
                    echo json_encode(['success' => false, 'message' => 'No items found in this order']);
                    exit();
                }
                
                // Initialize cart if not exists
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                $addedItems = 0;
                $outOfStockItems = [];
                
                while ($item = $orderItems->fetch(PDO::FETCH_ASSOC)) {
                    $productData = $product->readOne($item['product_id']);
                    
                    if ($productData && $productData['is_active'] == 1) {
                        $availableStock = $productData['stock_quantity'];
                        $requestedQty = $item['quantity'];
                        
                        if ($availableStock >= $requestedQty) {
                            // Add to cart or update quantity
                            if (isset($_SESSION['cart'][$item['product_id']])) {
                                $_SESSION['cart'][$item['product_id']] += $requestedQty;
                            } else {
                                $_SESSION['cart'][$item['product_id']] = $requestedQty;
                            }
                            $addedItems++;
                        } else {
                            $outOfStockItems[] = $productData['name'] . ' (Available: ' . $availableStock . ', Requested: ' . $requestedQty . ')';
                        }
                    } else {
                        $outOfStockItems[] = 'Product ID ' . $item['product_id'] . ' (No longer available)';
                    }
                }
                
                if ($addedItems > 0) {
                    $message = $addedItems . ' items added to cart';
                    if (!empty($outOfStockItems)) {
                        $message .= '. Some items were not added due to insufficient stock: ' . implode(', ', $outOfStockItems);
                    }
                    echo json_encode(['success' => true, 'message' => $message]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No items could be added to cart. ' . implode(', ', $outOfStockItems)]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error processing reorder: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>