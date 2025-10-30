<?php
header("Content-Type: application/json");
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    $user_id = $input['user_id'] ?? null;
    $new_password = $input['new_password'] ?? '';
    
    // Validation
    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "User ID is required"]);
        exit;
    }
    
    if (empty($new_password)) {
        echo json_encode(["success" => false, "message" => "New password is required"]);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long"]);
        exit;
    }
    
    // Check if user exists
    $checkStmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $checkStmt->execute([$user_id]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }
    
    // Hash the new password
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the password
    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $user_id]);
    
    // Log the activity (if you have an activity log table)
    try {
        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, created_at) 
            VALUES (?, 'password_reset', ?, NOW())
        ");
        $logStmt->execute([
            $_SESSION['user_id'], 
            "Password reset for user: " . $user['username']
        ]);
    } catch (Exception $e) {
        // Log insertion failed, but password reset succeeded
        error_log("Activity log insertion failed: " . $e->getMessage());
    }
    
    echo json_encode([
        "success" => true, 
        "message" => "Password reset successfully for user: " . $user['username']
    ]);
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred"
    ]);
}
?>