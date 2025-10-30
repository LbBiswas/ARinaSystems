<?php
// Debug version of delete_payment.php to identify the issue
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Capture any output
ob_start();

$debug_info = [];
$debug_info['php_version'] = phpversion();
$debug_info['session_started'] = session_status() === PHP_SESSION_ACTIVE;

try {
    session_start();
    $debug_info['session_id'] = session_id();
    $debug_info['session_data'] = $_SESSION;
    
    // Check request method
    $debug_info['request_method'] = $_SERVER['REQUEST_METHOD'];
    
    // Get raw input
    $raw_input = file_get_contents('php://input');
    $debug_info['raw_input'] = $raw_input;
    $debug_info['raw_input_length'] = strlen($raw_input);
    
    // Try to decode JSON
    $input = json_decode($raw_input, true);
    $debug_info['json_decode_result'] = $input;
    $debug_info['json_last_error'] = json_last_error_msg();
    
    // Check database connection
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Test query
        $test_query = $db->query("SELECT COUNT(*) as count FROM payments");
        $count_result = $test_query->fetch();
        
        $debug_info['database_connection'] = 'SUCCESS';
        $debug_info['payments_count'] = $count_result['count'];
        
    } catch (Exception $db_error) {
        $debug_info['database_connection'] = 'FAILED';
        $debug_info['database_error'] = $db_error->getMessage();
    }
    
    // Check if all required conditions are met
    $debug_info['user_logged_in'] = isset($_SESSION['user_id']);
    $debug_info['user_is_admin'] = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    $debug_info['is_post_request'] = $_SERVER['REQUEST_METHOD'] === 'POST';
    $debug_info['has_payment_id'] = isset($input['payment_id']);
    $debug_info['payment_id_is_numeric'] = isset($input['payment_id']) && is_numeric($input['payment_id']);
    
    // Capture any output that might have been generated
    $captured_output = ob_get_contents();
    ob_clean();
    
    if (!empty($captured_output)) {
        $debug_info['captured_output'] = $captured_output;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug information collected',
        'debug' => $debug_info
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    // Capture any output that might have been generated
    $captured_output = ob_get_contents();
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error during debug collection',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'debug' => $debug_info,
        'captured_output' => $captured_output ?? null
    ], JSON_PRETTY_PRINT);
}

ob_end_clean();
?>