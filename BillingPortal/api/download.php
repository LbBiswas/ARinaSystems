<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Not authenticated');
}

require_once '../config/database.php';

$documentId = $_GET['id'] ?? null;

if (!$documentId) {
    http_response_code(400);
    exit('Document ID required');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    // Get document with permission check
    if ($userType === 'admin') {
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
    } else {
        // Customer can only download documents where they are the customer or uploader
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND (customer_id = ? OR uploaded_by = ?)");
        $stmt->execute([$documentId, $userId, $userId]);
    }
    
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        exit('Document not found or access denied');
    }
    
    $filePath = "../uploads/documents/" . $document['file_name'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File not found on server');
    }
    
    // Set headers for download
    header('Content-Type: ' . ($document['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Log download activity
    try {
        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
            VALUES (?, 'document_downloaded', ?, ?, CURRENT_TIMESTAMP)
        ");
        $logStmt->execute([
            $userId, 
            "Downloaded document: " . $document['title'],
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
    
    // Output file
    readfile($filePath);
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    exit('Server error occurred');
}
?>