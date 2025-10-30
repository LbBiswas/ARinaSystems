<?php
// Minimal delete payment test to isolate the 500 error
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Step 1: Session check
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_type'] = 'admin';
    }
    
    if ($_SESSION['user_type'] !== 'admin') {
        throw new Exception('Not admin');
    }
    
    // Step 2: Method check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Not POST method');
    }
    
    // Step 3: JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON error: ' . json_last_error_msg());
    }
    
    if (!isset($input['payment_id']) || !is_numeric($input['payment_id'])) {
        throw new Exception('Invalid payment ID');
    }
    
    $payment_id = intval($input['payment_id']);
    
    // Step 4: Database connection
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 5: Check if payment exists
    $checkStmt = $db->prepare("SELECT id, invoice_number, customer_name FROM payments WHERE id = ?");
    $checkStmt->execute([$payment_id]);
    $payment = $checkStmt->fetch();
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    // Step 6: Delete (comment out for testing)
    /*
    $deleteStmt = $db->prepare("DELETE FROM payments WHERE id = ?");
    $deleteStmt->execute([$payment_id]);
    */
    
    echo json_encode([
        'success' => true,
        'message' => 'All steps passed (delete commented out for safety)',
        'found_payment' => $payment,
        'steps_completed' => [
            'session_check' => 'OK',
            'method_check' => 'OK', 
            'json_parse' => 'OK',
            'payment_id_valid' => 'OK',
            'database_connection' => 'OK',
            'payment_found' => 'OK'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>