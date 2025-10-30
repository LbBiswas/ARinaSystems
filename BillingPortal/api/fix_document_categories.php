<?php
// Script to fix NULL categories in documents table
// This script should be run once to fix existing data

header('Content-Type: application/json');

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'arinasystems_newbilling';
    $username = 'arinasystems_newbilling';
    $password = 'arinasystems_newbilling';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Check how many documents have NULL or empty categories
    $checkStmt = $db->query("SELECT COUNT(*) as null_count FROM documents WHERE category IS NULL OR category = ''");
    $nullCount = $checkStmt->fetch()['null_count'];

    if ($nullCount > 0) {
        // Update NULL or empty categories to 'General'
        $updateStmt = $db->prepare("UPDATE documents SET category = 'General' WHERE category IS NULL OR category = ''");
        $updateStmt->execute();
        $updatedRows = $updateStmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => "Fixed $updatedRows documents with missing categories",
            'updated_count' => $updatedRows,
            'null_count_before' => $nullCount
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'All documents already have valid categories',
            'updated_count' => 0,
            'null_count_before' => 0
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in fix_document_categories.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in fix_document_categories.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>