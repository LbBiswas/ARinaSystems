<?php
/**
 * Email Debug Script for ARina Systems
 * Tests email functionality step by step
 */

// Load configuration
$config = require_once 'contact_config.php';
require_once 'enhanced_smtp.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Debug - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; background: #d4edda; color: #155724; }
        .error { border-left-color: #dc3545; background: #f8d7da; color: #721c24; }
        .warning { border-left-color: #ffc107; background: #fff3cd; color: #856404; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; color: #0c5460; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 14px; margin: 10px 0; border: 1px solid #e9ecef; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background: #f1f3f4; }
        .test-form { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 15px 0; }
        .test-form input, .test-form textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .test-form button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .test-form button:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Email System Debug</h1>
            <p>Comprehensive email testing and diagnostics</p>
        </div>

<?php

// Step 1: Check configuration
echo '<div class="section">';
echo '<h2>📋 Step 1: Configuration Check</h2>';

echo '<table>';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';

// Check SMTP settings
$smtp = $config['mail_method']['smtp'];
echo '<tr><td>SMTP Enabled</td><td>' . ($config['mail_method']['use_smtp'] ? 'Yes' : 'No') . '</td><td>' . ($config['mail_method']['use_smtp'] ? '✅' : '❌') . '</td></tr>';
echo '<tr><td>SMTP Host</td><td>' . $smtp['host'] . '</td><td>' . (!empty($smtp['host']) ? '✅' : '❌') . '</td></tr>';
echo '<tr><td>SMTP Port</td><td>' . $smtp['port'] . '</td><td>' . (in_array($smtp['port'], [25, 465, 587]) ? '✅' : '⚠️') . '</td></tr>';
echo '<tr><td>SMTP Username</td><td>' . $smtp['username'] . '</td><td>' . (!empty($smtp['username']) ? '✅' : '❌') . '</td></tr>';
echo '<tr><td>SMTP Password</td><td>' . (!empty($smtp['password']) ? 'Set (****)' : 'Not Set') . '</td><td>' . (!empty($smtp['password']) ? '✅' : '❌') . '</td></tr>';
echo '<tr><td>Encryption</td><td>' . $smtp['encryption'] . '</td><td>' . (in_array($smtp['encryption'], ['tls', 'ssl']) ? '✅' : '❌') . '</td></tr>';
echo '<tr><td>From Email</td><td>' . $config['email']['from_email'] . '</td><td>' . (filter_var($config['email']['from_email'], FILTER_VALIDATE_EMAIL) ? '✅' : '❌') . '</td></tr>';
echo '<tr><td>To Email</td><td>' . $config['email']['to_email'] . '</td><td>' . (filter_var($config['email']['to_email'], FILTER_VALIDATE_EMAIL) ? '✅' : '❌') . '</td></tr>';

echo '</table>';
echo '</div>';

// Step 2: PHP Extensions Check
echo '<div class="section">';
echo '<h2>🔧 Step 2: PHP Extensions Check</h2>';

$extensions = ['openssl', 'sockets', 'curl'];
echo '<table>';
echo '<tr><th>Extension</th><th>Status</th><th>Required For</th></tr>';

foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo '<tr><td>' . $ext . '</td><td>' . ($loaded ? '✅ Loaded' : '❌ Missing') . '</td>';
    switch ($ext) {
        case 'openssl':
            echo '<td>TLS/SSL encryption</td>';
            break;
        case 'sockets':
            echo '<td>SMTP connections</td>';
            break;
        case 'curl':
            echo '<td>HTTP requests</td>';
            break;
    }
    echo '</tr>';
}

echo '</table>';
echo '</div>';

// Step 3: Connection Test
echo '<div class="section">';
echo '<h2>🌐 Step 3: SMTP Connection Test</h2>';

$connectionResult = testSMTPConnection($smtp);
if ($connectionResult['success']) {
    echo '<div class="success">';
    echo '<strong>✅ Connection Successful!</strong><br>';
    echo $connectionResult['message'];
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<strong>❌ Connection Failed!</strong><br>';
    echo $connectionResult['message'];
    echo '</div>';
}

echo '</div>';

// Step 4: Test Email Form
if (isset($_POST['test_email'])) {
    echo '<div class="section">';
    echo '<h2>📧 Step 4: Email Test Results</h2>';
    
    $testEmail = $_POST['test_email'];
    $testSubject = $_POST['test_subject'] ?: 'Email System Test - ' . date('Y-m-d H:i:s');
    $testMessage = $_POST['test_message'] ?: 'This is a test email from the ARina Systems email debugging system.';
    
    $htmlBody = "<html><body style='font-family: Arial, sans-serif;'>";
    $htmlBody .= "<h2>Email System Test</h2>";
    $htmlBody .= "<p><strong>Test performed:</strong> " . date('Y-m-d H:i:s') . "</p>";
    $htmlBody .= "<p><strong>Message:</strong></p>";
    $htmlBody .= "<p>" . nl2br(htmlspecialchars($testMessage)) . "</p>";
    $htmlBody .= "<hr>";
    $htmlBody .= "<p><small>This email was sent from the ARina Systems email debug system.</small></p>";
    $htmlBody .= "</body></html>";
    
    $emailResult = sendAuthenticatedSMTPEmail($config, $testEmail, $testSubject, $htmlBody);
    
    if ($emailResult) {
        echo '<div class="success">';
        echo '<strong>✅ Email Sent Successfully!</strong><br>';
        echo 'Test email sent to: ' . htmlspecialchars($testEmail) . '<br>';
        echo 'The email was processed and sent via SMTP.';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<strong>❌ Email Failed!</strong><br>';
        echo 'The SMTP email sending failed. This could be due to authentication issues, network problems, or server configuration.';
        echo '</div>';
    }
    
    echo '</div>';
}

?>

        <div class="section">
            <h2>🧪 Step 4: Send Test Email</h2>
            <div class="test-form">
                <form method="POST">
                    <label for="test_email">Test Email Address:</label>
                    <input type="email" id="test_email" name="test_email" value="<?= $config['email']['to_email'] ?>" required>
                    
                    <label for="test_subject">Subject:</label>
                    <input type="text" id="test_subject" name="test_subject" value="Email System Test - <?= date('Y-m-d H:i:s') ?>">
                    
                    <label for="test_message">Test Message:</label>
                    <textarea id="test_message" name="test_message" rows="4">This is a test email from the ARina Systems email debugging system. If you receive this, the email system is working correctly!</textarea>
                    
                    <button type="submit">Send Test Email</button>
                </form>
            </div>
        </div>

        <div class="section">
            <h2>📝 Step 5: Next Steps</h2>
            <p>If the test email fails:</p>
            <ul>
                <li>Check your Hostinger email password in contact_config.php</li>
                <li>Verify the email account exists in your Hostinger control panel</li>
                <li>Ensure your domain's DNS records are properly configured</li>
                <li>Check if your server's IP is not blacklisted</li>
                <li>Try using a different SMTP port (465 with SSL instead of 587 with TLS)</li>
            </ul>
            
            <p>If the test email succeeds but contact/schedule forms don't work:</p>
            <ul>
                <li>Check the contact_handler.php and schedule_handler.php for errors</li>
                <li>Verify the form JavaScript is submitting to the correct endpoints</li>
                <li>Check browser console for JavaScript errors</li>
                <li>Ensure the honeypot and spam detection aren't blocking legitimate submissions</li>
            </ul>
        </div>

        <div class="section info">
            <h2>📊 DNS Records Status</h2>
            <p>Based on your DNS records, the following email authentication is configured:</p>
            <ul>
                <li>✅ <strong>SPF Record:</strong> Configured with Hostinger</li>
                <li>✅ <strong>DKIM Records:</strong> Three DKIM keys configured (hostingermail-a, b, c)</li>
                <li>✅ <strong>DMARC Record:</strong> Configured with quarantine policy</li>
                <li>✅ <strong>MX Records:</strong> Pointing to Hostinger mail servers</li>
            </ul>
            <p>Your email authentication setup looks correct. If emails are still not being received, the issue might be with the PHP mail sending logic or server configuration.</p>
        </div>

    </div>
</body>
</html>

<?php

function testSMTPConnection($smtp) {
    try {
        $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 10);
        
        if (!$socket) {
            return [
                'success' => false,
                'message' => "Cannot connect to {$smtp['host']}:{$smtp['port']} - Error: $errno - $errstr"
            ];
        }
        
        $response = fgets($socket, 256);
        
        if (!$response || substr($response, 0, 3) !== '220') {
            fclose($socket);
            return [
                'success' => false,
                'message' => "SMTP server did not respond correctly. Response: " . trim($response)
            ];
        }
        
        fclose($socket);
        
        return [
            'success' => true,
            'message' => "Successfully connected to {$smtp['host']}:{$smtp['port']}. Server response: " . trim($response)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Connection test failed: " . $e->getMessage()
        ];
    }
}

?>