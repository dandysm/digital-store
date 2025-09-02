<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php'; // Tambahkan baris ini

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

if($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add':
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            // Validasi stok
            $product = new Product($db);
            $product_data = $product->readOne($product_id);
            
            if (!$product_data || $product_data['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit;
            }
            
            // Check if product already in cart
            $existing_quantity = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    if ($item['product_id'] == $product_id) {
                        $existing_quantity = $item['quantity'];
                        break;
                    }
                }
            }
            
            // Check total quantity doesn't exceed stock
            if (($existing_quantity + $quantity) > $product_data['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Total quantity exceeds available stock']);
                exit;
            }
            // Check if item already in cart
            $query = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($existing_item) {
                // Update quantity
                $new_quantity = $existing_item['quantity'] + $quantity;
                if($new_quantity > $product_data['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Total quantity melebihi stok']);
                    exit();
                }
                
                $query = "UPDATE cart SET quantity = :quantity WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':quantity', $new_quantity);
                $stmt->bindParam(':id', $existing_item['id']);
            } else {
                // Add new item
                $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', $quantity);
            }
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan ke keranjang']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk']);
            }
            break;
            
        case 'update':
            $cart_id = $_POST['cart_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            if($quantity <= 0) {
                // Remove item
                $query = "DELETE FROM cart WHERE id = :id AND user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $cart_id);
                $stmt->bindParam(':user_id', $user_id);
            } else {
                // Update quantity
                $query = "UPDATE cart SET quantity = :quantity WHERE id = :id AND user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':id', $cart_id);
                $stmt->bindParam(':user_id', $user_id);
            }
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Keranjang berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal mengupdate keranjang']);
            }
            break;
            
        case 'remove':
            $cart_id = $_POST['cart_id'] ?? 0;
            
            $query = "DELETE FROM cart WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $cart_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus dari keranjang']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid']);
}
?>