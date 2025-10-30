<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $type = isset($_GET['type']) ? $_GET['type'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            
            $query = "SELECT id as user_id, username, email, full_name, user_type, phone, status as account_status, created_at, updated_at FROM users WHERE 1=1";
            $params = [];
            
            if ($type && $type !== 'all') {
                $query .= " AND user_type = :type";
                $params[':type'] = $type;
            }
            if ($search) {
                $query .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            if ($status && $status !== 'all') {
                $query .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            $query .= " ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;
        
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Username, email, and password are required']);
                exit;
            }
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $data['username']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Username already exists']);
                exit;
            }
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $data['email']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, full_name, user_type, phone, status) VALUES (:username, :email, :password, :full_name, :user_type, :phone, :status)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':username' => $data['username'], ':email' => $data['email'], ':password' => $hashedPassword, ':full_name' => $data['full_name'] ?? '', ':user_type' => $data['user_type'] ?? 'customer', ':phone' => $data['phone'] ?? '', ':status' => $data['account_status'] ?? 'active']);
            if ($result) {
                $userId = $db->lastInsertId();
                
                // Auto-create payment record if user is a customer
                if (($data['user_type'] ?? 'customer') === 'customer') {
                    try {
                        // Generate user-based invoice number: INV-firstname_ddmmyy
                        $firstName = '';
                        if (!empty($data['full_name'])) {
                            $nameParts = explode(' ', trim($data['full_name']));
                            $firstName = $nameParts[0];
                        } else {
                            $firstName = $data['username'];
                        }
                        
                        // Clean first name (remove special characters, limit length)
                        $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
                        $firstName = substr($firstName, 0, 10); // Limit to 10 characters
                        
                        // Generate date suffix (ddmmyy format)
                        $dateSuffix = date('dmy'); // ddmmyy format: 221025 for Oct 22, 2025
                        
                        // Create invoice number
                        $invoiceNumber = 'INV-' . $firstName . '_' . $dateSuffix;
                        
                        // Create initial payment record
                        $paymentStmt = $db->prepare("INSERT INTO payments (user_id, customer_name, email, invoice_number, amount, status, due_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $paymentStmt->execute([
                            $userId,
                            $data['full_name'] ?? $data['username'],
                            $data['email'],
                            $invoiceNumber,
                            0.00, // Default amount
                            'unpaid', // Default status
                            date('Y-m-d', strtotime('+30 days')), // Due in 30 days
                            'Initial account setup'
                        ]);
                    } catch (Exception $e) {
                        error_log("Failed to create payment record: " . $e->getMessage());
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'User created successfully', 'user_id' => $userId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create user']);
            }
            break;
        
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                exit;
            }
            if (!empty($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid email format']);
                    exit;
                }
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
                $stmt->execute([':email' => $data['email'], ':user_id' => $data['user_id']]);
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Email already exists']);
                    exit;
                }
            }
            $updates = [];
            $params = [':user_id' => $data['user_id']];
            
            // Map frontend field names to database column names
            $fieldMapping = [
                'email' => 'email',
                'full_name' => 'full_name',
                'phone' => 'phone',
                'account_status' => 'status',
                'user_type' => 'user_type'
            ];
            
            foreach ($fieldMapping as $frontendField => $dbColumn) {
                if (isset($data[$frontendField])) {
                    $updates[] = "$dbColumn = :$frontendField";
                    $params[":$frontendField"] = $data[$frontendField];
                }
            }
            
            if (!empty($data['password'])) {
                $updates[] = "password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit;
            }
            $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute($params);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update user']);
            }
            break;
        
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                exit;
            }
            if ($data['user_id'] == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete your own account']);
                exit;
            }
            
            // Check for related data
            $stmt = $db->prepare("SELECT COUNT(*) as doc_count FROM documents WHERE customer_id = :user_id");
            $stmt->execute([':user_id' => $data['user_id']]);
            $docCount = $stmt->fetch(PDO::FETCH_ASSOC)['doc_count'];
            
            $stmt = $db->prepare("SELECT COUNT(*) as payment_count FROM payments WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $data['user_id']]);
            $paymentCount = $stmt->fetch(PDO::FETCH_ASSOC)['payment_count'];
            
            // If user has related data and force delete not confirmed, ask for confirmation
            if (($docCount > 0 || $paymentCount > 0) && !isset($data['force'])) {
                $message = "User has";
                if ($docCount > 0) $message .= " $docCount document(s)";
                if ($docCount > 0 && $paymentCount > 0) $message .= " and";
                if ($paymentCount > 0) $message .= " $paymentCount payment record(s)";
                $message .= ". All related data will be permanently deleted. Continue?";
                
                echo json_encode([
                    'warning' => true, 
                    'message' => $message, 
                    'doc_count' => $docCount,
                    'payment_count' => $paymentCount
                ]);
                exit;
            }
            
            // Begin transaction for safe deletion
            $db->beginTransaction();
            
            try {
                // Delete document files from filesystem and database
                if ($docCount > 0) {
                    // Get document files to delete from filesystem
                    $stmt = $db->prepare("SELECT file_name, file_path FROM documents WHERE customer_id = :user_id");
                    $stmt->execute([':user_id' => $data['user_id']]);
                    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Delete files from filesystem
                    foreach ($documents as $doc) {
                        $filePath = "../uploads/documents/" . $doc['file_name'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    // Delete document records
                    $stmt = $db->prepare("DELETE FROM documents WHERE customer_id = :user_id");
                    $stmt->execute([':user_id' => $data['user_id']]);
                }
                
                // Delete payment records
                if ($paymentCount > 0) {
                    $stmt = $db->prepare("DELETE FROM payments WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $data['user_id']]);
                }
                
                // Delete activity logs
                $stmt = $db->prepare("DELETE FROM activity_logs WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $data['user_id']]);
                
                // Finally delete the user
                $stmt = $db->prepare("DELETE FROM users WHERE id = :user_id");
                $result = $stmt->execute([':user_id' => $data['user_id']]);
                
                if ($result) {
                    $db->commit();
                    echo json_encode([
                        'success' => true, 
                        'message' => 'User and all related data deleted successfully',
                        'deleted_documents' => $docCount,
                        'deleted_payments' => $paymentCount
                    ]);
                } else {
                    $db->rollback();
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to delete user']);
                }
                
            } catch (Exception $e) {
                $db->rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete user and related data: ' . $e->getMessage()]);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
