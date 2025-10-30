<?php
header("Content-Type: application/json");
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
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
    
    // Check and create bill_date_from, bill_date_to, and bill_amount columns if they don't exist
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
        error_log("Column check/creation error: " . $e->getMessage());
        // Continue anyway - columns might already exist
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        echo json_encode(["success" => false, "message" => "No files uploaded"]);
        exit;
    }
    
    $uploadDir = '../uploads/documents/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    $createdInvoiceNumber = null; // Track invoice number for email notification
    
    $assignedUserId = $_POST['assigned_user_id'] ?? null;
    $category = $_POST['category'] ?? 'general';
    $description = $_POST['description'] ?? '';
    
    // Get bill data for invoices
    $billDateFrom = $_POST['bill_date_from'] ?? null;
    $billDateTo = $_POST['bill_date_to'] ?? null;
    $billAmount = $_POST['bill_amount'] ?? null;
    
    // Validate that a user is assigned
    if (!$assignedUserId) {
        echo json_encode(["success" => false, "message" => "User assignment is required. Please select a user to assign the document to."]);
        exit;
    }
    
    // Verify the assigned user exists and get email for notifications
    $userCheck = $db->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
    $userCheck->execute([$assignedUserId]);
    $assignedUser = $userCheck->fetch();
    
    if (!$assignedUser) {
        echo json_encode(["success" => false, "message" => "Selected user does not exist. Please select a valid user."]);
        exit;
    }
    
    // Allowed file types
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif'
    ];
    
    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['files']['tmp_name'][$key];
            $fileSize = $_FILES['files']['size'][$key];
            $fileType = $_FILES['files']['type'][$key];
            
            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "File $name: Invalid file type";
                continue;
            }
            
            // Validate file size (50MB max)
            if ($fileSize > 50 * 1024 * 1024) {
                $errors[] = "File $name: File too large (max 50MB)";
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($tmpName, $filePath)) {
                // Check and add invoice_number column if it doesn't exist
                try {
                    $checkInvoiceCol = $db->query("SHOW COLUMNS FROM documents LIKE 'invoice_number'");
                    if ($checkInvoiceCol->rowCount() === 0) {
                        $db->exec("ALTER TABLE documents ADD COLUMN invoice_number VARCHAR(100) NULL");
                    }
                } catch (Exception $e) {
                    error_log("Invoice column check error: " . $e->getMessage());
                }
                
                // Insert into database using existing schema
                $stmt = $db->prepare("
                    INSERT INTO documents (
                        customer_id, 
                        title,
                        file_name, 
                        file_path, 
                        file_size, 
                        file_type, 
                        uploaded_by,
                        upload_date,
                        bill_date_from,
                        bill_date_to,
                        bill_amount,
                        invoice_number
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?)
                ");
                
                // Use the original filename (without extension) as title
                $title = pathinfo($name, PATHINFO_FILENAME);
                
                $stmt->execute([
                    $assignedUserId,  // customer_id (who the document is assigned to)
                    $title,           // title
                    $fileName,        // file_name (generated unique name)
                    $filePath,        // file_path
                    $fileSize,        // file_size
                    $fileType,        // file_type
                    $_SESSION['user_id'], // uploaded_by (current admin user)
                    $billDateFrom,    // bill_date_from
                    $billDateTo,      // bill_date_to
                    $billAmount,      // bill_amount
                    null              // invoice_number (will be updated later if payment is created)
                ]);
                
                // Get the document ID for later update
                $documentId = $db->lastInsertId();
                
                // Sync with payment management if category is invoice and bill data exists
                if ($category === 'invoice' && ($billDateTo || $billAmount)) {
                    try {
                        // Check if payment exists for this customer
                        $paymentCheck = $db->prepare("SELECT id FROM payments WHERE user_id = ?");
                        $paymentCheck->execute([$assignedUserId]);
                        $payment = $paymentCheck->fetch();
                        
                        if ($payment) {
                            // NEVER UPDATE existing invoices - always create new ones to preserve history
                            // This prevents invoice overriding/replacement
                            error_log("Invoice upload: Found existing payment for user " . $assignedUserId . " - creating new invoice instead of updating to preserve history");
                            
                            // Generate user-based invoice number: INV-firstname_ddmmyy
                            $firstName = '';
                            if (!empty($assignedUser['full_name'])) {
                                $nameParts = explode(' ', trim($assignedUser['full_name']));
                                $firstName = $nameParts[0];
                            } else {
                                $firstName = $assignedUser['username'];
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
                                $newInvoice = $baseInvoice . '_' . ($existingCount + 1);
                            } else {
                                $newInvoice = $baseInvoice;
                            }
                            
                            $createPayment = $db->prepare("
                                INSERT INTO payments (user_id, customer_name, email, invoice_number, amount, status, due_date, created_at, description)
                                VALUES (?, ?, ?, ?, ?, 'unpaid', ?, NOW(), ?)
                            ");
                            $createPayment->execute([
                                $assignedUserId,
                                $assignedUser['full_name'] ?: $assignedUser['username'],
                                $assignedUser['email'],
                                $newInvoice,
                                $billAmount ?: 0.00,
                                $billDateTo ?: date('Y-m-d', strtotime('+30 days')),
                                'Invoice document: ' . $title
                            ]);
                            
                            // Update the document with the invoice number
                            $updateDocStmt = $db->prepare("UPDATE documents SET invoice_number = ? WHERE id = ?");
                            $updateDocStmt->execute([$newInvoice, $documentId]);
                            
                            // Store invoice number for email notification
                            $createdInvoiceNumber = $newInvoice;
                            
                            error_log("Created new invoice " . $newInvoice . " to preserve existing invoice history");
                        } else {
                            // Create new payment record - first invoice for this user
                            // Generate user-based invoice number: INV-firstname_ddmmyy
                            $firstName = '';
                            if (!empty($assignedUser['full_name'])) {
                                $nameParts = explode(' ', trim($assignedUser['full_name']));
                                $firstName = $nameParts[0];
                            } else {
                                $firstName = $assignedUser['username'];
                            }
                            
                            // Clean first name (remove special characters, limit length)
                            $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
                            $firstName = substr($firstName, 0, 10); // Limit to 10 characters
                            
                            // Generate date suffix (ddmmyy format)
                            $dateSuffix = date('dmy'); // ddmmyy format: 221025 for Oct 22, 2025
                            
                            // Create invoice number
                            $newInvoice = 'INV-' . $firstName . '_' . $dateSuffix;
                            
                            $createPayment = $db->prepare("
                                INSERT INTO payments (user_id, customer_name, email, invoice_number, amount, status, due_date, created_at)
                                VALUES (?, ?, ?, ?, ?, 'unpaid', ?, NOW())
                            ");
                            $createPayment->execute([
                                $assignedUserId,
                                $assignedUser['full_name'] ?: $assignedUser['username'],
                                $assignedUser['email'],
                                $newInvoice,
                                $billAmount ?: 0.00,
                                $billDateTo ?: date('Y-m-d', strtotime('+30 days'))
                            ]);
                            
                            // Update the document with the invoice number
                            $updateDocStmt = $db->prepare("UPDATE documents SET invoice_number = ? WHERE id = ?");
                            $updateDocStmt->execute([$newInvoice, $documentId]);
                            
                            // Store invoice number for email notification
                            $createdInvoiceNumber = $newInvoice;
                        }
                    } catch (Exception $e) {
                        error_log("Payment sync error: " . $e->getMessage());
                    }
                }
                
                $uploadedFiles[] = [
                    'title' => $title,
                    'original_name' => $name, // Keep for display purposes
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'file_type' => $fileType,
                    'assigned_to' => $assignedUser['full_name'] ?: $assignedUser['username'],
                    'bill_amount' => $billAmount
                ];
                
                // Log the activity
                try {
                    $logStmt = $db->prepare("
                        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                        VALUES (?, 'document_uploaded', ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $logStmt->execute([
                        $_SESSION['user_id'], 
                        "Uploaded document: $title, assigned to: " . ($assignedUser['full_name'] ?: $assignedUser['username']),
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                } catch (Exception $e) {
                    // Log insertion failed, but upload succeeded
                    error_log("Activity log insertion failed: " . $e->getMessage());
                }
                
            } else {
                $errors[] = "File $name: Failed to move uploaded file";
            }
        } else {
            $errors[] = "File $name: Upload error";
        }
    }
    
    $response = [
        "success" => count($uploadedFiles) > 0,
        "uploaded_files" => $uploadedFiles,
        "uploaded_count" => count($uploadedFiles),
        "assigned_to" => $assignedUser['full_name'] ?: $assignedUser['username']
    ];
    
    if (!empty($errors)) {
        $response["errors"] = $errors;
        $response["message"] = "Some files failed to upload: " . implode(", ", $errors);
    } else {
        $assignedName = $assignedUser['full_name'] ?: $assignedUser['username'];
        $response["message"] = count($uploadedFiles) . " files uploaded successfully and assigned to " . $assignedName;
        
        // Send email notification to assigned user
        if (count($uploadedFiles) > 0) {
            try {
                $emailHelper = new EmailHelper();
                $uploaderName = $_SESSION['username'] ?? 'Administrator';
                
                $emailSent = $emailHelper->sendDocumentUploadNotification(
                    $assignedUser['email'],
                    $assignedName,
                    $uploadedFiles,
                    $uploaderName,
                    $category,
                    $createdInvoiceNumber // Pass invoice number for invoice emails
                );
                
                if ($emailSent) {
                    $response["email_sent"] = true;
                    $response["message"] .= ". Email notification sent to " . $assignedUser['email'];
                } else {
                    $response["email_sent"] = false;
                    $response["message"] .= ". Note: Email notification failed to send.";
                }
            } catch (Exception $e) {
                error_log("Email notification failed: " . $e->getMessage());
                $response["email_sent"] = false;
                $response["message"] .= ". Note: Email notification failed to send.";
            }
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Document upload error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Upload failed: " . $e->getMessage()
    ]);
}
?>