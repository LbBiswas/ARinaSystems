<?php
/**
 * SMTP Email Test for ARina Systems
 * Tests both contact and schedule email functionality with SMTP
 */

// Load configuration and SMTP
$config = require_once 'contact_config.php';
require_once 'simple_smtp.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Deliverability Test - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; background: #d4edda; color: #155724; }
        .error { border-left-color: #dc3545; background: #f8d7da; color: #721c24; }
        .warning { border-left-color: #ffc107; background: #fff3cd; color: #856404; }
        .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn.success { background: #28a745; }
        .btn.danger { background: #dc3545; }
        .config-info { background: #e9ecef; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f1f3f4; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Deliverability Test</h1>
            <p>Testing SMTP Configuration for Contact & Schedule Forms</p>
        </div>

        <?php
        // Display current configuration
        echo '<div class="section">';
        echo '<h2>📋 Current Configuration</h2>';
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
        echo '<tr><td>SMTP Enabled</td><td>' . ($config['mail_method']['use_smtp'] ? 'Yes' : 'No') . '</td>';
        echo '<td>' . ($config['mail_method']['use_smtp'] ? '✅ Good' : '⚠️ Using PHP mail()') . '</td></tr>';
        echo '<tr><td>SMTP Host</td><td>' . htmlspecialchars($config['mail_method']['smtp']['host']) . '</td><td>✅</td></tr>';
        echo '<tr><td>SMTP Port</td><td>' . $config['mail_method']['smtp']['port'] . '</td><td>✅</td></tr>';
        echo '<tr><td>Encryption</td><td>' . $config['mail_method']['smtp']['encryption'] . '</td><td>✅</td></tr>';
        echo '<tr><td>Username</td><td>' . htmlspecialchars($config['mail_method']['smtp']['username']) . '</td><td>✅</td></tr>';
        echo '<tr><td>From Email</td><td>' . htmlspecialchars($config['email']['from_email']) . '</td><td>✅</td></tr>';
        echo '<tr><td>To Email</td><td>' . htmlspecialchars($config['email']['to_email']) . '</td><td>✅</td></tr>';
        echo '</table>';
        echo '</div>';

        // Test SMTP connection if requested
        if (isset($_POST['test_connection'])) {
            echo '<div class="section">';
            echo '<h2>🔧 SMTP Connection Test</h2>';
            
            $smtp = $config['mail_method']['smtp'];
            $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 10);
            
            if ($socket) {
                echo '<div class="success">✅ Successfully connected to ' . $smtp['host'] . ':' . $smtp['port'] . '</div>';
                $response = fgets($socket, 515);
                echo '<p><strong>Server Response:</strong> ' . htmlspecialchars($response) . '</p>';
                fclose($socket);
            } else {
                echo '<div class="error">❌ Failed to connect to SMTP server<br>';
                echo '<strong>Error:</strong> ' . htmlspecialchars($errstr) . ' (' . $errno . ')</div>';
            }
            echo '</div>';
        }

        // Send test email if requested
        if (isset($_POST['send_test_contact'])) {
            echo '<div class="section">';
            echo '<h2>📧 Contact Form Test Email</h2>';
            
            $testData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+1-555-0123',
                'message' => 'This is a test email from the contact form to verify SMTP delivery and ensure emails reach the inbox instead of spam folder.'
            ];
            
            $to = $config['email']['to_email'];
            $subject = 'TEST: Contact Form Submission - ' . $config['email']['site_name'];
            $htmlBody = '<h2>Contact Form Test Email</h2><p>This email was sent to test SMTP configuration.</p><p><strong>Name:</strong> ' . $testData['name'] . '</p><p><strong>Email:</strong> ' . $testData['email'] . '</p><p><strong>Message:</strong> ' . $testData['message'] . '</p><p><em>Sent at: ' . date('Y-m-d H:i:s T') . '</em></p>';
            $replyTo = $testData['name'] . ' <' . $testData['email'] . '>';
            
            $result = sendSMTPEmail($config, $to, $subject, $htmlBody, $replyTo);
            
            if ($result) {
                echo '<div class="success">✅ Test contact email sent successfully!</div>';
                echo '<p><strong>To:</strong> ' . htmlspecialchars($to) . '</p>';
                echo '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>';
                echo '<p>Check your inbox at <strong>' . htmlspecialchars($to) . '</strong></p>';
            } else {
                echo '<div class="error">❌ Failed to send test contact email</div>';
                echo '<p>Check your SMTP configuration and try again.</p>';
            }
            echo '</div>';
        }

        // Send test schedule email if requested
        if (isset($_POST['send_test_schedule'])) {
            echo '<div class="section">';
            echo '<h2>📅 Schedule Form Test Email</h2>';
            
            $testData = [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1-555-0123',
                'call_time' => date('Y-m-d\TH:i', strtotime('+1 day')),
                'message' => 'This is a test consultation request to verify schedule form SMTP delivery.'
            ];
            
            $to = $config['email']['to_email'];
            $subject = 'TEST: New Consultation Request - ' . $config['email']['site_name'];
            $htmlBody = '<h2>Schedule Call Test Email</h2><p>This email was sent to test schedule form SMTP configuration.</p><p><strong>Name:</strong> ' . $testData['first_name'] . ' ' . $testData['last_name'] . '</p><p><strong>Email:</strong> ' . $testData['email'] . '</p><p><strong>Call Time:</strong> ' . $testData['call_time'] . '</p><p><strong>Message:</strong> ' . $testData['message'] . '</p><p><em>Sent at: ' . date('Y-m-d H:i:s T') . '</em></p>';
            $replyTo = $testData['first_name'] . ' ' . $testData['last_name'] . ' <' . $testData['email'] . '>';
            
            $result = sendSMTPEmail($config, $to, $subject, $htmlBody, $replyTo);
            
            if ($result) {
                echo '<div class="success">✅ Test schedule email sent successfully!</div>';
                echo '<p><strong>To:</strong> ' . htmlspecialchars($to) . '</p>';
                echo '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>';
                echo '<p>Check your inbox at <strong>' . htmlspecialchars($to) . '</strong></p>';
            } else {
                echo '<div class="error">❌ Failed to send test schedule email</div>';
                echo '<p>Check your SMTP configuration and try again.</p>';
            }
            echo '</div>';
        }
        ?>

        <div class="section">
            <h2>🧪 Run Tests</h2>
            <p>Click the buttons below to test your SMTP configuration:</p>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="test_connection" class="btn">🔧 Test SMTP Connection</button>
            </form>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="send_test_contact" class="btn success">📧 Send Contact Test Email</button>
            </form>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="send_test_schedule" class="btn success">📅 Send Schedule Test Email</button>
            </form>
        </div>

        <div class="section">
            <h2>✅ Expected Results</h2>
            <ul>
                <li><strong>SMTP Connection:</strong> Should connect successfully to smtp.hostinger.com:587</li>
                <li><strong>Test Emails:</strong> Should arrive in your inbox (not spam) within 1-2 minutes</li>
                <li><strong>Authentication:</strong> Emails should show proper sender authentication</li>
                <li><strong>Reply-To:</strong> Should be set correctly for easy responses</li>
            </ul>
        </div>

        <div class="section warning">
            <h2>⚠️ Important Notes</h2>
            <ul>
                <li><strong>SMTP is now enabled</strong> in your configuration</li>
                <li><strong>Fixed typo</strong> in SMTP username (removed extra .com)</li>
                <li><strong>Both forms updated</strong> to use authenticated SMTP</li>
                <li><strong>Fallback protection</strong> - if SMTP fails, uses regular mail()</li>
                <li><strong>Delete this test file</strong> after confirming everything works</li>
            </ul>
        </div>

        <div class="section">
            <h2>🔗 Test Your Forms</h2>
            <a href="contact.html" class="btn">Test Contact Form</a>
            <a href="schedule-call.html" class="btn">Test Schedule Form</a>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn">Refresh Page</a>
        </div>
    </div>
</body>
</html>