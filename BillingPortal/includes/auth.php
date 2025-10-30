<?php
// includes/auth.php - Authentication functions

class Auth {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Auth constructor error: " . $e->getMessage());
            throw new Exception("Failed to initialize authentication");
        }
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password, full_name, user_type, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'Account is inactive'];
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];
                
                // Log activity
                $this->logActivity($user['id'], 'login', 'User logged in');
                
                return ['success' => true, 'user_type' => $user['user_type']];
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        session_destroy();
        return true;
    }
    
    public function register($userData) {
        try {
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert new user using your existing schema
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, full_name, phone, user_type, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashedPassword,
                $userData['full_name'] ?? '',
                $userData['phone'] ?? '',
                $userData['user_type'] ?? 'customer'
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($userId, 'register', 'New user registered');
            
            return ['success' => true, 'message' => 'User registered successfully', 'user_id' => $userId];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
            }
            
            // Hash and update new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Log activity
            $this->logActivity($userId, 'password_change', 'User changed password');
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer';
}
?>