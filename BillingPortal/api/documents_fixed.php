<?php
header("Content-Type: application/json");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if category column exists
    $categoryExists = false;
    try {
        $checkCategory = $db->query("SHOW COLUMNS FROM documents LIKE 'category'");
        $categoryExists = $checkCategory->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Category column check error: " . $e->getMessage());
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $userId = $_SESSION['user_id'];
            $userType = $_SESSION['user_type'];
            
            // Check if we're getting a specific document
            if (isset($_GET['id'])) {
                $documentId = $_GET['id'];
                
                if ($userType === 'admin') {
                    $categorySelect = $categoryExists ? "d.category," : "'General' as category,";
                    $stmt = $db->prepare("
                        SELECT 
                            d.*,
                            {$categorySelect}
                            uploader.username as uploader_username,
                            uploader.full_name as uploader_name,
                            uploader.email as uploader_email,
                            customer.username as customer_username,
                            customer.full_name as customer_name,
                            customer.email as customer_email
                        FROM documents d
                        LEFT JOIN users uploader ON d.uploaded_by = uploader.id
                        LEFT JOIN users customer ON d.customer_id = customer.id
                        WHERE d.id = ?
                    ");
                    $stmt->execute([$documentId]);
                } else {
                    $categorySelect = $categoryExists ? "d.category," : "'General' as category,";
                    $stmt = $db->prepare("
                        SELECT 
                            d.*,
                            {$categorySelect}
                            uploader.username as uploader_username,
                            uploader.full_name as uploader_name,
                            customer.username as customer_username,
                            customer.full_name as customer_name
                        FROM documents d
                        LEFT JOIN users uploader ON d.uploaded_by = uploader.id
                        LEFT JOIN users customer ON d.customer_id = customer.id
                        WHERE d.id = ? AND (d.customer_id = ? OR d.uploaded_by = ?)
                    ");
                    $stmt->execute([$documentId, $userId, $userId]);
                }
                
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($document) {
                    $document['file_size_formatted'] = formatFileSize($document['file_size']);
                    $document['upload_date_formatted'] = date('M d, Y H:i', strtotime($document['upload_date']));
                    $document['uploader_display'] = $document['uploader_name'] ?: $document['uploader_username'] ?: 'Unknown';
                    
                    echo json_encode([
                        "success" => true,
                        "document" => $document
                    ]);
                } else {
                    echo json_encode(["success" => false, "message" => "Document not found"]);
                }
            } else {
                // Get all documents for user
                if ($userType === 'admin') {
                    $categorySelect = $categoryExists ? "d.category," : "'General' as category,";
                    $stmt = $db->prepare("
                        SELECT 
                            d.id,
                            d.title,
                            d.file_name,
                            d.file_path,
                            d.file_size,
                            d.file_type,
                            d.upload_date,
                            d.status,
                            {$categorySelect}
                            d.customer_id,
                            d.uploaded_by,
                            uploader.username as uploader_username,
                            uploader.full_name as uploader_name,
                            uploader.email as uploader_email,
                            customer.username as customer_username,
                            customer.full_name as customer_name,
                            customer.email as customer_email,
                            customer.phone as customer_phone
                        FROM documents d
                        LEFT JOIN users uploader ON d.uploaded_by = uploader.id
                        LEFT JOIN users customer ON d.customer_id = customer.id
                        ORDER BY d.upload_date DESC
                    ");
                    $stmt->execute();
                } else {
                    $categorySelect = $categoryExists ? "d.category," : "'General' as category,";
                    $stmt = $db->prepare("
                        SELECT 
                            d.id,
                            d.title,
                            d.file_name,
                            d.file_path,
                            d.file_size,
                            d.file_type,
                            d.upload_date,
                            d.status,
                            {$categorySelect}
                            d.customer_id,
                            d.uploaded_by,
                            uploader.username as uploader_username,
                            uploader.full_name as uploader_name,
                            customer.username as customer_username,
                            customer.full_name as customer_name
                        FROM documents d
                        LEFT JOIN users uploader ON d.uploaded_by = uploader.id
                        LEFT JOIN users customer ON d.customer_id = customer.id
                        WHERE d.customer_id = ? OR d.uploaded_by = ?
                        ORDER BY d.upload_date DESC
                    ");
                    $stmt->execute([$userId, $userId]);
                }
                
                $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format document data with complete information
                foreach ($documents as &$doc) {
                    $doc['file_size_formatted'] = formatFileSize($doc['file_size'] ?? 0);
                    $doc['file_icon'] = getFileIcon($doc['file_type'] ?? '');
                    $doc['upload_date_formatted'] = date('M d, Y H:i', strtotime($doc['upload_date']));
                    $doc['status'] = $doc['status'] ?: 'pending';
                    $doc['category'] = $doc['category'] ?: 'General';
                    
                    // Complete uploader information
                    $doc['uploader_display'] = !empty($doc['uploader_name']) ? 
                        $doc['uploader_name'] : 
                        (!empty($doc['uploader_username']) ? $doc['uploader_username'] : 'Unknown');
                    
                    if (!empty($doc['uploader_email'])) {
                        $doc['uploader_display'] .= ' (' . $doc['uploader_email'] . ')';
                    }
                    
                    // Complete customer information
                    $doc['customer_display'] = !empty($doc['customer_name']) ? 
                        $doc['customer_name'] : 
                        (!empty($doc['customer_username']) ? $doc['customer_username'] : 'Unassigned');
                    
                    if (!empty($doc['customer_email'])) {
                        $doc['customer_display'] .= ' (' . $doc['customer_email'] . ')';
                    }
                    
                    if (!empty($doc['customer_phone'])) {
                        $doc['customer_phone_display'] = $doc['customer_phone'];
                    }
                }
                
                echo json_encode([
                    "success" => true,
                    "documents" => $documents,
                    "count" => count($documents)
                ]);
            }
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "Method not allowed"]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Documents API error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error occurred",
        "error_details" => $e->getMessage(),
        "debug_info" => [
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "user_id" => $_SESSION['user_id'] ?? 'not set',
            "user_type" => $_SESSION['user_type'] ?? 'not set'
        ]
    ]);
}

// Helper functions
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon($fileType) {
    $type = strtolower($fileType);
    if (strpos($type, 'pdf') !== false) return 'fa-file-pdf';
    if (strpos($type, 'word') !== false || strpos($type, 'doc') !== false) return 'fa-file-word';
    if (strpos($type, 'image') !== false || strpos($type, 'jpg') !== false || strpos($type, 'png') !== false) return 'fa-file-image';
    if (strpos($type, 'excel') !== false || strpos($type, 'sheet') !== false) return 'fa-file-excel';
    return 'fa-file';
}
?>