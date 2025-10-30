<?php
// includes/document_manager.php - Document management functions
require_once '../config/config.php';

class DocumentManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function uploadDocument($fileData, $customerId, $uploadedBy, $title = null) {
        try {
            // Validate file
            $validation = $this->validateFile($fileData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = UPLOAD_PATH . $fileName;
            
            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
                return ['success' => false, 'message' => 'Failed to save file'];
            }
            
            // Save to database
            $stmt = $this->db->prepare("INSERT INTO documents (customer_id, title, file_name, file_path, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $customerId,
                $title ?: $fileData['name'],
                $fileData['name'],
                $filePath,
                $fileData['size'],
                $extension,
                $uploadedBy
            ]);
            
            $documentId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($uploadedBy, 'document_upload', "Uploaded document: {$fileData['name']}");
            
            return ['success' => true, 'document_id' => $documentId, 'file_name' => $fileName];
        } catch (Exception $e) {
            error_log("Document upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed'];
        }
    }
    
    public function getDocuments($customerId = null, $limit = null) {
        try {
            $query = "SELECT d.*, u.full_name as customer_name, up.full_name as uploaded_by_name 
                     FROM documents d 
                     JOIN users u ON d.customer_id = u.id 
                     JOIN users up ON d.uploaded_by = up.id";
            $params = [];
            
            if ($customerId) {
                $query .= " WHERE d.customer_id = ?";
                $params[] = $customerId;
            }
            
            $query .= " ORDER BY d.upload_date DESC";
            
            if ($limit) {
                $query .= " LIMIT ?";
                $params[] = $limit;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get documents error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDocument($documentId, $userId = null) {
        try {
            $query = "SELECT d.*, u.full_name as customer_name 
                     FROM documents d 
                     JOIN users u ON d.customer_id = u.id 
                     WHERE d.id = ?";
            $params = [$documentId];
            
            // If not admin, only allow access to own documents
            if ($userId && !isAdmin()) {
                $query .= " AND d.customer_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get document error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteDocument($documentId, $userId = null) {
        try {
            // Get document info first
            $document = $this->getDocument($documentId, $userId);
            if (!$document) {
                return ['success' => false, 'message' => 'Document not found'];
            }
            
            // Delete file from filesystem
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            
            // Delete from database
            $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$documentId]);
            
            // Log activity
            $this->logActivity($_SESSION['user_id'], 'document_delete', "Deleted document: {$document['file_name']}");
            
            return ['success' => true, 'message' => 'Document deleted successfully'];
        } catch (Exception $e) {
            error_log("Delete document error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }
    
    public function downloadDocument($documentId, $userId = null) {
        try {
            $document = $this->getDocument($documentId, $userId);
            if (!$document) {
                return false;
            }
            
            if (!file_exists($document['file_path'])) {
                return false;
            }
            
            // Log activity
            $this->logActivity($_SESSION['user_id'], 'document_download', "Downloaded document: {$document['file_name']}");
            
            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
            header('Content-Length: ' . filesize($document['file_path']));
            header('Cache-Control: must-revalidate');
            
            // Output file
            readfile($document['file_path']);
            return true;
        } catch (Exception $e) {
            error_log("Download document error: " . $e->getMessage());
            return false;
        }
    }
    
    private function validateFile($fileData) {
        // Check if file was uploaded
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error'];
        }
        
        // Check file size
        if ($fileData['size'] > MAX_FILE_SIZE) {
            return ['valid' => false, 'message' => 'File size exceeds limit'];
        }
        
        // Check file type
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_FILE_TYPES)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        return ['valid' => true];
    }
    
    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}
?>