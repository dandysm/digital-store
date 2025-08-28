<?php
class User {
    private $conn;
    private $table_name = "users";
    private $password_resets_table = "password_resets";

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $phone;
    public $role;
    public $is_active;
    public $email_verified_at;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET username=:username, email=:email, password=:password, 
                     full_name=:full_name, phone=:phone, role=:role";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":role", $this->role);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login($email, $password) {
        $query = "SELECT id, username, email, password, full_name, role, is_active 
                 FROM " . $this->table_name . " 
                 WHERE email = :email AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    // Check if email exists
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Check if username exists
    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Create password reset token
    public function createPasswordResetToken($email) {
        // Check if email exists
        if(!$this->emailExists($email)) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $query = "INSERT INTO " . $this->password_resets_table . " 
                 SET email=:email, token=:token, expires_at=:expires_at";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expires_at);
        
        if($stmt->execute()) {
            return $token;
        }
        return false;
    }

    // Verify password reset token
    public function verifyResetToken($token) {
        $query = "SELECT email FROM " . $this->password_resets_table . " 
                 WHERE token = :token AND expires_at > NOW() AND used = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Reset password
    public function resetPassword($token, $new_password) {
        $token_data = $this->verifyResetToken($token);
        if(!$token_data) {
            return false;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":email", $token_data['email']);
        
        if($stmt->execute()) {
            // Mark token as used
            $update_token = "UPDATE " . $this->password_resets_table . " SET used = 1 WHERE token = :token";
            $stmt2 = $this->conn->prepare($update_token);
            $stmt2->bindParam(":token", $token);
            $stmt2->execute();
            
            return true;
        }
        return false;
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all users with pagination
    public function getAllUsers($limit = 10, $offset = 0, $search = '', $role_filter = '') {
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($role_filter)) {
            $where_conditions[] = "role = :role";
            $params[':role'] = $role_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT id, username, email, full_name, phone, role, is_active, created_at 
                 FROM " . $this->table_name . " 
                 " . $where_clause . "
                 ORDER BY created_at DESC 
                 LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Count total users
    public function countUsers($search = '', $role_filter = '') {
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($role_filter)) {
            $where_conditions[] = "role = :role";
            $params[':role'] = $role_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Update user role
    public function updateUserRole($user_id, $new_role) {
        $query = "UPDATE " . $this->table_name . " SET role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $new_role);
        $stmt->bindParam(':id', $user_id);
        
        return $stmt->execute();
    }

    // Update user status (active/inactive)
    public function updateUserStatus($user_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET is_active = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $user_id);
        
        return $stmt->execute();
    }

    // Delete user (soft delete by setting is_active to 0)
    public function deleteUser($user_id) {
        return $this->updateUserStatus($user_id, 0);
    }

    // Get user statistics
    public function getUserStats() {
        $stats = [];
        
        // Total users
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active users
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Users by role
        $query = "SELECT role, COUNT(*) as count FROM " . $this->table_name . " GROUP BY role";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    // Get recent users
    public function getRecentUsers($limit = 5) {
        $query = "SELECT id, username, email, full_name, role, created_at 
                 FROM " . $this->table_name . " 
                 ORDER BY created_at DESC 
                 LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update user profile
    public function updateProfile($user_id, $username, $email, $full_name, $phone) {
        $query = "UPDATE " . $this->table_name . " 
                 SET username = :username, email = :email, full_name = :full_name, phone = :phone 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':id', $user_id);
        
        return $stmt->execute();
    }

    // Change password
    public function changePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $user_id);
        
        return $stmt->execute();
    }
}
?>