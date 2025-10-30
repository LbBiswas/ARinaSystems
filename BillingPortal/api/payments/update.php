<?php
// api/payments/update.php - Update single payment
header('Content-Type: application/json');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $paymentId = intval($input['payment_id'] ?? 0);
    $invoiceNumber = trim($input['invoice_number'] ?? '');
    $amount = floatval($input['amount'] ?? 0);
    $status = $input['status'] ?? '';
    $paymentDate = $input['payment_date'] ?? null;

    if (!$paymentId) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        exit;
    }

    if (empty($invoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invoice number is required']);
        exit;
    }

    if ($amount < 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than or equal to 0']);
        exit;
    }

    if (!in_array($status, ['paid', 'unpaid', 'pending'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment status']);
        exit;
    }

    // If status is not 'paid', clear payment date
    if ($status !== 'paid') {
        $paymentDate = null;
    }

    // Check if invoice number is unique (excluding current payment)
    $checkStmt = $db->prepare("SELECT id FROM payments WHERE invoice_number = ? AND id != ?");
    $checkStmt->execute([$invoiceNumber, $paymentId]);
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Invoice number already exists']);
        exit;
    }

    // Update payment
    $stmt = $db->prepare("UPDATE payments SET invoice_number = ?, amount = ?, status = ?, payment_date = ? WHERE id = ?");
    $stmt->execute([$invoiceNumber, $amount, $status, $paymentDate, $paymentId]);
    
    if ($stmt->rowCount() > 0) {
        // Log the activity
        try {
            $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, 'payment_update', ?, ?, CURRENT_TIMESTAMP)");
            $logStmt->execute([
                $_SESSION['user_id'],
                "Updated payment #$paymentId: Invoice=$invoiceNumber, Amount=$amount, Status=$status",
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Payment updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment not found or no changes made'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Payment update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating payment: ' . $e->getMessage()
    ]);
}
?>
