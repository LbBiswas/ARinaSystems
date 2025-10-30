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
    
    if (!$db) {
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            try {
                $type = $_GET['type'] ?? 'all';
                
                if ($type === 'customers') {
                    // Get only customer users - using existing columns only
                    $stmt = $db->prepare("
                        SELECT 
                            id, 
                            username, 
                            email, 
                            full_name,
                            phone,
                            user_type, 
                            status,
                            created_at,
                            updated_at
                        FROM users 
                        WHERE user_type = 'customer'
                        ORDER BY created_at DESC
                    ");
                } else {
                    // Get all users for admin management
                    $stmt = $db->prepare("
                        SELECT 
                            id, 
                            username, 
                            email, 
                            full_name,
                            phone,
                            user_type, 
                            status,
                            created_at,
                            updated_at
                        FROM users 
                        ORDER BY created_at DESC
                    ");
                }
                
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Clean up the data for frontend
                foreach ($users as &$user) {
                    // Ensure full_name is not empty
                    if (empty($user['full_name'])) {
                        $user['full_name'] = $user['username'];
                    }
                    
                    // Ensure status is set
                    $user['status'] = $user['status'] ?: 'active';
                    
                    // Format dates
                    if ($user['created_at']) {
                        $user['created_at_formatted'] = date('M d, Y', strtotime($user['created_at']));
                    }
                    
                    if ($user['updated_at']) {
                        $user['updated_at_formatted'] = date('M d, Y H:i', strtotime($user['updated_at']));
                    }
                }
                
                echo json_encode([
                    "success" => true,
                    "users" => $users,
                    "count" => count($users),
                    "type" => $type
                ]);
                
            } catch (PDOException $e) {
                error_log("Database error in users.php: " . $e->getMessage());
                echo json_encode([
                    "success" => false, 
                    "message" => "Database query error: " . $e->getMessage()
                ]);
            }
            break;
            
        case 'POST':
            // Create or update user
            $input = json_decode(file_get_contents("php://input"), true);
            
            $id = $input['id'] ?? null;
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $full_name = trim($input['full_name'] ?? '');
            $user_type = $input['user_type'] ?? '';
            $status = $input['status'] ?? 'active';
            $password = $input['password'] ?? '';
            $phone = trim($input['phone'] ?? '');
            
            // Validation
            if (empty($username) || empty($email) || empty($user_type)) {
                echo json_encode(["success" => false, "message" => "Username, email, and user type are required"]);
                exit;
            }
            
            if (!in_array($user_type, ['admin', 'customer'])) {
                echo json_encode(["success" => false, "message" => "Invalid user type"]);
                exit;
            }
            
            if (!in_array($status, ['active', 'inactive'])) {
                $status = 'active';
            }
            
            try {
                if ($id) {
                    // Update existing user
                    $sql = "UPDATE users SET 
                            username = ?, 
                            email = ?, 
                            full_name = ?, 
                            user_type = ?, 
                            status = ?, 
                            phone = ?, 
                            updated_at = CURRENT_TIMESTAMP";
                    $params = [$username, $email, $full_name, $user_type, $status, $phone];
                    
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long"]);
                            exit;
                        }
                        $sql .= ", password = ?";
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $id;
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    
                    echo json_encode(["success" => true, "message" => "User updated successfully"]);
                } else {
                    // Create new user
                    if (empty($password)) {
                        echo json_encode(["success" => false, "message" => "Password is required for new users"]);
                        exit;
                    }
                    
                    if (strlen($password) < 6) {
                        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long"]);
                        exit;
                    }
                    
                    // Check if username/email already exists
                    $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $checkStmt->execute([$username, $email]);
                    if ($checkStmt->fetch()) {
                        echo json_encode(["success" => false, "message" => "Username or email already exists"]);
                        exit;
                    }
                    
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, full_name, password, user_type, status, phone, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    
                    $stmt->execute([$username, $email, $full_name, $hashedPassword, $user_type, $status, $phone]);
                    
                    // Log activity if activity_logs table exists
                    try {
                        $newUserId = $db->lastInsertId();
                        $logStmt = $db->prepare("
                            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                            VALUES (?, 'user_created', ?, ?, CURRENT_TIMESTAMP)
                        ");
                        $logStmt->execute([
                            $_SESSION['user_id'], 
                            "Created new user: $username ($user_type)",
                            $_SERVER['REMOTE_ADDR'] ?? ''
                        ]);
                    } catch (Exception $e) {
                        // Activity logging failed, but user creation succeeded
                        error_log("Activity log error: " . $e->getMessage());
                    }
                    
                    echo json_encode(["success" => true, "message" => "User created successfully"]);
                }
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
            break;
            
        case 'DELETE':
            // Delete user
            $input = json_decode(file_get_contents("php://input"), true);
            $user_id = $input['user_id'] ?? $input['id'] ?? null;
            
            if (!$user_id) {
                echo json_encode(["success" => false, "message" => "User ID is required"]);
                exit;
            }
            
            try {
                // Check if user exists and get info
                $checkStmt = $db->prepare("SELECT username, user_type FROM users WHERE id = ?");
                $checkStmt->execute([$user_id]);
                $user = $checkStmt->fetch();
                
                if (!$user) {
                    echo json_encode(["success" => false, "message" => "User not found"]);
                    exit;
                }
                
                // Don't allow deleting the current admin
                if ($user['user_type'] === 'admin' && $user_id == $_SESSION['user_id']) {
                    echo json_encode(["success" => false, "message" => "Cannot delete your own admin account"]);
                    exit;
                }
                
                // Delete user (foreign keys will handle document cleanup)
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Log activity
                try {
                    $logStmt = $db->prepare("
                        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                        VALUES (?, 'user_deleted', ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $logStmt->execute([
                        $_SESSION['user_id'], 
                        "Deleted user: {$user['username']} ({$user['user_type']})",
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                } catch (Exception $e) {
                    // Activity logging failed, but deletion succeeded
                    error_log("Activity log error: " . $e->getMessage());
                }
                
                echo json_encode(["success" => true, "message" => "User deleted successfully"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "Method not allowed"]);
            break;
    }
    
} catch (Exception $e) {
    error_log("User management error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error occurred",
        "error_details" => $e->getMessage()
    ]);
}
?>