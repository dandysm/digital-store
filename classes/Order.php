<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $user_id;
    public $total_amount;
    public $status;
    public $payment_method;
    public $payment_status;
    public $created_at;
    public $shipping_address;
    public $city;
    public $postal_code;
    public $phone;
    public $notes;
    
    public function create() {
        $query = "INSERT INTO orders SET 
                    user_id = :user_id,
                    total_amount = :total_amount,
                    status = :status,
                    payment_method = :payment_method,
                    payment_status = :payment_status,
                    shipping_address = :shipping_address,
                    city = :city,
                    postal_code = :postal_code,
                    phone = :phone,
                    notes = :notes,
                    created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':shipping_address', $this->shipping_address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':postal_code', $this->postal_code);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':notes', $this->notes);
        
        return $stmt->execute();
    }

    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getTotalRevenue() {
        $query = "SELECT SUM(total_amount) as total FROM " . $this->table_name . " WHERE status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getRecent($limit = 5) {
        $query = "SELECT o.*, u.full_name as customer_name FROM " . $this->table_name . " o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fungsi baru untuk pengelolaan pesanan
    public function getAll($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                 FROM " . $this->table_name . " o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC 
                 LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone 
                 FROM " . $this->table_name . " o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE o.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.name as product_name, p.image_url 
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function updatePaymentStatus($id, $payment_status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":payment_status", $payment_status);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function searchOrders($search_term, $status_filter = '') {
        $query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                 FROM " . $this->table_name . " o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE (u.full_name LIKE :search OR u.email LIKE :search OR o.id LIKE :search)";
        
        if (!empty($status_filter)) {
            $query .= " AND o.status = :status";
        }
        
        $query .= " ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $search_param = "%" . $search_term . "%";
        $stmt->bindParam(":search", $search_param);
        
        if (!empty($status_filter)) {
            $stmt->bindParam(":status", $status_filter);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method baru yang diperlukan untuk user_detail.php
    public function getOrdersByUserId($user_id) {
        $query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                 FROM " . $this->table_name . " o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE o.user_id = :user_id 
                 ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function __construct($db) {
        $this->conn = $db;
    }
}
?>