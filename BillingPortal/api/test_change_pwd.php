<?php
// Simple test to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Starting session...<br>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session started!<br>";

echo "Step 2: Loading database...<br>";
$dbPath = dirname(__DIR__) . '/config/database.php';
echo "DB Path: $dbPath<br>";
if (file_exists($dbPath)) {
    require_once $dbPath;
    echo "Database.php loaded!<br>";
} else {
    die("Database.php not found!");
}

echo "Step 3: Creating Database instance...<br>";
try {
    $database = new Database();
    echo "Database object created!<br>";
    
    $conn = $database->getConnection();
    echo "Connection established!<br>";
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

echo "Step 4: Loading Auth class...<br>";
$authPath = dirname(__DIR__) . '/includes/auth.php';
echo "Auth Path: $authPath<br>";
if (file_exists($authPath)) {
    require_once $authPath;
    echo "Auth.php loaded!<br>";
} else {
    die("Auth.php not found!");
}

echo "Step 5: Creating Auth instance...<br>";
try {
    $auth = new Auth();
    echo "Auth object created!<br>";
} catch (Exception $e) {
    die("Auth error: " . $e->getMessage());
}

echo "Step 6: Checking changePassword method...<br>";
if (method_exists($auth, 'changePassword')) {
    echo "✅ changePassword method exists!<br>";
} else {
    die("❌ changePassword method NOT found!");
}

echo "<hr>";
echo "<h2>All checks passed! ✅</h2>";
echo "<p>The backend should work. Try changing password again.</p>";

// Test with a fake user
$_SESSION['user_id'] = 999;
echo "<br>Step 7: Testing changePassword (will fail with wrong password)...<br>";
try {
    $result = $auth->changePassword(999, 'wrong', 'newpass123');
    echo "Result: " . json_encode($result);
} catch (Exception $e) {
    echo "Error (expected): " . $e->getMessage();
}
?>
