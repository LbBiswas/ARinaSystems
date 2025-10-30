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
    
    // Initialize stats array
    $stats = [];
    
    // Check if documents table exists
    $stmt = $db->query("SHOW TABLES LIKE 'documents'");
    if ($stmt->rowCount() === 0) {
        // Table doesn't exist - return empty stats
        echo json_encode([
            "success" => true,
            "stats" => [
                "total_documents" => 0,
                "total_size" => 0,
                "documents_today" => 0,
                "unique_users" => 0
            ],
            "message" => "Documents table not found"
        ]);
        exit;
    }
    
    // Get total documents
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM documents");
        $stats['total_documents'] = (int)$stmt->fetch()['total'];
    } catch (Exception $e) {
        $stats['total_documents'] = 0;
        error_log("Stats API - total_documents error: " . $e->getMessage());
    }
    
    // Get total storage used (in bytes, convert to MB in frontend)
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(file_size), 0) as total_size FROM documents");
        $stats['total_size'] = (int)$stmt->fetch()['total_size'];
    } catch (Exception $e) {
        $stats['total_size'] = 0;
        error_log("Stats API - total_size error: " . $e->getMessage());
    }
    
    // Get documents uploaded today
    try {
        $stmt = $db->query("SELECT COUNT(*) as today FROM documents WHERE DATE(upload_date) = CURDATE()");
        $stats['documents_today'] = (int)$stmt->fetch()['today'];
    } catch (Exception $e) {
        $stats['documents_today'] = 0;
        error_log("Stats API - documents_today error: " . $e->getMessage());
    }
    
    // Get unique users (count of users who have uploaded documents)
    try {
        $stmt = $db->query("SELECT COUNT(DISTINCT uploaded_by) as unique_users FROM documents WHERE uploaded_by IS NOT NULL");
        $stats['unique_users'] = (int)$stmt->fetch()['unique_users'];
    } catch (Exception $e) {
        $stats['unique_users'] = 0;
        error_log("Stats API - unique_users error: " . $e->getMessage());
    }
    
    echo json_encode([
        "success" => true,
        "stats" => $stats,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Document stats error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred",
        "debug_info" => $e->getMessage(),
        "error_line" => $e->getLine(),
        "error_file" => $e->getFile()
    ]);
}
?>