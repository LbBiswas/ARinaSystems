<?php
// api/payments.php - Get all payment records
header('Content-Type: application/json');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if payments table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'payments'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    if (!$tableExists) {
        // Create payments table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            invoice_number VARCHAR(100) NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('paid', 'unpaid', 'pending') DEFAULT 'unpaid',
            due_date DATE NOT NULL,
            payment_date DATE NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($createTable);
    } else {
        // Table exists, check if email column exists and add if missing
        $columnCheck = $db->query("SHOW COLUMNS FROM payments LIKE 'email'");
        if ($columnCheck->rowCount() === 0) {
            // Add email column
            $db->exec("ALTER TABLE payments ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT '' AFTER customer_name");
            
            // Populate email from users table for existing records
            $db->exec("UPDATE payments p 
                       JOIN users u ON p.user_id = u.id 
                       SET p.email = u.email 
                       WHERE p.email = ''");
        }
        
        // Check if status column includes 'unpaid'
        $statusCheck = $db->query("SHOW COLUMNS FROM payments WHERE Field = 'status'");
        $statusColumn = $statusCheck->fetch(PDO::FETCH_ASSOC);
        if ($statusColumn && strpos($statusColumn['Type'], 'unpaid') === false) {
            // Modify status ENUM to include 'unpaid'
            $db->exec("ALTER TABLE payments MODIFY COLUMN status ENUM('paid', 'unpaid', 'pending') DEFAULT 'unpaid'");
        }
    }
    
    if (!$tableExists) {
        
        // Insert sample payment data for all customers
        $customersStmt = $db->query("SELECT id, username, full_name, email FROM users WHERE user_type = 'customer'");
        $customers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($customers) > 0) {
            $insertStmt = $db->prepare("INSERT INTO payments (user_id, customer_name, email, invoice_number, amount, status, due_date, payment_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($customers as $index => $customer) {
                $customerName = $customer['full_name'] ?: $customer['username'];
                $invoiceBase = 2025001 + ($index * 3);
                
                // Create 3 payments per customer
                $samplePayments = [
                    [
                        'invoice' => 'INV-' . $invoiceBase,
                        'amount' => 1250.00 + ($index * 100),
                        'status' => 'paid',
                        'due_date' => '2025-11-01',
                        'payment_date' => '2025-10-28',
                        'description' => 'Monthly service fee'
                    ],
                    [
                        'invoice' => 'INV-' . ($invoiceBase + 1),
                        'amount' => 850.50 + ($index * 50),
                        'status' => 'pending',
                        'due_date' => '2025-11-15',
                        'payment_date' => null,
                        'description' => 'Consulting services'
                    ],
                    [
                        'invoice' => 'INV-' . ($invoiceBase + 2),
                        'amount' => 2100.00 + ($index * 200),
                        'status' => 'unpaid',
                        'due_date' => '2025-10-10',
                        'payment_date' => null,
                        'description' => 'Annual subscription'
                    ]
                ];
                
                foreach ($samplePayments as $payment) {
                    $insertStmt->execute([
                        $customer['id'],
                        $customerName,
                        $customer['email'],
                        $payment['invoice'],
                        $payment['amount'],
                        $payment['status'],
                        $payment['due_date'],
                        $payment['payment_date'],
                        $payment['description']
                    ]);
                }
            }
        }
    }
    
    // Fetch all payments with user information
    // Use COALESCE to handle cases where email might not be in payments table yet
    $query = "SELECT 
                p.id,
                p.user_id,
                p.customer_name,
                COALESCE(p.email, u.email, '') as email,
                p.invoice_number,
                p.amount,
                p.status,
                p.due_date,
                p.payment_date,
                p.description,
                p.created_at,
                p.updated_at,
                u.username
              FROM payments p
              LEFT JOIN users u ON p.user_id = u.id
              ORDER BY p.created_at DESC";
    
    $stmt = $db->query($query);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary
    $summary = [
        'total_payments' => count($payments),
        'total_amount' => 0,
        'paid_amount' => 0,
        'unpaid_amount' => 0,
        'pending_amount' => 0,
        'paid_count' => 0,
        'unpaid_count' => 0,
        'pending_count' => 0
    ];
    
    foreach ($payments as $payment) {
        $amount = floatval($payment['amount']);
        $summary['total_amount'] += $amount;
        
        switch ($payment['status']) {
            case 'paid':
                $summary['paid_amount'] += $amount;
                $summary['paid_count']++;
                break;
            case 'unpaid':
                $summary['unpaid_amount'] += $amount;
                $summary['unpaid_count']++;
                break;
            case 'pending':
                $summary['pending_amount'] += $amount;
                $summary['pending_count']++;
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    error_log("Payments API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading payments: ' . $e->getMessage()
    ]);
}
?>
