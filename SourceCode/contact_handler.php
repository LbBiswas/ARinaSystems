<?php
/**
 * Contact Form Handler for ARina Systems
 * Handles form submissions from the contact page
 * Uses configuration file for easy management
 */

// Load configuration
$config = require_once 'contact_config.php';
require_once 'enhanced_smtp.php';

// Enable error reporting for debugging (disable in production)
if ($config['development']['debug_mode']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST request.'
    ]);
    exit;
}

/**
 * Input Validation and Sanitization
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $phone);
}

/**
 * Rate Limiting Function
 */
function checkRateLimit($ip, $limit = 5) {
    $logFile = 'contact_rate_limit.log';
    $currentTime = time();
    $oneHourAgo = $currentTime - 3600;
    
    // Read existing logs
    $logs = [];
    if (file_exists($logFile)) {
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    // Filter logs from last hour for this IP
    $recentSubmissions = 0;
    $validLogs = [];
    
    foreach ($logs as $log) {
        $parts = explode('|', $log);
        if (count($parts) >= 2) {
            $logTime = intval($parts[0]);
            $logIP = $parts[1];
            
            if ($logTime > $oneHourAgo) {
                $validLogs[] = $log;
                if ($logIP === $ip) {
                    $recentSubmissions++;
                }
            }
        }
    }
    
    // Check if rate limit exceeded
    if ($recentSubmissions >= $limit) {
        return false;
    }
    
    // Add current submission to log
    $validLogs[] = $currentTime . '|' . $ip;
    file_put_contents($logFile, implode("\n", $validLogs));
    
    return true;
}

/**
 * Spam Detection - Improved to reduce false positives
 */
function detectSpam($formData, $config) {
    // Malicious script patterns (keep these strict)
    $maliciousPatterns = [
        '<script', 'javascript:', 'onclick=', 'onload=', 'eval(',
        'document.cookie', 'window.location', 'alert(', 'confirm(',
        'prompt(', 'document.write', 'innerHTML', 'outerHTML'
    ];
    
    // Excessive link patterns (only flag if excessive)
    $linkPatterns = ['http://', 'https://'];
    
    $suspiciousContent = strtolower($formData['message'] . ' ' . $formData['name']);
    
    $spamScore = 0;
    
    // Check for malicious patterns (high weight)
    foreach ($maliciousPatterns as $pattern) {
        if (stripos($suspiciousContent, strtolower($pattern)) !== false) {
            $spamScore += 5; // High penalty for malicious content
        }
    }
    
    // Check for excessive links (only if more than 3 links)
    $linkCount = 0;
    foreach ($linkPatterns as $pattern) {
        $linkCount += substr_count($suspiciousContent, $pattern);
    }
    if ($linkCount > 3) {
        $spamScore += 3; // Penalty for excessive links
    }
    
    // Check for suspicious patterns (lighter penalties)
    $suspiciousWords = ['viagra', 'casino', 'lottery', 'winner', 'congratulations', 'prize', 'million', 'inherit'];
    foreach ($suspiciousWords as $word) {
        if (stripos($suspiciousContent, $word) !== false) {
            $spamScore += 1;
        }
    }
    
    // Check for honeypot field (instant spam if filled)
    if (!empty($formData[$config['security']['honeypot_field']])) {
        return true;
    }
    
    // Higher threshold to reduce false positives
    return $spamScore > 8;
}

/**
 * Email Sending Function
 */
function sendEmail($config, $formData) {
    $to = $config['email']['to_email'];
    $subject = 'Business Inquiry from ' . htmlspecialchars($formData['name']) . ' - ARina Systems';
    
    // Create professional email body
    $message = createProfessionalEmailBody($config, $formData);
    
    // Set reply-to
    $replyTo = null;
    if ($config['email']['reply_to_customer']) {
        $replyTo = $formData['name'] . ' <' . $formData['email'] . '>';
    }
    
    // Send via enhanced SMTP or regular mail
    return sendAuthenticatedSMTPEmail($config, $to, $subject, $message, $replyTo);
}

function createProfessionalEmailBody($config, $formData) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Business Inquiry</title>
        <style>
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background: #f8f9fa; 
            }
            .email-container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: #ffffff; 
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 24px; 
                font-weight: 300;
            }
            .content { 
                padding: 30px 20px; 
                background: #ffffff; 
            }
            .contact-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 25px;
                border-left: 4px solid #28a745;
            }
            .message-section {
                background: #fff3cd;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 25px;
                border-left: 4px solid #ffc107;
            }
            .field { 
                margin-bottom: 15px; 
            }
            .field:last-child {
                margin-bottom: 0;
            }
            .label { 
                font-weight: 600; 
                color: #495057; 
                margin-bottom: 5px; 
                display: block; 
                font-size: 14px;
            }
            .value { 
                color: #212529;
                font-size: 16px;
                word-wrap: break-word;
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                font-size: 12px; 
                color: #6c757d;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
            }
            .meta-info {
                font-size: 12px;
                color: #6c757d;
                background: #e9ecef;
                padding: 15px;
                border-radius: 4px;
                margin-top: 20px;
            }
            @media only screen and (max-width: 600px) {
                .email-container { margin: 10px; }
                .content { padding: 20px 15px; }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>🤝 New Business Inquiry</h1>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Professional Contact Request</p>
            </div>
            
            <div class="content">
                <div class="contact-section">
                    <h3 style="margin-top: 0; color: #28a745;">📋 Contact Information</h3>
                    
                    <div class="field">
                        <div class="label">Full Name</div>
                        <div class="value">' . htmlspecialchars($formData['name']) . '</div>
                    </div>
                    
                    <div class="field">
                        <div class="label">Email Address</div>
                        <div class="value"><a href="mailto:' . htmlspecialchars($formData['email']) . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($formData['email']) . '</a></div>
                    </div>';
                
    if (!empty($formData['phone'])) {
        $html .= '
                    <div class="field">
                        <div class="label">Phone Number</div>
                        <div class="value"><a href="tel:' . htmlspecialchars($formData['phone']) . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($formData['phone']) . '</a></div>
                    </div>';
    }
    
    $html .= '
                </div>
                
                <div class="message-section">
                    <h3 style="margin-top: 0; color: #856404;">💬 Inquiry Details</h3>
                    <div class="value" style="line-height: 1.8;">' . nl2br(htmlspecialchars($formData['message'])) . '</div>
                </div>
                
                <div class="meta-info">
                    <strong>📊 Submission Details:</strong><br>
                    <strong>Date:</strong> ' . date('F j, Y \a\t g:i A T') . '<br>
                    <strong>Source:</strong> Website Contact Form<br>
                    <strong>IP:</strong> ' . htmlspecialchars($formData['ip']) . '
                </div>
            </div>
            
            <div class="footer">
                <p><strong>ARina Systems</strong> - Professional Web Development & Digital Solutions</p>
                <p>This email was automatically generated from your business website.</p>
                <p><strong>Important:</strong> To respond to this inquiry, simply reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

function createEmailBody($config, $formData) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Segoe UI", Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .content { padding: 30px 20px; background: #f9f9f9; }
            .field { margin-bottom: 20px; }
            .label { font-weight: bold; color: #555; margin-bottom: 8px; display: block; }
            .value { 
                background: white; 
                padding: 15px; 
                border-left: 4px solid #667eea; 
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                font-size: 12px; 
                color: #666; 
                background: #e9ecef;
            }
            .logo { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
            .meta-info { font-size: 11px; color: #888; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">' . htmlspecialchars($config['email']['site_name']) . '</div>
                <p>New Contact Form Submission</p>
            </div>
            <div class="content">
                <div class="field">
                    <div class="label">Full Name:</div>
                    <div class="value">' . htmlspecialchars($formData['name']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Email Address:</div>
                    <div class="value"><a href="mailto:' . htmlspecialchars($formData['email']) . '">' . htmlspecialchars($formData['email']) . '</a></div>
                </div>';
                
    if (!empty($formData['phone'])) {
        $html .= '
                <div class="field">
                    <div class="label">Phone Number:</div>
                    <div class="value"><a href="tel:' . htmlspecialchars($formData['phone']) . '">' . htmlspecialchars($formData['phone']) . '</a></div>
                </div>';
    }
    
    $html .= '
                <div class="field">
                    <div class="label">Message:</div>
                    <div class="value">' . nl2br(htmlspecialchars($formData['message'])) . '</div>
                </div>
                
                <div class="meta-info">
                    <strong>Submission Details:</strong><br>
                    Date: ' . date('Y-m-d H:i:s') . '<br>
                    IP Address: ' . htmlspecialchars($formData['ip']) . '<br>
                    User Agent: ' . htmlspecialchars($formData['user_agent']) . '
                </div>
            </div>
            <div class="footer">
                <p>This email was sent automatically from the ' . htmlspecialchars($config['email']['site_name']) . ' website contact form.</p>
                <p>To reply to this inquiry, simply respond to this email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Logging Function
 */
function logSubmission($config, $type, $data, $userIP) {
    if (!$config['logging']['enable_logging']) {
        return;
    }
    
    $logFile = "contact_{$type}.log";
    $timestamp = date('Y-m-d H:i:s');
    $email = isset($data['email']) ? $data['email'] : 'unknown';
    
    $logEntry = "{$timestamp} - {$type} - {$email} - {$userIP}\n";
    
    // Check log file size and rotate if necessary
    if (file_exists($logFile) && filesize($logFile) > $config['logging']['log_file_max_size']) {
        rename($logFile, $logFile . '.old');
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Main Processing Logic
 */
try {
    // Get visitor information
    $userIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Rate limiting check
    if (!checkRateLimit($userIP, $config['security']['rate_limit'])) {
        echo json_encode([
            'success' => false,
            'message' => $config['messages']['error_rate_limit']
        ]);
        exit;
    }
    
    // Validate and sanitize form data
    $formData = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'message' => sanitizeInput($_POST['message'] ?? ''),
        'ip' => $userIP,
        'user_agent' => $userAgent,
        $config['security']['honeypot_field'] => $_POST[$config['security']['honeypot_field']] ?? ''
    ];
    
    // Validation
    $errors = [];
    $validation = $config['validation'];
    
    // Check required fields
    foreach ($validation['required_fields'] as $field) {
        if (empty($formData[$field])) {
            $errors[] = ucfirst($field) . ' is required.';
        }
    }
    
    // Validate name
    if (!empty($formData['name'])) {
        $nameLen = strlen($formData['name']);
        if ($nameLen < $validation['name_min_length']) {
            $errors[] = 'Name must be at least ' . $validation['name_min_length'] . ' characters long.';
        } elseif ($nameLen > $validation['name_max_length']) {
            $errors[] = 'Name must be less than ' . $validation['name_max_length'] . ' characters long.';
        }
    }
    
    // Validate email
    if (!empty($formData['email']) && !validateEmail($formData['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate phone (if provided)
    if (!empty($formData['phone']) && !validatePhone($formData['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    
    // Validate message
    if (!empty($formData['message'])) {
        $messageLen = strlen($formData['message']);
        if ($messageLen < $validation['message_min_length']) {
            $errors[] = 'Message must be at least ' . $validation['message_min_length'] . ' characters long.';
        } elseif ($messageLen > $validation['message_max_length']) {
            $errors[] = 'Message must be less than ' . $validation['message_max_length'] . ' characters long.';
        }
    }
    
    // Check for spam
    if (detectSpam($formData, $config)) {
        logSubmission($config, 'spam', $formData, $userIP);
        echo json_encode([
            'success' => false,
            'message' => $config['messages']['error_spam']
        ]);
        exit;
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => $config['messages']['error_validation'] . ' ' . implode(' ', $errors)
        ]);
        exit;
    }
    
    // Send email
    if (sendEmail($config, $formData)) {
        // Log successful submission
        logSubmission($config, 'success', $formData, $userIP);
        
        echo json_encode([
            'success' => true,
            'message' => $config['messages']['success']
        ]);
    } else {
        // Log failed submission
        logSubmission($config, 'failed', $formData, $userIP);
        
        echo json_encode([
            'success' => false,
            'message' => $config['messages']['error_general']
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    $errorData = ['error' => $e->getMessage()];
    logSubmission($config, 'exception', $errorData, $userIP ?? 'unknown');
    
    echo json_encode([
        'success' => false,
        'message' => $config['messages']['error_general']
    ]);
}
?>