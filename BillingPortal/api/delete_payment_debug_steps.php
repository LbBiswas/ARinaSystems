<?php
// Emergency debug version - minimal code to isolate the 500 error
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Capture any output
ob_start();

try {
    echo "Step 1: Starting debug...\n";
    
    // Test 1: Basic PHP functionality
    echo "Step 2: PHP working\n";
    
    // Test 2: Session
    session_start();
    echo "Step 3: Session started\n";
    
    // Test 3: Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Not a POST request: " . $_SERVER['REQUEST_METHOD']);
    }
    echo "Step 4: POST method confirmed\n";
    
    // Test 4: Authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("No user_id in session");
    }
    if ($_SESSION['user_type'] !== 'admin') {
        throw new Exception("User type is not admin: " . $_SESSION['user_type']);
    }
    echo "Step 5: Authentication passed\n";
    
    // Test 5: Get input
    $rawInput = file_get_contents('php://input');
    echo "Step 6: Raw input: " . $rawInput . "\n";
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON error: " . json_last_error_msg());
    }
    echo "Step 7: JSON decoded\n";
    
    if (!isset($input['payment_id'])) {
        throw new Exception("No payment_id in input");
    }
    $payment_id = intval($input['payment_id']);
    echo "Step 8: Payment ID: " . $payment_id . "\n";
    
    // Test 6: Database config
    require_once '../config/database.php';
    echo "Step 9: Database config loaded\n";
    
    // Test 7: Database connection
    $database = new Database();
    echo "Step 10: Database object created\n";
    
    $db = $database->getConnection();
    echo "Step 11: Database connection established\n";
    
    // Test 8: Simple query
    $result = $db->query("SELECT 1 as test");
    echo "Step 12: Database query successful\n";
    
    // Test 9: Check payment exists
    $stmt = $db->prepare("SELECT id FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        throw new Exception("Payment not found: " . $payment_id);
    }
    echo "Step 13: Payment found\n";
    
    // Don't actually delete, just simulate success
    echo "Step 14: All tests passed\n";
    
    // Clear any debug output
    ob_clean();
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Debug successful - payment deletion would work',
        'payment_id' => $payment_id,
        'debug' => 'All 14 steps completed successfully'
    ]);
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Log the error
    error_log("Delete payment debug error: " . $e->getMessage());
    
    // Return detailed error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Debug error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    // Handle fatal errors
    ob_clean();
    error_log("Delete payment fatal error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Ensure clean output
ob_end_flush();
?>