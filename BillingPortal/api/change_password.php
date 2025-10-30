<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Show errors for debugging
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    // Include files
    $configPath = dirname(__DIR__) . '/config/database.php';
    $authPath = dirname(__DIR__) . '/includes/auth.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found at: $configPath");
    }
    
    if (!file_exists($authPath)) {
        throw new Exception("Auth file not found at: $authPath");
    }
    
    require_once $configPath;
    require_once $authPath;

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated - Please login again']);
        exit;
    }

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get and decode input
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);

    // Validate input
    if (!$input || !isset($input['current_password']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Create Auth instance and change password
    $auth = new Auth();
    $result = $auth->changePassword(
        $_SESSION['user_id'],
        $input['current_password'],
        $input['new_password']
    );

    http_response_code(200);
    ob_clean(); // Clean any unexpected output
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Change password error: " . $e->getMessage());
    http_response_code(500);
    ob_clean(); // Clean any unexpected output
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
?>