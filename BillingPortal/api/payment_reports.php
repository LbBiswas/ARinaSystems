<?php
// api/payment_reports.php - Payment tracking API
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
    
    // Check if payments table exists, if not create it
    $tableCheck = $db->query("SHOW TABLES LIKE 'payments'");
    if ($tableCheck->rowCount() === 0) {
        // Create payments table
        $createTable = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            invoice_number VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            due_date DATE NOT NULL,
            payment_date DATE NULL,
            status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($createTable);
        
        // Insert sample payment data
        $sampleData = [
            [
                'user_id' => 2, // Assuming customer user ID
                'customer_name' => 'Demo Customer',
                'invoice_number' => 'INV-2025-001',
                'amount' => 1250.00,
                'due_date' => '2025-11-01',
                'payment_date' => '2025-10-28',
                'status' => 'paid',
                'description' => 'Monthly billing service fee'
            ],
            [
                'user_id' => 2,
                'customer_name' => 'Demo Customer',
                'invoice_number' => 'INV-2025-002',
                'amount' => 850.50,
                'due_date' => '2025-11-15',
                'payment_date' => null,
                'status' => 'pending',
                'description' => 'Consulting services'
            ],
            [
                'user_id' => 2,
                'customer_name' => 'Demo Customer',
                'invoice_number' => 'INV-2025-003',
                'amount' => 2100.00,
                'due_date' => '2025-10-10',
                'payment_date' => null,
                'status' => 'overdue',
                'description' => 'Annual subscription fee'
            ],
            [
                'user_id' => 2,
                'customer_name' => 'Demo Customer',
                'invoice_number' => 'INV-2025-004',
                'amount' => 450.75,
                'due_date' => '2025-11-20',
                'payment_date' => null,
                'status' => 'pending',
                'description' => 'Additional services'
            ],
            [
                'user_id' => 2,
                'customer_name' => 'Demo Customer',
                'invoice_number' => 'INV-2025-005',
                'amount' => 1800.00,
                'due_date' => '2025-10-05',
                'payment_date' => '2025-10-04',
                'status' => 'paid',
                'description' => 'Project milestone payment'
            ],
            [
                'user_id' => 2,
                'customer_name' => 'Demo Customer',
                'invoice_number' => 'INV-2025-006',
                'amount' => 675.25,
                'due_date' => '2025-09-25',
                'payment_date' => null,
                'status' => 'overdue',
                'description' => 'Maintenance fee'
            ]
        ];
        
        $insertStmt = $db->prepare("INSERT INTO payments (user_id, customer_name, invoice_number, amount, due_date, payment_date, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sampleData as $payment) {
            $insertStmt->execute([
                $payment['user_id'],
                $payment['customer_name'],
                $payment['invoice_number'],
                $payment['amount'],
                $payment['due_date'],
                $payment['payment_date'],
                $payment['status'],
                $payment['description']
            ]);
        }
    }
    
    // Fetch all payments with user information
    $query = "SELECT 
                p.id,
                p.user_id,
                p.customer_name,
                p.invoice_number,
                p.amount,
                p.due_date,
                p.payment_date,
                p.status,
                p.description,
                p.created_at,
                u.username,
                u.email
              FROM payments p
              LEFT JOIN users u ON p.user_id = u.id
              ORDER BY p.due_date DESC";
    
    $stmt = $db->query($query);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    $summary = [
        'total_amount' => 0,
        'paid_amount' => 0,
        'pending_amount' => 0,
        'overdue_amount' => 0,
        'total_count' => count($payments),
        'paid_count' => 0,
        'pending_count' => 0,
        'overdue_count' => 0
    ];
    
    foreach ($payments as $payment) {
        $amount = floatval($payment['amount']);
        $summary['total_amount'] += $amount;
        
        switch ($payment['status']) {
            case 'paid':
                $summary['paid_amount'] += $amount;
                $summary['paid_count']++;
                break;
            case 'pending':
                $summary['pending_amount'] += $amount;
                $summary['pending_count']++;
                break;
            case 'overdue':
                $summary['overdue_amount'] += $amount;
                $summary['overdue_count']++;
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    error_log("Payment reports error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading payment data: ' . $e->getMessage()
    ]);
}
?>
