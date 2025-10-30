<?php
// Enhanced delete payment with multiple linking strategies
header('Content-Type: application/json');

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
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Get payment details
        $paymentStmt = $db->prepare("SELECT id, invoice_number, customer_name, user_id FROM payments WHERE id = ?");
        $paymentStmt->execute([$id]);
        $payment = $paymentStmt->fetch();
        
        if (!$payment) {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }
        
        // Find and delete related documents using multiple strategies
        $deletedDocsCount = 0;
        
        // Check if documents table exists
        $docTableCheck = $db->query("SHOW TABLES LIKE 'documents'");
        if ($docTableCheck->rowCount() > 0) {
            
            // Get documents table structure
            $docColumns = $db->query("DESCRIBE documents")->fetchAll();
            $availableColumns = array_column($docColumns, 'Field');
            
            $relatedDocs = [];
            
            // Strategy 1: Match by invoice_number (if column exists)
            if (in_array('invoice_number', $availableColumns)) {
                $docsStmt = $db->prepare("SELECT id, file_name, title FROM documents WHERE invoice_number = ?");
                $docsStmt->execute([$payment['invoice_number']]);
                $docs = $docsStmt->fetchAll();
                $relatedDocs = array_merge($relatedDocs, $docs);
                error_log("Found " . count($docs) . " documents by invoice_number: {$payment['invoice_number']}");
            }
            
            // Strategy 2: Match by user_id/uploaded_by (if payment has user_id)
            if ($payment['user_id'] && in_array('uploaded_by', $availableColumns)) {
                $docsStmt = $db->prepare("SELECT id, file_name, title FROM documents WHERE uploaded_by = ?");
                $docsStmt->execute([$payment['user_id']]);
                $docs = $docsStmt->fetchAll();
                // Only add if not already found by invoice number
                foreach ($docs as $doc) {
                    $exists = false;
                    foreach ($relatedDocs as $existing) {
                        if ($existing['id'] == $doc['id']) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $relatedDocs[] = $doc;
                    }
                }
                error_log("Found " . count($docs) . " additional documents by user_id: {$payment['user_id']}");
            }
            
            // Strategy 3: Match by customer_id (if both tables have it)
            if (in_array('customer_id', $availableColumns)) {
                // First get customer_id from users table if payment has user_id
                if ($payment['user_id']) {
                    $userCheck = $db->query("SHOW TABLES LIKE 'users'");
                    if ($userCheck->rowCount() > 0) {
                        $customerStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                        $customerStmt->execute([$payment['user_id']]);
                        if ($customerStmt->fetch()) {
                            $docsStmt = $db->prepare("SELECT id, file_name, title FROM documents WHERE customer_id = ?");
                            $docsStmt->execute([$payment['user_id']]);
                            $docs = $docsStmt->fetchAll();
                            // Only add if not already found
                            foreach ($docs as $doc) {
                                $exists = false;
                                foreach ($relatedDocs as $existing) {
                                    if ($existing['id'] == $doc['id']) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                if (!$exists) {
                                    $relatedDocs[] = $doc;
                                }
                            }
                            error_log("Found " . count($docs) . " additional documents by customer_id: {$payment['user_id']}");
                        }
                    }
                }
            }
            
            // Delete found documents
            foreach ($relatedDocs as $doc) {
                // Delete physical file
                $filePath = "../uploads/documents/" . $doc['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete database record
                $deleteDocStmt = $db->prepare("DELETE FROM documents WHERE id = ?");
                $deleteDocStmt->execute([$doc['id']]);
                $deletedDocsCount++;
                
                error_log("Auto-deleted document '{$doc['title']}' (ID: {$doc['id']}) due to payment deletion");
            }
        }
        
        // Delete the payment
        $stmt = $db->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $db->commit();
            
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
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>