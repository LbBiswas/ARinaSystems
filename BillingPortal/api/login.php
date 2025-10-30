<?php
// api/login.php - Login endpoint
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        exit;
    }

    // Check user credentials
    $stmt = $db->prepare("SELECT id, username, email, password, full_name, user_type, status FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Account is inactive']);
            exit;
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $user['email'];
        
        // Log activity
        try {
            $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, 'login', 'User logged in', ?, CURRENT_TIMESTAMP)");
            $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'user_type' => $user['user_type'],
            'redirect' => $user['user_type'] === 'admin' ? 'admin.html' : 'customer.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }

} catch (Exception $e) {
    error_log("Login API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>