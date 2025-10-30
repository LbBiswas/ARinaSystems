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
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Ensure bill_date, bill_date_from, bill_date_to, and bill_amount columns exist
            try {
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_date DATE NULL AFTER description");
                }
                
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date_from'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_date_from DATE NULL AFTER description");
                }
                
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date_to'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_date_to DATE NULL AFTER bill_date_from");
                }
                
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_amount'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_amount DECIMAL(10,2) NULL DEFAULT 0.00 AFTER bill_date_to");
                }
            } catch (Exception $e) {
                error_log("Column check/creation error: " . $e->getMessage());
            }
            
            // Get all documents with user information
            $stmt = $db->query("
                SELECT 
                    d.*,
                    u.username as owner_name,
                    u.full_name,
                    CONCAT(COALESCE(u.full_name, u.username), ' (', u.email, ')') as owner_display
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                ORDER BY d.upload_date DESC
            ");
            
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "documents" => $documents
            ]);
            break;
            
        case 'DELETE':
            // Delete document
            $input = json_decode(file_get_contents("php://input"), true);
            $documentId = $input['id'] ?? null;
            
            if (!$documentId) {
                echo json_encode(["success" => false, "message" => "Document ID is required"]);
                exit;
            }
            
            // Get document info with customer details for notification
            $stmt = $db->prepare("
                SELECT d.file_name, d.title, d.customer_id, d.uploaded_by,
                       u.email as customer_email, u.full_name as customer_name, u.username,
                       uploader.full_name as uploader_name, uploader.username as uploader_username
                FROM documents d
                LEFT JOIN users u ON d.customer_id = u.id  
                LEFT JOIN users uploader ON d.uploaded_by = uploader.id
                WHERE d.id = ?
            ");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();
            
            if (!$document) {
                echo json_encode(["success" => false, "message" => "Document not found"]);
                exit;
            }
            
            // Begin transaction for safe deletion
            $db->beginTransaction();
            
            // Handle foreign key constraints by checking and updating/deleting related records
            try {
                // Check if payments table exists
                $tableCheck = $db->query("SHOW TABLES LIKE 'payments'");
                if ($tableCheck->rowCount() > 0) {
                    // First, check if there are any payments referencing this document
                    $checkPayments = $db->prepare("SELECT id, invoice_number FROM payments WHERE document_id = ?");
                    $checkPayments->execute([$documentId]);
                    $relatedPayments = $checkPayments->fetchAll();
                    
                    if (!empty($relatedPayments)) {
                        // Try to set document_id to NULL first
                        try {
                            $updatePayments = $db->prepare("UPDATE payments SET document_id = NULL WHERE document_id = ?");
                            $updatePayments->execute([$documentId]);
                        } catch (Exception $updateError) {
                            // If UPDATE fails, try dropping the foreign key constraint temporarily
                            try {
                                // Get the constraint name
                                $constraintQuery = $db->query("
                                    SELECT CONSTRAINT_NAME 
                                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                    WHERE TABLE_SCHEMA = DATABASE() 
                                    AND TABLE_NAME = 'payments' 
                                    AND COLUMN_NAME = 'document_id' 
                                    AND REFERENCED_TABLE_NAME = 'documents'
                                ");
                                $constraint = $constraintQuery->fetch();
                                
                                if ($constraint) {
                                    // Temporarily drop the constraint
                                    $db->exec("ALTER TABLE payments DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME']);
                                    
                                    // Now update the payments
                                    $updatePayments = $db->prepare("UPDATE payments SET document_id = NULL WHERE document_id = ?");
                                    $updatePayments->execute([$documentId]);
                                    
                                    // Recreate the constraint with proper ON DELETE SET NULL
                                    $db->exec("ALTER TABLE payments ADD CONSTRAINT " . $constraint['CONSTRAINT_NAME'] . " 
                                              FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL ON UPDATE CASCADE");
                                }
                            } catch (Exception $constraintError) {
                                // If all else fails, delete the related payment records
                                error_log("Constraint handling failed, deleting related payments: " . $constraintError->getMessage());
                                foreach ($relatedPayments as $payment) {
                                    $deletePayment = $db->prepare("DELETE FROM payments WHERE id = ?");
                                    $deletePayment->execute([$payment['id']]);
                                    
                                    // Log the payment deletion
                                    error_log("Auto-deleted payment {$payment['invoice_number']} due to document deletion constraint");
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error handling foreign key constraints: " . $e->getMessage());
                // Continue with document deletion - constraints might not exist
            }
            
            // Now delete from database
            $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$documentId]);
            
            if ($stmt->rowCount() === 0) {
                $db->rollback();
                echo json_encode(["success" => false, "message" => "No document was deleted from database"]);
                exit;
            }
            
            // Log the activity (try activity_log first, then activity_logs)
            $logSuccess = false;
            try {
                $logStmt = $db->prepare("INSERT INTO activity_log (user_id, action, description, created_at) VALUES (?, 'document_deleted', ?, NOW())");
                $logStmt->execute([$_SESSION['user_id'], "Deleted document: " . $document['title']]);
                $logSuccess = true;
            } catch (Exception $logError) {
                try {
                    $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, 'document_deleted', ?, ?, CURRENT_TIMESTAMP)");
                    $logStmt->execute([$_SESSION['user_id'], "Deleted document: " . $document['title'], $_SERVER['REMOTE_ADDR'] ?? '']);
                    $logSuccess = true;
                } catch (Exception $e) {
                    error_log("Activity log insertion failed: " . $e->getMessage());
                }
            }
            
            // Commit the transaction
            $db->commit();
            
            // Delete file from filesystem after successful database deletion
            $filePath = "../uploads/documents/" . $document['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Send notification to customer if document was uploaded by them
            if ($document['customer_email'] && ($document['customer_id'] == $document['uploaded_by'])) {
                try {
                    require_once '../includes/email_helper.php';
                    $emailHelper = new EmailHelper();
                    
                    $customerName = $document['customer_name'] ?: $document['username'];
                    $adminName = $_SESSION['full_name'] ?: $_SESSION['username'] ?: 'System Administrator';
                    
                    $emailHelper->sendDocumentDeletionNotification(
                        $document['customer_email'],
                        $customerName,
                        $document['title'],
                        $adminName
                    );
                } catch (Exception $e) {
                    error_log("Failed to send deletion notification: " . $e->getMessage());
                    // Don't fail the deletion if email fails
                }
            }
            
            echo json_encode([
                "success" => true, 
                "message" => "Document deleted successfully"
            ]);
            break;
            
        case 'PUT':
            // Update document metadata
            $input = json_decode(file_get_contents("php://input"), true);
            
            $documentId = $input['id'] ?? null;
            $category = $input['category'] ?? '';
            $description = $input['description'] ?? '';
            $assignedUserId = $input['assigned_user_id'] ?? null;
            $billDateFrom = $input['bill_date_from'] ?? null;
            $billDateTo = $input['bill_date_to'] ?? null;
            $billAmount = $input['bill_amount'] ?? null;
            
            if (!$documentId) {
                echo json_encode(["success" => false, "message" => "Document ID is required"]);
                exit;
            }
            
            // Check if bill columns exist, add if not
            try {
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date_from'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_date_from DATE NULL AFTER description");
                }
                
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_date_to'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_date_to DATE NULL AFTER bill_date_from");
                }
                
                $checkColumn = $db->query("SHOW COLUMNS FROM documents LIKE 'bill_amount'");
                if ($checkColumn->rowCount() === 0) {
                    $db->exec("ALTER TABLE documents ADD COLUMN bill_amount DECIMAL(10,2) NULL DEFAULT 0.00 AFTER bill_date_to");
                }
            } catch (Exception $e) {
                error_log("Column check/creation error: " . $e->getMessage());
            }
            
            // Update document
            $sql = "UPDATE documents SET category = ?, description = ?";
            $params = [$category, $description];
            
            if ($billDateFrom !== null) {
                $sql .= ", bill_date_from = ?";
                $params[] = $billDateFrom;
            }
            
            if ($billDateTo !== null) {
                $sql .= ", bill_date_to = ?";
                $params[] = $billDateTo;
            }
            
            if ($billAmount !== null) {
                $sql .= ", bill_amount = ?";
                $params[] = floatval($billAmount);
            }
            
            $sql .= ", updated_at = NOW()";
            
            if ($assignedUserId) {
                $sql .= ", uploaded_by = ?";
                $params[] = $assignedUserId;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $documentId;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // If bill_date_to and bill_amount are set, sync with payment management (use bill_date_to as due date)
            // ONLY CREATE NEW INVOICES - DO NOT UPDATE EXISTING ONES TO PRESERVE INVOICE HISTORY
            if ($billDateTo && $billAmount && $assignedUserId && $category === 'invoice') {
                try {
                    // Check if payment exists for this user
                    $paymentCheck = $db->prepare("SELECT id, status FROM payments WHERE user_id = ?");
                    $paymentCheck->execute([$assignedUserId]);
                    $payment = $paymentCheck->fetch(PDO::FETCH_ASSOC);
                    
                    if ($payment) {
                        // DO NOT UPDATE existing payment - preserve invoice history for reports
                        // Only log that we found an existing payment
                        error_log("Document edit: Found existing payment ID " . $payment['id'] . " for user " . $assignedUserId . " - preserving invoice history");
                    } else {
                        // Create new payment record
                        $userStmt = $db->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
                        $userStmt->execute([$assignedUserId]);
                        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            // Generate user-based invoice number: INV-firstname_ddmmyy
                            $firstName = '';
                            if (!empty($user['full_name'])) {
                                $nameParts = explode(' ', trim($user['full_name']));
                                $firstName = $nameParts[0];
                            } else {
                                $firstName = $user['username'];
                            }
                            
                            // Clean first name (remove special characters, limit length)
                            $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
                            $firstName = substr($firstName, 0, 10); // Limit to 10 characters
                            
                            // Generate date suffix (ddmmyy format)
                            $dateSuffix = date('dmy'); // ddmmyy format: 221025 for Oct 22, 2025
                            
                            // Create base invoice number
                            $baseInvoice = 'INV-' . $firstName . '_' . $dateSuffix;
                            
                            // Check if this exact invoice number exists, add sequence if needed
                            $sequenceCheck = $db->prepare("SELECT COUNT(*) as count FROM payments WHERE invoice_number LIKE ?");
                            $sequenceCheck->execute([$baseInvoice . '%']);
                            $existingCount = $sequenceCheck->fetch()['count'];
                            
                            if ($existingCount > 0) {
                                // If invoice exists, add sequence number
                                $invoiceNumber = $baseInvoice . '_' . ($existingCount + 1);
                            } else {
                                $invoiceNumber = $baseInvoice;
                            }
                            
                            $createPayment = $db->prepare("INSERT INTO payments (user_id, customer_name, email, invoice_number, amount, status, due_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $createPayment->execute([
                                $assignedUserId,
                                $user['full_name'] ?: $user['username'],
                                $user['email'],
                                $invoiceNumber,
                                $billAmount,
                                'unpaid',
                                $billDateTo,
                                'Document billing'
                            ]);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Payment sync error: " . $e->getMessage());
                }
            }
            
            echo json_encode([
                "success" => true, 
                "message" => "Document updated successfully"
            ]);
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "Method not allowed"]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Document admin error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database error occurred"
    ]);
}
?>