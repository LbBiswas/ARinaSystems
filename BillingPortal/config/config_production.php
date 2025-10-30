<?php
// config/config_production.php - Production configuration
session_start();

// Production environment settings
ini_set('display_errors', 0);  // Hide errors in production
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Define constants - UPDATE THESE FOR YOUR HOSTING
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/billing');  // Adjust path as needed
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('LOG_PATH', ROOT_PATH . '/logs/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Create necessary directories
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Set error log location
ini_set('error_log', LOG_PATH . 'error.log');

// Include database connection
require_once 'database.php';

// Production security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);    // Requires HTTPS
ini_set('session.use_strict_mode', 1);

// Utility functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer';
}

function redirect($url) {
    // Ensure HTTPS in production
    if (!headers_sent()) {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https://';
        } else {
            $protocol = 'https://'; // Force HTTPS
        }
        $host = $_SERVER['HTTP_HOST'];
        header("Location: {$protocol}{$host}/{$url}");
        exit();
    }
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            return '<i class="fas fa-file-pdf"></i>';
        case 'doc':
        case 'docx':
            return '<i class="fas fa-file-word"></i>';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return '<i class="fas fa-file-image"></i>';
        default:
            return '<i class="fas fa-file"></i>';
    }
}

// Enhanced CSRF protection for production
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting for production
function rateLimitCheck($action, $limit = 5, $timeWindow = 300) {
    $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = ['count' => 1, 'reset' => $now + $timeWindow];
        return true;
    }
    
    if ($now > $_SESSION['rate_limits'][$key]['reset']) {
        $_SESSION['rate_limits'][$key] = ['count' => 1, 'reset' => $now + $timeWindow];
        return true;
    }
    
    if ($_SESSION['rate_limits'][$key]['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limits'][$key]['count']++;
    return true;
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Log security events
function logSecurityEvent($event, $details = '') {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'event' => $event,
        'details' => $details,
        'user_id' => $_SESSION['user_id'] ?? 'anonymous'
    ];
    
    error_log("SECURITY: " . json_encode($logEntry), 3, LOG_PATH . 'security.log');
}

/*
HOSTINGER DEPLOYMENT NOTES:

1. Update ROOT_PATH to match your hosting structure:
   - If in root: define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
   - If in subfolder: define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/billing');
   - If subdomain: define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

2. Ensure HTTPS is configured on Hostinger:
   - Enable SSL in hPanel
   - Force HTTPS redirect

3. Set proper file permissions:
   - uploads/: 755
   - logs/: 755  
   - config files: 644

4. Monitor logs regularly:
   - Check logs/error.log for PHP errors
   - Check logs/security.log for security events
*/
?>