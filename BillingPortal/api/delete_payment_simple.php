<?php
// Simple payment deletion API for testing
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    session_start();

    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['payment_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $payment_id = intval($input['payment_id']);

    // Database connection
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    // Check if payments table exists
    $result = $db->query("SHOW TABLES LIKE 'payments'");
    if ($result->rowCount() === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Payments table does not exist. Please recreate the payments table first.',
            'debug' => 'Table not found'
        ]);
        exit;
    }

    // Simple deletion without cascade
    $stmt = $db->prepare("DELETE FROM payments WHERE id = ?");
    $result = $stmt->execute([$payment_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment deleted successfully',
            'deleted_id' => $payment_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment not found or already deleted',
            'payment_id' => $payment_id
        ]);
    }

} catch (Exception $e) {
    error_log("Delete payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
}
?>