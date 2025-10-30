<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access');
}

require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Document ID required');
}

$documentId = $_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get document info
    $stmt = $db->prepare("
        SELECT 
            d.*,
            u.username as owner_name
        FROM documents d
        LEFT JOIN users u ON d.uploaded_by = u.id
        WHERE d.id = ?
    ");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();
    
    if (!$document) {
        header('HTTP/1.1 404 Not Found');
        exit('Document not found');
    }
    
    // Check permissions - admin can download any document, users can only download their own
    if ($_SESSION['user_type'] !== 'admin' && $document['uploaded_by'] != $_SESSION['user_id']) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied');
    }
    
    $filePath = $document['file_path'];
    
    // Check if file exists
    if (!file_exists($filePath)) {
        header('HTTP/1.1 404 Not Found');
        exit('File not found on server');
    }
    
    // Log the download activity
    try {
        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
            VALUES (?, 'document_downloaded', ?, ?, CURRENT_TIMESTAMP)
        ");
        $logStmt->execute([
            $_SESSION['user_id'], 
            "Downloaded document: " . $document['title'],
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log insertion failed, but download can proceed
        error_log("Activity log insertion failed: " . $e->getMessage());
    }
    
    // Set headers for file download
    header('Content-Type: ' . $document['file_type']);
    header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
    header('Content-Length: ' . $document['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // Output file
    readfile($filePath);
    
} catch (Exception $e) {
    error_log("Document download error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Download failed');
}
?>