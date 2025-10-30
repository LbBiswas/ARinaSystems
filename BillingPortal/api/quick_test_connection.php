<?php
// Quick test for delete_payment.php connection
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';

try {
    // Test the exact same connection method as delete_payment.php
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Test query to verify payments table exists
    $stmt = $db->query("SELECT COUNT(*) as count FROM payments LIMIT 1");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Connection successful',
        'payments_count' => $result['count'],
        'database_working' => 'YES'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>