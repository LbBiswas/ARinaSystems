<?php
// api/payments/bulk-update.php - Bulk update payments
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

    $updates = $input['updates'] ?? [];

    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();

    $updateStmt = $db->prepare("UPDATE payments SET invoice_number = ?, amount = ?, status = ?, payment_date = ? WHERE id = ?");
    $successCount = 0;
    $errors = [];

    foreach ($updates as $update) {
        $paymentId = intval($update['payment_id'] ?? 0);
        $invoiceNumber = trim($update['invoice_number'] ?? '');
        $amount = floatval($update['amount'] ?? 0);
        $status = $update['status'] ?? '';
        $paymentDate = $update['payment_date'] ?? null;

        if (!$paymentId || !in_array($status, ['paid', 'unpaid', 'pending']) || empty($invoiceNumber) || $amount < 0) {
            $errors[] = "Payment #$paymentId: Invalid data";
            continue;
        }

        // Check if invoice number is unique (excluding current payment)
        $checkStmt = $db->prepare("SELECT id FROM payments WHERE invoice_number = ? AND id != ?");
        $checkStmt->execute([$invoiceNumber, $paymentId]);
        if ($checkStmt->rowCount() > 0) {
            $errors[] = "Payment #$paymentId: Invoice number '$invoiceNumber' already exists";
            continue;
        }

        // If status is not 'paid', clear payment date
        if ($status !== 'paid') {
            $paymentDate = null;
        }

        $updateStmt->execute([$invoiceNumber, $amount, $status, $paymentDate, $paymentId]);
        
        if ($updateStmt->rowCount() > 0) {
            $successCount++;
        }
    }

    // Commit transaction
    $db->commit();

    // Log the activity
    try {
        $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, 'bulk_payment_update', ?, ?, CURRENT_TIMESTAMP)");
        $logStmt->execute([
            $_SESSION['user_id'],
            "Bulk updated $successCount payment(s)",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully updated $successCount payment(s)",
        'updated_count' => $successCount,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Bulk payment update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating payments: ' . $e->getMessage()
    ]);
}
?>
