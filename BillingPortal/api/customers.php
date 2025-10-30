<?php
// api/customers.php - Get all customers
header('Content-Type: application/json');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch all customers with their details
    $query = "SELECT 
                id,
                username,
                email,
                full_name,
                user_type,
                status,
                created_at
              FROM users
              WHERE user_type = 'customer'
              ORDER BY full_name ASC";
    
    $stmt = $db->query($query);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'total' => count($customers)
    ]);
    
} catch (Exception $e) {
    error_log("Customers API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading customers: ' . $e->getMessage()
    ]);
}
?>
