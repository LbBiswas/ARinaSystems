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
    
    // Check and create bill columns if they don't exist (to prevent errors)
    try {
        $checkBillDateFrom = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date_from'");
        if ($checkBillDateFrom->rowCount() === 0) {
            $db->exec("ALTER TABLE documents ADD COLUMN bill_date_from DATE NULL");
        }
        
        $checkBillDateTo = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date_to'");
        if ($checkBillDateTo->rowCount() === 0) {
            $db->exec("ALTER TABLE documents ADD COLUMN bill_date_to DATE NULL");
        }
        
        $checkBillAmount = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_amount'");
        if ($checkBillAmount->rowCount() === 0) {
            $db->exec("ALTER TABLE documents ADD COLUMN bill_amount DECIMAL(10,2) NULL DEFAULT 0.00");
        }
    } catch (Exception $e) {
        // Column creation error - not critical for stats
        error_log("Stats API - Column check error: " . $e->getMessage());
    }
    
    $stats = [];
    
    // Total documents
    $stmt = $db->query("SELECT COUNT(*) as total FROM documents");
    $stats['total_documents'] = $stmt->fetch()['total'];
    
    // Total storage used
    $stmt = $db->query("SELECT SUM(file_size) as total_size FROM documents");
    $result = $stmt->fetch();
    $stats['total_size'] = $result['total_size'] ?: 0;
    
    // Documents uploaded today
    $stmt = $db->query("SELECT COUNT(*) as today FROM documents WHERE DATE(upload_date) = CURDATE()");
    $stats['documents_today'] = $stmt->fetch()['today'];
    
    // Documents uploaded this week
    $stmt = $db->query("SELECT COUNT(*) as this_week FROM documents WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['documents_this_week'] = $stmt->fetch()['this_week'];
    
    // Documents uploaded this month
    $stmt = $db->query("SELECT COUNT(*) as this_month FROM documents WHERE MONTH(upload_date) = MONTH(NOW()) AND YEAR(upload_date) = YEAR(NOW())");
    $stats['documents_this_month'] = $stmt->fetch()['this_month'];
    
    // Unique users who have uploaded documents
    $stmt = $db->query("SELECT COUNT(DISTINCT uploaded_by) as unique_users FROM documents");
    $stats['unique_users'] = $stmt->fetch()['unique_users'];
    
    // Most common file types
    $stmt = $db->query("
        SELECT file_type, COUNT(*) as count 
        FROM documents 
        GROUP BY file_type 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stats['file_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Most common categories
    $stmt = $db->query("
        SELECT category, COUNT(*) as count 
        FROM documents 
        WHERE category IS NOT NULL AND category != ''
        GROUP BY category 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stats['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Average file size
    $stmt = $db->query("SELECT AVG(file_size) as avg_size FROM documents");
    $result = $stmt->fetch();
    $stats['average_file_size'] = $result['avg_size'] ?: 0;
    
    // Recent uploads (last 10)
    $stmt = $db->query("
        SELECT 
            d.title,
            d.file_name,
            d.file_size,
            d.upload_date,
            u.username,
            u.full_name
        FROM documents d
        LEFT JOIN users u ON d.uploaded_by = u.id
        ORDER BY d.upload_date DESC
        LIMIT 10
    ");
    $stats['recent_uploads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Storage by user
    $stmt = $db->query("
        SELECT 
            u.username,
            u.full_name,
            COUNT(d.id) as document_count,
            SUM(d.file_size) as total_size
        FROM users u
        LEFT JOIN documents d ON u.id = d.uploaded_by
        GROUP BY u.id
        ORDER BY total_size DESC
        LIMIT 10
    ");
    $stats['storage_by_user'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "stats" => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Document stats error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred"
    ]);
}
?>