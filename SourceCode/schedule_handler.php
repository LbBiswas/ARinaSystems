<?php
/**
 * Schedule Call Form Handler for ARina Systems
 * Handles consultation booking form submissions
 * Sends formatted emails and provides JSON response for AJAX requests
 */

// Load configuration (reusing contact config with schedule-specific settings)
$config = require_once 'contact_config.php';
require_once 'enhanced_smtp.php';

// Schedule call specific configuration
$scheduleConfig = [
    'email' => [
        'to_email' => $config['email']['to_email'],
        'from_email' => $config['email']['from_email'],
        'site_name' => $config['email']['site_name'],
        'subject_prefix' => 'New Consultation Request',
    ],
    'validation' => [
        'name_min_length' => 2,
        'phone_min_length' => 7,
        'required_fields' => ['first_name', 'last_name', 'phone', 'email', 'call_time'],
        'optional_fields' => ['company', 'website', 'message', 'country_code'],
    ],
    'security' => [
        'rate_limit' => 10,                                // Max submissions per IP per hour (increased for testing)
        'allowed_origins' => $config['security']['allowed_origins'],
        'honeypot_field' => 'honeypot',                    // Different honeypot field for schedule form
        'enable_captcha' => false,
        'captcha_secret' => '',
    ],
    'logging' => $config['logging'],
    'messages' => [
        'success' => 'Thank you for scheduling a consultation! We will contact you at the specified time to confirm the appointment.',
        'error_general' => 'Sorry, there was an error processing your consultation request. Please try again later.',
        'error_rate_limit' => 'Too many booking requests. Please wait before submitting again.',
        'error_validation' => 'Please fix the validation errors and try again.',
        'error_spam' => 'Your request appears to contain spam content.',
        'error_past_time' => 'Please select a future date and time for your consultation.',
    ]
];

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
    return preg_match('/^[0-9\s\-\(\)]{7,20}$/', $phone);
}

function validateDateTime($datetime) {
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $datetime);
    if (!$date) {
        return false;
    }
    
    // Check if the date is in the future
    $now = new DateTime();
    return $date > $now;
}

function validateWebsite($website) {
    if (empty($website)) {
        return true; // Optional field
    }
    
    // Add protocol if missing
    if (!preg_match('#^https?://#', $website)) {
        $website = 'http://' . $website;
    }
    
    return filter_var($website, FILTER_VALIDATE_URL);
}

/**
 * Rate Limiting Function (reusing from contact form)
 */
function checkRateLimit($ip, $limit = 3) { // Lower limit for scheduling
    $logFile = 'schedule_rate_limit.log';
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
 * Spam Detection for Schedule Forms - Improved to reduce false positives
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
    
    $suspiciousContent = strtolower(
        $formData['message'] . ' ' . 
        $formData['first_name'] . ' ' . 
        $formData['last_name'] . ' ' .
        ($formData['company'] ?? '')
    );
    
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
    
    // Check honeypot field (website field can be used as honeypot alternative)
    if (!empty($formData['honeypot'])) {
        return true;
    }
    
    // Higher threshold to reduce false positives
    return $spamScore > 8;
}

/**
 * Email Sending Function for Schedule Calls
 */
function sendScheduleEmail($config, $formData) {
    $to = $config['email']['to_email'];
    $subject = 'Consultation Booking: ' . htmlspecialchars($formData['first_name'] . ' ' . $formData['last_name']) . ' - ARina Systems';
    
    // Create professional email body
    $message = createProfessionalScheduleEmailBody($config, $formData);
    
    // Set reply-to
    $replyTo = $formData['first_name'] . ' ' . $formData['last_name'] . ' <' . $formData['email'] . '>';
    
    // Send via enhanced SMTP or regular mail
    return sendAuthenticatedSMTPEmail($config, $to, $subject, $message, $replyTo);
}

