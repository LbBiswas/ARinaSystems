<?php
// Minimal delete payment API for testing
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    session_start();

    // Basic auth check
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Method check
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

    // Test database connection
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    // Simple existence check and deletion
    $stmt = $db->prepare("SELECT id, invoice_number FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }

    // Delete payment
    $deleteStmt = $db->prepare("DELETE FROM payments WHERE id = ?");
    $deleteStmt->execute([$payment_id]);

    if ($deleteStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment deleted successfully',
            'deleted_id' => $payment_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>