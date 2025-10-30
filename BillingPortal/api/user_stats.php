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
    
    // Get user statistics
    $stats = [];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['total'] = $stmt->fetch()['total'];
    
    // Active users (assuming active status or no status field means active)
    $stmt = $db->query("SELECT COUNT(*) as active FROM users WHERE status = 'active' OR status IS NULL");
    $stats['active'] = $stmt->fetch()['active'];
    
    // Admin users
    $stmt = $db->query("SELECT COUNT(*) as admins FROM users WHERE user_type = 'admin'");
    $stats['admins'] = $stmt->fetch()['admins'];
    
    // Customer users
    $stmt = $db->query("SELECT COUNT(*) as customers FROM users WHERE user_type = 'customer'");
    $stats['customers'] = $stmt->fetch()['customers'];
    
    // Inactive users
    $stmt = $db->query("SELECT COUNT(*) as inactive FROM users WHERE status = 'inactive'");
    $stats['inactive'] = $stmt->fetch()['inactive'];
    
    // Pending users
    $stmt = $db->query("SELECT COUNT(*) as pending FROM users WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetch()['pending'];
    
    // Users registered today
    $stmt = $db->query("SELECT COUNT(*) as today FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['today'];
    
    // Users registered this week
    $stmt = $db->query("SELECT COUNT(*) as this_week FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['this_week'] = $stmt->fetch()['this_week'];
    
    // Users registered this month
    $stmt = $db->query("SELECT COUNT(*) as this_month FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stats['this_month'] = $stmt->fetch()['this_month'];
    
    echo json_encode([
        "success" => true,
        "stats" => $stats
    ]);
    
} catch (Exception $e) {
    error_log("User stats error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred"
    ]);
}
?>