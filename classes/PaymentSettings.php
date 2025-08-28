<?php
class PaymentSettings {
    private $conn;
    private $table_name = "payment_settings";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTripaySettings() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE gateway_name = 'tripay'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateTripaySettings($api_key, $private_key, $merchant_code, $is_sandbox, $is_active) {
        $query = "UPDATE " . $this->table_name . " 
                 SET api_key = :api_key, private_key = :private_key, 
                     merchant_code = :merchant_code, is_sandbox = :is_sandbox, 
                     is_active = :is_active, updated_at = NOW() 
                 WHERE gateway_name = 'tripay'";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":api_key", $api_key);
        $stmt->bindParam(":private_key", $private_key);
        $stmt->bindParam(":merchant_code", $merchant_code);
        $stmt->bindParam(":is_sandbox", $is_sandbox);
        $stmt->bindParam(":is_active", $is_active);
        
        return $stmt->execute();
    }

    public function isActive($gateway_name) {
        $query = "SELECT is_active FROM " . $this->table_name . " WHERE gateway_name = :gateway_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":gateway_name", $gateway_name);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['is_active'] : false;
    }
}
?>