function createProfessionalScheduleEmailBody($config, $formData) {
    // Format the call time nicely
    $callTime = DateTime::createFromFormat('Y-m-d\TH:i', $formData['call_time']);
    $formattedCallTime = $callTime ? $callTime->format('l, F j, Y \a\t g:i A') . ' (UTC)' : $formData['call_time'];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Professional Consultation Request</title>
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
                max-width: 650px; 
                margin: 20px auto; 
                background: #ffffff; 
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
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
            .appointment-section {
                background: #d1ecf1;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 25px;
                border-left: 4px solid #17a2b8;
            }
            .contact-section {
                background: #d4edda;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 25px;
                border-left: 4px solid #28a745;
            }
            .details-section {
                background: #fff3cd;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 25px;
                border-left: 4px solid #ffc107;
            }
            .field { 
                margin-bottom: 15px; 
                display: flex;
                flex-wrap: wrap;
            }
            .field:last-child {
                margin-bottom: 0;
            }
            .label { 
                font-weight: 600; 
                color: #495057; 
                margin-bottom: 5px; 
                min-width: 120px;
                font-size: 14px;
            }
            .value { 
                color: #212529;
                font-size: 16px;
                word-wrap: break-word;
                flex: 1;
            }
            .call-time {
                font-size: 20px;
                font-weight: 600;
                color: #17a2b8;
                text-align: center;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 6px;
                border: 2px solid #17a2b8;
                margin: 20px 0;
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
                .field { flex-direction: column; }
                .label { min-width: auto; margin-bottom: 3px; }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>📅 Professional Consultation Request</h1>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">New Client Appointment Booking</p>
            </div>
            
            <div class="content">
                <div class="appointment-section">
                    <h3 style="margin-top: 0; color: #17a2b8;">🕒 Requested Appointment Time</h3>
                    <div class="call-time">' . $formattedCallTime . '</div>
                </div>
                
                <div class="contact-section">
                    <h3 style="margin-top: 0; color: #28a745;">👤 Client Information</h3>
                    
                    <div class="field">
                        <div class="label">Name:</div>
                        <div class="value">' . htmlspecialchars($formData['first_name'] . ' ' . $formData['last_name']) . '</div>
                    </div>
                    
                    <div class="field">
                        <div class="label">Email:</div>
                        <div class="value"><a href="mailto:' . htmlspecialchars($formData['email']) . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($formData['email']) . '</a></div>
                    </div>
                    
                    <div class="field">
                        <div class="label">Phone:</div>
                        <div class="value">';
                        
    if (!empty($formData['country_code'])) {
        $html .= htmlspecialchars($formData['country_code']) . ' ';
    }
    
    $html .= '<a href="tel:' . htmlspecialchars($formData['phone']) . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($formData['phone']) . '</a></div>
                    </div>';
                    
    if (!empty($formData['company'])) {
        $html .= '
                    <div class="field">
                        <div class="label">Company:</div>
                        <div class="value">' . htmlspecialchars($formData['company']) . '</div>
                    </div>';
    }
    
    if (!empty($formData['website'])) {
        $html .= '
                    <div class="field">
                        <div class="label">Website:</div>
                        <div class="value"><a href="' . htmlspecialchars($formData['website']) . '" target="_blank" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($formData['website']) . '</a></div>
                    </div>';
    }
    
    $html .= '
                </div>';
                
    if (!empty($formData['message'])) {
        $html .= '
                <div class="details-section">
                    <h3 style="margin-top: 0; color: #856404;">💬 Additional Information</h3>
                    <div class="value" style="line-height: 1.8;">' . nl2br(htmlspecialchars($formData['message'])) . '</div>
                </div>';
    }
    
    $html .= '
                <div class="meta-info">
                    <strong>📊 Booking Details:</strong><br>
                    <strong>Submitted:</strong> ' . date('F j, Y \a\t g:i A T') . '<br>
                    <strong>Source:</strong> Professional Consultation Form<br>
                    <strong>IP Address:</strong> ' . htmlspecialchars($formData['ip']) . '<br>
                    <strong>Status:</strong> Pending Confirmation
                </div>
            </div>
            
            <div class="footer">
                <p><strong>ARina Systems</strong> - Professional Web Development & Digital Solutions</p>
                <p>⚠️ <strong>Action Required:</strong> Please confirm this appointment by responding to this email.</p>
                <p>This consultation request was submitted through your professional website.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

function createScheduleEmailBody($config, $formData) {
    // Format the call time nicely
    $callTime = DateTime::createFromFormat('Y-m-d\TH:i', $formData['call_time']);
    $formattedCallTime = $callTime ? $callTime->format('l, F j, Y \a\t g:i A') . ' UTC' : $formData['call_time'];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Segoe UI", Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 650px; margin: 0 auto; background: #ffffff; }
            .header { 
                background: linear-gradient(135deg, #13b77a 0%, #1ccfcf 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .content { padding: 30px 20px; background: #f9f9f9; }
            .consultation-info {
                background: #e8f5f1;
                border: 2px solid #13b77a;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
            }
            .field { margin-bottom: 20px; }
            .field-group { display: flex; gap: 20px; margin-bottom: 20px; }
            .field-group .field { flex: 1; margin-bottom: 0; }
            .label { font-weight: bold; color: #555; margin-bottom: 8px; display: block; }
            .value { 
                background: white; 
                padding: 15px; 
                border-left: 4px solid #13b77a; 
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .call-time {
                font-size: 1.3em;
                font-weight: bold;
                color: #13b77a;
                background: white;
                padding: 15px;
                border-radius: 8px;
                border: 2px solid #13b77a;
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
            .priority { 
                background: #fff3cd; 
                border: 1px solid #ffeaa7; 
                color: #856404; 
                padding: 10px; 
                border-radius: 5px; 
                margin: 15px 0; 
                text-align: center;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">📞 ' . htmlspecialchars($config['email']['site_name']) . '</div>
                <p>New Consultation Request</p>
            </div>
            
            <div class="priority">
                🚨 HIGH PRIORITY: Client requesting consultation call
            </div>
            
            <div class="content">
                <div class="consultation-info">
                    <h3 style="margin: 0 0 15px 0; color: #13b77a;">📅 Scheduled Call Details</h3>
                    <div class="call-time">' . htmlspecialchars($formattedCallTime) . '</div>
                </div>
                
                <div class="field-group">
                    <div class="field">
                        <div class="label">First Name:</div>
                        <div class="value">' . htmlspecialchars($formData['first_name']) . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Last Name:</div>
                        <div class="value">' . htmlspecialchars($formData['last_name']) . '</div>
                    </div>
                </div>
                
                <div class="field-group">
                    <div class="field">
                        <div class="label">Email Address:</div>
                        <div class="value"><a href="mailto:' . htmlspecialchars($formData['email']) . '">' . htmlspecialchars($formData['email']) . '</a></div>
                    </div>
                    <div class="field">
                        <div class="label">Phone Number:</div>
                        <div class="value"><a href="tel:' . htmlspecialchars($formData['country_code'] . $formData['phone']) . '">' . htmlspecialchars($formData['country_code'] . ' ' . $formData['phone']) . '</a></div>
                    </div>
                </div>';
                
    if (!empty($formData['company'])) {
        $html .= '
                <div class="field">
                    <div class="label">Company:</div>
                    <div class="value">' . htmlspecialchars($formData['company']) . '</div>
                </div>';
    }
    
    if (!empty($formData['website'])) {
        $website = $formData['website'];
        if (!preg_match('#^https?://#', $website)) {
            $website = 'http://' . $website;
        }
        $html .= '
                <div class="field">
                    <div class="label">Website:</div>
                    <div class="value"><a href="' . htmlspecialchars($website) . '" target="_blank">' . htmlspecialchars($formData['website']) . '</a></div>
                </div>';
    }
    
    if (!empty($formData['message'])) {
        $html .= '
                <div class="field">
                    <div class="label">Discussion Topics:</div>
                    <div class="value">' . nl2br(htmlspecialchars($formData['message'])) . '</div>
                </div>';
    }
    
    $html .= '
                <div class="meta-info">
                    <strong>Submission Details:</strong><br>
                    Request Date: ' . date('Y-m-d H:i:s') . '<br>
                    IP Address: ' . htmlspecialchars($formData['ip']) . '<br>
                    User Agent: ' . htmlspecialchars(substr($formData['user_agent'], 0, 100)) . '
                </div>
            </div>
            <div class="footer">
                <p><strong>Next Steps:</strong></p>
                <p>1. Review the consultation request details above</p>
                <p>2. Contact the client to confirm the appointment</p>
                <p>3. Send calendar invitation if confirmed</p>
                <br>
                <p>This email was sent automatically from the ' . htmlspecialchars($config['email']['site_name']) . ' consultation booking system.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Logging Function for Schedule Calls
 */
function logScheduleSubmission($config, $type, $data, $userIP) {
    if (!$config['logging']['enable_logging']) {
        return;
    }
    
    $logFile = "schedule_{$type}.log";
    $timestamp = date('Y-m-d H:i:s');
    $email = isset($data['email']) ? $data['email'] : 'unknown';
    $callTime = isset($data['call_time']) ? $data['call_time'] : 'unknown';
    
    $logEntry = "{$timestamp} - {$type} - {$email} - Call: {$callTime} - {$userIP}\n";
    
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
    
    // Rate limiting check (stricter for consultations)
    if (!checkRateLimit($userIP, 3)) {
        echo json_encode([
            'success' => false,
            'message' => $scheduleConfig['messages']['error_rate_limit']
        ]);
        exit;
    }
    
    // Validate and sanitize form data
    $formData = [
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'country_code' => sanitizeInput($_POST['country_code'] ?? ''),
        'company' => sanitizeInput($_POST['company'] ?? ''),
        'website' => sanitizeInput($_POST['website'] ?? ''),
        'call_time' => sanitizeInput($_POST['call_time'] ?? ''),
        'message' => sanitizeInput($_POST['message'] ?? ''),
        'honeypot' => $_POST['_honeypot'] ?? '',
        'ip' => $userIP,
        'user_agent' => $userAgent
    ];
    
    // Validation
    $errors = [];
    $validation = $scheduleConfig['validation'];
    
    // Check required fields
    foreach ($validation['required_fields'] as $field) {
        if (empty($formData[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Validate names
    foreach (['first_name', 'last_name'] as $nameField) {
        if (!empty($formData[$nameField])) {
            $nameLen = strlen($formData[$nameField]);
            if ($nameLen < $validation['name_min_length']) {
                $errors[] = ucfirst(str_replace('_', ' ', $nameField)) . ' must be at least ' . $validation['name_min_length'] . ' characters long.';
            }
        }
    }
    
    // Validate email
    if (!empty($formData['email']) && !validateEmail($formData['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate phone
    if (!empty($formData['phone'])) {
        if (!validatePhone($formData['phone'])) {
            $errors[] = 'Please enter a valid phone number.';
        }
        if (strlen($formData['phone']) < $validation['phone_min_length']) {
            $errors[] = 'Phone number must be at least ' . $validation['phone_min_length'] . ' digits long.';
        }
    }
    
    // Validate call time
    if (!empty($formData['call_time']) && !validateDateTime($formData['call_time'])) {
        $errors[] = $scheduleConfig['messages']['error_past_time'];
    }
    
    // Validate website (if provided)
    if (!empty($formData['website']) && !validateWebsite($formData['website'])) {
        $errors[] = 'Please enter a valid website URL.';
    }
    
    // Check for spam
    if (detectSpam($formData, $scheduleConfig)) {
        logScheduleSubmission($scheduleConfig, 'spam', $formData, $userIP);
        echo json_encode([
            'success' => false,
            'message' => $scheduleConfig['messages']['error_spam']
        ]);
        exit;
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => $scheduleConfig['messages']['error_validation'] . ' ' . implode(' ', $errors)
        ]);
        exit;
    }
    
    // Send email
    if (sendScheduleEmail($scheduleConfig, $formData)) {
        // Log successful submission
        logScheduleSubmission($scheduleConfig, 'success', $formData, $userIP);
        
        echo json_encode([
            'success' => true,
            'message' => $scheduleConfig['messages']['success']
        ]);
    } else {
        // Log failed submission
        logScheduleSubmission($scheduleConfig, 'failed', $formData, $userIP);
        
        echo json_encode([
            'success' => false,
            'message' => $scheduleConfig['messages']['error_general']
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    $errorData = ['error' => $e->getMessage()];
    logScheduleSubmission($scheduleConfig, 'exception', $errorData, $userIP ?? 'unknown');
    
    echo json_encode([
        'success' => false,
        'message' => $scheduleConfig['messages']['error_general']
    ]);
}
?>