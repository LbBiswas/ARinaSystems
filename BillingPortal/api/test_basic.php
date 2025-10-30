<?php
// Simple test to check what's causing the 500 error
header('Content-Type: application/json');

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test 1: Session
    session_start();
    $test_results = [];
    $test_results['session_status'] = 'OK';
    
    // Test 2: Database connection
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $test_results['database_connection'] = 'OK';
    
    // Test 3: Check payments table
    $stmt = $db->query("SHOW TABLES LIKE 'payments'");
    $test_results['payments_table_exists'] = $stmt->rowCount() > 0;
    
    if ($test_results['payments_table_exists']) {
        $count_stmt = $db->query("SELECT COUNT(*) as count FROM payments");
        $count = $count_stmt->fetch();
        $test_results['payments_count'] = $count['count'];
    }
    
    // Test 4: Check session variables (simulate admin session)
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $test_results['session_vars_set'] = 'OK';
    
    // Test 5: JSON input simulation
    $test_input = '{"payment_id": 1}';
    $parsed = json_decode($test_input, true);
    $test_results['json_parsing'] = json_last_error() === JSON_ERROR_NONE ? 'OK' : json_last_error_msg();
    
    echo json_encode([
        'success' => true,
        'message' => 'All tests passed',
        'tests' => $test_results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'tests' => $test_results ?? []
    ], JSON_PRETTY_PRINT);
}
?>