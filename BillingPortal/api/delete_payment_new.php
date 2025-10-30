<?php
// API endpoint for deleting payment records
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, log them instead

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

// Validate payment ID
if (!isset($input['payment_id']) || !is_numeric($input['payment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID provided']);
    exit;
}

$payment_id = intval($input['payment_id']);

try {
    // Include database configuration
    require_once '../config/database.php';
    
    // Database connection using proper config
    $database = new Database();
    $db = $database->getConnection();

    // Check if the payment exists first
    $checkStmt = $db->prepare("SELECT id, invoice_number, customer_name, amount, status FROM payments WHERE id = ?");
    $checkStmt->execute([$payment_id]);
    $payment = $checkStmt->fetch();

    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment record not found']);
        exit;
    }

    // Begin transaction for safe deletion
    $db->beginTransaction();

    // Delete the payment record
    $deleteStmt = $db->prepare("DELETE FROM payments WHERE id = ?");
    $deleteStmt->execute([$payment_id]);

    if ($deleteStmt->rowCount() === 0) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'No payment record was deleted']);
        exit;
    }

    // Optional: Log the deletion (only if activity_log table exists)
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE 'activity_log'");
        if ($tableCheck->rowCount() > 0) {
            $logStmt = $db->prepare("INSERT INTO activity_log (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
            $logDescription = "Deleted payment record: Invoice {$payment['invoice_number']}, Customer: {$payment['customer_name']}, Amount: $" . number_format($payment['amount'], 2);
            $logStmt->execute([
                $_SESSION['user_id'],
                'delete_payment',
                $logDescription
            ]);
        }
    } catch (Exception $logError) {
        // Log error is not critical, continue with the main operation
        error_log("Failed to log payment deletion: " . $logError->getMessage());
    }

    // Commit the transaction
    $db->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment record deleted successfully',
        'deleted_payment' => [
            'id' => $payment['id'],
            'invoice_number' => $payment['invoice_number'],
            'customer_name' => $payment['customer_name']
        ]
    ]);

} catch (PDOException $e) {
    // Rollback transaction if it's active
    if ($db && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Database error in delete_payment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error_details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if it's active
    if (isset($db) && $db && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("General error in delete_payment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while deleting the payment',
        'error_details' => $e->getMessage()
    ]);
}
?>