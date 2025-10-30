<?php
// Debug version of delete_payment.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    // Log start
    error_log("=== DELETE PAYMENT DEBUG START ===");
    
    // Check session
    session_start();
    error_log("Session started successfully");
    
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        error_log("Authentication failed - user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", user_type: " . ($_SESSION['user_type'] ?? 'not set'));
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    error_log("Authentication passed - user_id: " . $_SESSION['user_id']);
    
    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Wrong method: " . $_SERVER['REQUEST_METHOD']);
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    error_log("Method check passed");
    
    // Get input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    error_log("JSON decoded successfully: " . print_r($input, true));
    
    // Validate payment ID
    if (!isset($input['payment_id']) || !is_numeric($input['payment_id'])) {
        error_log("Invalid payment ID: " . ($input['payment_id'] ?? 'not set'));
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }
    
    $payment_id = intval($input['payment_id']);
    error_log("Payment ID validated: " . $payment_id);
    
    // Include database config
    error_log("About to include database config");
    require_once '../config/database.php';
    error_log("Database config included");
    
    // Connect to database
    error_log("Creating database connection");
    $database = new Database();
    $db = $database->getConnection();
    error_log("Database connection created");
    
    // Check if payments table exists
    error_log("Checking if payments table exists");
    $tableCheck = $db->query("SHOW TABLES LIKE 'payments'");
    if ($tableCheck->rowCount() === 0) {
        error_log("Payments table does not exist");
        echo json_encode(['success' => false, 'message' => 'Payments table does not exist']);
        exit;
    }
    error_log("Payments table exists");
    
    // Check if payment exists
    error_log("Checking if payment exists with ID: " . $payment_id);
    $checkStmt = $db->prepare("SELECT id, invoice_number, customer_name FROM payments WHERE id = ?");
    $checkStmt->execute([$payment_id]);
    $payment = $checkStmt->fetch();
    
    if (!$payment) {
        error_log("Payment not found with ID: " . $payment_id);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }
    error_log("Payment found: " . print_r($payment, true));
    
    // Simple deletion
    error_log("Attempting to delete payment with ID: " . $payment_id);
    $deleteStmt = $db->prepare("DELETE FROM payments WHERE id = ?");
    $deleteStmt->execute([$payment_id]);
    
    if ($deleteStmt->rowCount() > 0) {
        error_log("Payment deleted successfully");
        echo json_encode([
            'success' => true,
            'message' => 'Payment deleted successfully',
            'deleted_payment' => $payment
        ]);
    } else {
        error_log("No rows affected in deletion");
        echo json_encode(['success' => false, 'message' => 'No payment was deleted']);
    }
    
    error_log("=== DELETE PAYMENT DEBUG END ===");
    
} catch (Exception $e) {
    error_log("EXCEPTION CAUGHT: " . $e->getMessage());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    error_log("FATAL ERROR CAUGHT: " . $e->getMessage());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>