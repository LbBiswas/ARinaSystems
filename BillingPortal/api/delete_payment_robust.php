<?php
// Simplified and robust delete payment API
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in JSON response
ini_set('log_errors', 1);

// Function to send JSON response and exit
function sendResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

// Function to log errors
function logError($message, $exception = null) {
    $log = "Delete Payment Error: " . $message;
    if ($exception) {
        $log .= " | " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
    }
    error_log($log);
}

try {
    // Start session
    session_start();

    // Authentication check
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        logError("Unauthorized access - user_id: " . ($_SESSION['user_id'] ?? 'null') . ", user_type: " . ($_SESSION['user_type'] ?? 'null'));
        http_response_code(401);
        sendResponse(false, 'Unauthorized access');
    }

    // Method check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logError("Invalid method: " . $_SERVER['REQUEST_METHOD']);
        http_response_code(405);
        sendResponse(false, 'Method not allowed');
    }

    // Get and validate input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        logError("Invalid JSON: " . json_last_error_msg() . " | Raw input: " . $rawInput);
        http_response_code(400);
        sendResponse(false, 'Invalid JSON input');
    }

    if (!isset($input['payment_id']) || !is_numeric($input['payment_id'])) {
        logError("Invalid payment_id: " . ($input['payment_id'] ?? 'null'));
        http_response_code(400);
        sendResponse(false, 'Invalid payment ID');
    }

    $payment_id = intval($input['payment_id']);

    // Database connection
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    // Check if payment exists
    $stmt = $db->prepare("SELECT id, invoice_number, customer_name, amount FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        logError("Payment not found: ID {$payment_id}");
        http_response_code(404);
        sendResponse(false, 'Payment record not found');
    }

    // Simple deletion with transaction
    $db->beginTransaction();
    
    try {
        // Delete the payment
        $deleteStmt = $db->prepare("DELETE FROM payments WHERE id = ?");
        $deleteStmt->execute([$payment_id]);

        if ($deleteStmt->rowCount() === 0) {
            throw new Exception("No payment was deleted");
        }

        // Commit transaction
        $db->commit();

        // Log success
        error_log("Payment deleted successfully: ID {$payment_id}, Invoice: {$payment['invoice_number']}");

        // Success response
        sendResponse(true, 'Payment deleted successfully', [
            'deleted_payment' => [
                'id' => $payment['id'],
                'invoice_number' => $payment['invoice_number'],
                'customer_name' => $payment['customer_name']
            ]
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    logError("Exception occurred", $e);
    http_response_code(500);
    sendResponse(false, 'An error occurred while deleting the payment');
}
?>