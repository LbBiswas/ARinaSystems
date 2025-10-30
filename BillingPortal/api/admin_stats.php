<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get active users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total customers
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'customer'");
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total documents
    $stmt = $db->query("SELECT COUNT(*) as total FROM documents");
    $totalDocuments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get recent users (last 5)
    $stmt = $db->query("SELECT id as user_id, username, full_name, email, user_type, created_at 
                        FROM users ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity (last 10)
    $stmt = $db->query("SELECT al.id, al.user_id, al.action, al.description, al.created_at, u.username 
                        FROM activity_logs al 
                        LEFT JOIN users u ON al.user_id = u.id 
                        ORDER BY al.created_at DESC LIMIT 10");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_customers' => $totalCustomers,
            'total_documents' => $totalDocuments
        ],
        'recent_users' => $recentUsers,
        'recent_activity' => $recentActivity
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>