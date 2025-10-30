<?php
// api/debug.php - Comprehensive connection testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
    ],
    'php_extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
        'mysqli' => extension_loaded('mysqli')
    ],
    'file_structure' => [],
    'database_test' => []
];

try {
    // Test file structure
    $configPath = '../config/config.php';
    $dbPath = '../config/database.php';
    $authPath = '../includes/auth.php';
    
    $response['file_structure'] = [
        'config_exists' => file_exists($configPath),
        'database_config_exists' => file_exists($dbPath),
        'auth_exists' => file_exists($authPath),
        'config_readable' => is_readable($configPath),
        'database_readable' => is_readable($dbPath)
    ];
    
    // Test database connection
    if (file_exists($dbPath)) {
        require_once $dbPath;
        
        if (class_exists('Database')) {
            $response['database_test']['class_exists'] = true;
            
            try {
                $database = new Database();
                $response['database_test']['instance_created'] = true;
                
                $conn = $database->getConnection();
                $response['database_test']['connection_successful'] = true;
                $response['database_test']['connection_type'] = get_class($conn);
                
                // Test database operations
                try {
                    // Check if tables exist
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $response['database_test']['tables_found'] = $tables;
                    $response['database_test']['table_count'] = count($tables);
                    
                    // Test users table specifically
                    if (in_array('users', $tables)) {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                        $result = $stmt->fetch();
                        $response['database_test']['users_count'] = $result['count'];
                        
                        // Get sample user data (excluding passwords)
                        $stmt = $conn->query("SELECT id, username, email, full_name, user_type, status, created_at FROM users LIMIT 3");
                        $users = $stmt->fetchAll();
                        $response['database_test']['sample_users'] = $users;
                    }
                    
                    // Test documents table
                    if (in_array('documents', $tables)) {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM documents");
                        $result = $stmt->fetch();
                        $response['database_test']['documents_count'] = $result['count'];
                    }
                    
                    // Test activity_logs table
                    if (in_array('activity_logs', $tables)) {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_logs");
                        $result = $stmt->fetch();
                        $response['database_test']['activity_logs_count'] = $result['count'];
                    }
                    
                    $response['database_test']['queries_successful'] = true;
                    
                } catch (PDOException $e) {
                    $response['database_test']['query_error'] = $e->getMessage();
                    $response['database_test']['queries_successful'] = false;
                }
                
            } catch (PDOException $e) {
                $response['database_test']['connection_successful'] = false;
                $response['database_test']['connection_error'] = $e->getMessage();
                $response['database_test']['error_code'] = $e->getCode();
            }
        } else {
            $response['database_test']['class_exists'] = false;
            $response['database_test']['error'] = 'Database class not found';
        }
    } else {
        $response['database_test']['file_exists'] = false;
    }
    
    // Overall status
    $response['overall_status'] = [
        'ready' => $response['database_test']['connection_successful'] ?? false,
        'issues' => []
    ];
    
    if (!$response['php_extensions']['pdo']) {
        $response['overall_status']['issues'][] = 'PDO extension not available';
    }
    if (!$response['php_extensions']['pdo_mysql']) {
        $response['overall_status']['issues'][] = 'PDO MySQL extension not available';
    }
    if (!($response['database_test']['connection_successful'] ?? false)) {
        $response['overall_status']['issues'][] = 'Database connection failed';
    }
    if (empty($response['database_test']['tables_found'] ?? [])) {
        $response['overall_status']['issues'][] = 'No tables found in database';
    }
    
} catch (Exception $e) {
    $response['critical_error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>