<?php
// api/login_simple.php - Simple login endpoint for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        exit;
    }

    // Simple demo authentication (for testing)
    $demo_accounts = [
        'admin' => ['password' => 'admin123', 'type' => 'admin'],
        'demo' => ['password' => 'demo123', 'type' => 'customer']
    ];

    if (isset($demo_accounts[$username]) && $demo_accounts[$username]['password'] === $password) {
        // Start session
        session_start();
        $_SESSION['user_id'] = $username;
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = $demo_accounts[$username]['type'];
        
        echo json_encode([
            'success' => true,
            'user_type' => $demo_accounts[$username]['type'],
            'redirect' => $demo_accounts[$username]['type'] === 'admin' ? 'admin.php' : 'customer.php',
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid credentials. Use admin/admin123 or demo/demo123'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>