<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Require files
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    
    // Log activity if user is logged in
    if (isset($_SESSION['user_id'])) {
        $auth = new Auth();
        $auth->logout();
    }
    
    // Destroy session
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    
    // Try to destroy session anyway
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Logged out']);
}
?>