<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once '../config/database.php';

    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get user profile
            try {
                $stmt = $db->prepare("SELECT id, username, email, full_name, phone, user_type, created_at FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    echo json_encode(['success' => true, 'user' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } catch (Exception $e) {
                error_log("Profile GET error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to load profile']);
            }
            break;
            
        case 'POST':
            // Update user profile
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validate input
                if (!isset($input['full_name']) || !isset($input['email'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                    exit;
                }
                
                // Validate email
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                    exit;
                }
                
                // Check if email is already taken by another user
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$input['email'], $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
                    exit;
                }
                
                // Update user profile
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $success = $stmt->execute([
                    trim($input['full_name']),
                    trim($input['email']),
                    isset($input['phone']) ? trim($input['phone']) : null,
                    $_SESSION['user_id']
                ]);
                
                if ($success) {
                    // Update session
                    $_SESSION['full_name'] = trim($input['full_name']);
                    $_SESSION['email'] = trim($input['email']);
                    
                    // Get updated user data
                    $stmt = $db->prepare("SELECT id, username, email, full_name, phone, user_type FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Profile updated successfully',
                        'user' => $user
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                }
            } catch (Exception $e) {
                error_log("Profile POST error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Profile API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>