<?php
// Enhanced delete payment API with cascade deletion
header('Content-Type: application/json');

// Simple error handling
try {
    session_start();
    
    // Basic checks
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST required']);
        exit;
    }
    
    if ($_SESSION['user_type'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin required']);
        exit;
    }
    
    // Get payment ID
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['payment_id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    // Connect to database
    require_once '../config/database.php';
    $db = (new Database())->getConnection();
    
    // Begin transaction for safe cascade deletion
    $db->beginTransaction();
    
    try {
        // First, get payment details for finding related documents
        $paymentStmt = $db->prepare("SELECT id, invoice_number, customer_name FROM payments WHERE id = ?");
        $paymentStmt->execute([$id]);
        $payment = $paymentStmt->fetch();
        
        if (!$payment) {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }
        
        // Find and delete related documents
        $deletedDocsCount = 0;
        
        // Check if documents table exists
        $docTableCheck = $db->query("SHOW TABLES LIKE 'documents'");
        if ($docTableCheck->rowCount() > 0) {
            
            // Check if invoice_number column exists in documents table
            $invoiceColumnCheck = $db->query("SHOW COLUMNS FROM documents LIKE 'invoice_number'");
            
            if ($invoiceColumnCheck->rowCount() > 0) {
                // Find documents related to this payment by invoice_number
                $relatedDocsStmt = $db->prepare("
                    SELECT id, file_name, title 
                    FROM documents 
                    WHERE invoice_number = ?
                ");
                $relatedDocsStmt->execute([$payment['invoice_number']]);
                $relatedDocs = $relatedDocsStmt->fetchAll();
                
                // Delete each related document
                foreach ($relatedDocs as $doc) {
                    // Delete physical file first
                    $filePath = "../uploads/documents/" . $doc['file_name'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    
                    // Delete document record from database
                    $deleteDocStmt = $db->prepare("DELETE FROM documents WHERE id = ?");
                    $deleteDocStmt->execute([$doc['id']]);
                    $deletedDocsCount++;
                    
                    // Log document deletion
                    error_log("Auto-deleted document '{$doc['title']}' due to payment deletion (Payment ID: {$id}, Invoice: {$payment['invoice_number']})");
                }
            } else {
                // No invoice_number column, so no documents can be linked to payments
                error_log("Documents table exists but has no invoice_number column - cannot perform cascade deletion");
            }
        }
        
        // Now delete the payment
        $stmt = $db->prepare("DELETE FROM payments WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            // Commit the transaction
            $db->commit();
            
            // Success message with cascade info
            $message = 'Payment deleted successfully';
            if ($deletedDocsCount > 0) {
                $message .= " (Also deleted {$deletedDocsCount} related document(s))";
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'deleted_payment' => [
                    'id' => $payment['id'],
                    'invoice_number' => $payment['invoice_number'],
                    'customer_name' => $payment['customer_name']
                ],
                'deleted_documents_count' => $deletedDocsCount
            ]);
        } else {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>