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
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
case 'GET':
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    // Check if we're getting a specific document
    if (isset($_GET['id'])) {
        $documentId = $_GET['id'];
        
        if ($userType === 'admin') {
            $stmt = $db->prepare("
                SELECT 
                    d.*,
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
            $stmt = $db->prepare("
                SELECT 
                    d.*,
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
            $document['file_icon'] = getFileIcon($document['file_type']);
            $document['upload_date_formatted'] = date('M d, Y H:i', strtotime($document['upload_date']));
            $document['status'] = $document['status'] ?: 'pending';
            $document['uploader_display'] = $document['uploader_name'] ?: $document['uploader_username'] ?: 'Unknown';
            $document['customer_display'] = $document['customer_name'] ?: $document['customer_username'] ?: 'Unassigned';
            
            echo json_encode([
                "success" => true,
                "document" => $document
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Document not found or access denied"
            ]);
        }
        exit;
    }
    
    // Get all documents for current user
    if ($userType === 'admin') {
        $stmt = $db->query("
            SELECT 
                d.id,
                d.title,
                d.file_name,
                d.file_path,
                d.file_size,
                d.file_type,
                d.upload_date,
                d.status,
                d.category,
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
    } else {
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
                d.category,
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
        $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        $doc['file_icon'] = getFileIcon($doc['file_type']);
        $doc['upload_date_formatted'] = date('M d, Y H:i', strtotime($doc['upload_date']));
        $doc['status'] = $doc['status'] ?: 'pending';
        
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
    break;
            
        case 'PUT':
            // Update document (admin only)
            if ($_SESSION['user_type'] !== 'admin') {
                echo json_encode(["success" => false, "message" => "Admin access required"]);
                exit;
            }
            
            $input = json_decode(file_get_contents("php://input"), true);
            
            $documentId = $input['document_id'] ?? null;
            $title = $input['title'] ?? '';
            $status = $input['status'] ?? 'pending';
            $customerId = $input['customer_id'] ?? null;
            
            if (!$documentId) {
                echo json_encode(["success" => false, "message" => "Document ID required"]);
                exit;
            }
            
            if (empty($title)) {
                echo json_encode(["success" => false, "message" => "Title is required"]);
                exit;
            }
            
            try {
                $stmt = $db->prepare("
                    UPDATE documents 
                    SET title = ?, status = ?, customer_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $status, $customerId, $documentId]);
                
                // Log activity
                try {
                    $logStmt = $db->prepare("
                        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                        VALUES (?, 'document_updated', ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $logStmt->execute([
                        $_SESSION['user_id'], 
                        "Updated document: $title (ID: $documentId)",
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                } catch (Exception $e) {
                    error_log("Activity log error: " . $e->getMessage());
                }
                
                echo json_encode(["success" => true, "message" => "Document updated successfully"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            }
            break;
            
        case 'DELETE':
            // Delete document with proper authorization
            $input = json_decode(file_get_contents("php://input"), true);
            $documentId = $input['document_id'] ?? $input['id'] ?? null;
            
            if (!$documentId) {
                echo json_encode(["success" => false, "message" => "Document ID required"]);
                exit;
            }
            
            try {
                // Get document info and check permissions
                $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
                $stmt->execute([$documentId]);
                $document = $stmt->fetch();
                
                if (!$document) {
                    echo json_encode(["success" => false, "message" => "Document not found"]);
                    exit;
                }
                
                // Check if user can delete (admin or document owner/customer)
                if ($_SESSION['user_type'] !== 'admin' && 
                    $document['uploaded_by'] != $_SESSION['user_id'] && 
                    $document['customer_id'] != $_SESSION['user_id']) {
                    echo json_encode(["success" => false, "message" => "Permission denied"]);
                    exit;
                }
                
                // Delete file from filesystem
                $filePath = "../uploads/documents/" . $document['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete from database
                $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
                $stmt->execute([$documentId]);
                
                // Log activity
                try {
                    $logStmt = $db->prepare("
                        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                        VALUES (?, 'document_deleted', ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $logStmt->execute([
                        $_SESSION['user_id'], 
                        "Deleted document: " . $document['title'],
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                } catch (Exception $e) {
                    // Activity logging failed, but deletion succeeded
                    error_log("Activity log error: " . $e->getMessage());
                }
                
                echo json_encode(["success" => true, "message" => "Document deleted successfully"]);
            } catch (PDOException $e) {
                echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
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
        "error_details" => $e->getMessage()
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