<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$auth = new Auth();
$result = $auth->register($input);

echo json_encode($result);
?>