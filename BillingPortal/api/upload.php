<?php
header("Content-Type: application/json");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

require_once '../config/database.php';
require_once '../includes/email_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "No file uploaded or upload error"]);
        exit;
    }
    
    $file = $_FILES['file'];
    $uploadDir = '../uploads/documents/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file
    $maxSize = 10 * 1024 * 1024; // 10MB
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    
    if ($file['size'] > $maxSize) {
        echo json_encode(["success" => false, "message" => "File too large. Maximum size is 10MB."]);
        exit;
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(["success" => false, "message" => "File type not allowed."]);
        exit;
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(["success" => false, "message" => "Failed to save file"]);
        exit;
    }
    
    // Get additional data
    $title = $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME);
    $customer_id = $_POST['customer_id'] ?? $_SESSION['user_id']; // Default to uploader
    
    // For customers uploading their own documents
    if ($_SESSION['user_type'] === 'customer') {
        $customer_id = $_SESSION['user_id'];
    }
    
    // Save to database using your existing schema
    $stmt = $db->prepare("
        INSERT INTO documents (
            customer_id,
            title, 
            file_name, 
            file_path, 
            file_size, 
            file_type,
            uploaded_by,
            upload_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        $customer_id,
        $title,
        $fileName,
        $filePath,
        $file['size'],
        $fileType,
        $_SESSION['user_id']
    ]);
    
    // Log activity
    try {
        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
            VALUES (?, 'document_uploaded', ?, ?, CURRENT_TIMESTAMP)
        ");
        $logStmt->execute([
            $_SESSION['user_id'], 
            "Uploaded document: $title",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        // Activity logging failed, but upload succeeded
        error_log("Activity log error: " . $e->getMessage());
    }
    
    // Send email notification to admin if customer uploaded the document
    if ($_SESSION['user_type'] === 'customer') {
        try {
            // Get customer information
            $customerStmt = $db->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
            $customerStmt->execute([$_SESSION['user_id']]);
            $customer = $customerStmt->fetch();
            
            // Get admin email(s)
            $adminStmt = $db->prepare("SELECT email FROM users WHERE user_type = 'admin' LIMIT 1");
            $adminStmt->execute();
            $admin = $adminStmt->fetch();
            
            if ($customer && $admin) {
                $emailHelper = new EmailHelper();
                
                // Determine document category based on file type/name
                $category = 'document';
                $fileName = strtolower($file['name']);
                if (strpos($fileName, 'invoice') !== false || strpos($fileName, 'bill') !== false) {
                    $category = 'invoice';
                } elseif (strpos($fileName, 'receipt') !== false || strpos($fileName, 'payment') !== false) {
                    $category = 'receipt';
                } elseif (strpos($fileName, 'contract') !== false || strpos($fileName, 'agreement') !== false) {
                    $category = 'contract';
                } elseif (in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
                    $category = 'image';
                } elseif ($fileType === 'application/pdf') {
                    $category = 'pdf document';
                }
                
                $documentData = [
                    'original_name' => $file['name'],
                    'file_size' => $file['size']
                ];
                
                $customerName = $customer['full_name'] ?: $customer['username'];
                
                $emailSent = $emailHelper->sendCustomerUploadNotificationToAdmin(
                    $admin['email'],
                    $customerName,
                    $customer['email'],
                    $documentData,
                    $category
                );
                
                if ($emailSent) {
                    error_log("Admin notification email sent for customer upload by: " . $customerName . " (Category: $category)");
                } else {
                    error_log("Failed to send admin notification email for customer upload");
                }
            }
        } catch (Exception $e) {
            // Email failed, but upload succeeded
            error_log("Admin notification email error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        "success" => true, 
        "message" => "File uploaded successfully",
        "document_id" => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Upload failed: " . $e->getMessage()
    ]);
}
?>