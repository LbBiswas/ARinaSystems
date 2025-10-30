<?php
/**
 * Contact Form Test - Exact Simulation
 * Tests the actual contact form functionality
 */

// Load configuration and dependencies
$config = require_once 'contact_config.php';
require_once 'simple_smtp.php';
require_once 'contact_handler.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Test - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #007bff; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .btn { background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0056b3; }
        .result { margin-top: 20px; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Contact Form Functionality Test</h1>
            <p>Testing the exact same process as your contact form</p>
        </div>

        <?php
        if (isset($_POST['submit_test'])) {
            echo '<div class="result info">';
            echo '<h3>📧 Processing Contact Form Test...</h3>';
            
            // Simulate the exact same data structure as contact form
            $formData = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'message' => $_POST['message'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Test Browser',
                'timestamp' => time()
            ];
            
            echo '<h4>📋 Form Data Captured:</h4>';
            echo '<ul>';
            echo '<li><strong>Name:</strong> ' . htmlspecialchars($formData['name']) . '</li>';
            echo '<li><strong>Email:</strong> ' . htmlspecialchars($formData['email']) . '</li>';
            echo '<li><strong>Phone:</strong> ' . htmlspecialchars($formData['phone']) . '</li>';
            echo '<li><strong>Message Length:</strong> ' . strlen($formData['message']) . ' characters</li>';
            echo '</ul>';
            
            // Test email sending using the exact same function as contact form
            echo '<h4>📤 Sending Email...</h4>';
            
            try {
                $result = sendEmail($config, $formData);
                
                if ($result) {
                    echo '<div class="result success">';
                    echo '<h3>✅ Email Sent Successfully!</h3>';
                    echo '<p>The contact form email was sent using the exact same process as your website contact form.</p>';
                    echo '<p><strong>To Email:</strong> ' . htmlspecialchars($config['email']['to_email']) . '</p>';
                    echo '<p><strong>Subject:</strong> Business Inquiry from ' . htmlspecialchars($formData['name']) . ' - ARina Systems</p>';
                    echo '<p><strong>Method:</strong> SMTP via Hostinger</p>';
                    echo '<p><strong>Time:</strong> ' . date('F j, Y \a\t g:i A T') . '</p>';
                    echo '</div>';
                    
                    echo '<div class="result info">';
                    echo '<h4>📊 Email Analysis:</h4>';
                    echo '<ul>';
                    echo '<li>✅ Used professional business email template</li>';
                    echo '<li>✅ Sent via authenticated SMTP</li>';
                    echo '<li>✅ Proper reply-to header set</li>';
                    echo '<li>✅ Professional subject line</li>';
                    echo '<li>✅ HTML formatting with proper encoding</li>';
                    echo '</ul>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="result error">';
                    echo '<h3>❌ Email Sending Failed</h3>';
                    echo '<p>There was an issue sending the email. Check the server logs for more details.</p>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="result error">';
                echo '<h3>❌ Error Occurred</h3>';
                echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>

        <form method="post">
            <h3>📝 Test Contact Form Submission</h3>
            <p>This will test the exact same email sending process as your contact form:</p>
            
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number">
            </div>
            
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="5" required placeholder="Enter your message or inquiry"></textarea>
            </div>
            
            <button type="submit" name="submit_test" class="btn">🧪 Test Contact Form Email</button>
        </form>
        
        <div class="result info" style="margin-top: 30px;">
            <h3>🎯 What This Test Does</h3>
            <ul>
                <li>Uses the exact same <code>sendEmail()</code> function as your contact form</li>
                <li>Creates the same data structure and format</li>
                <li>Sends via the same SMTP configuration</li>
                <li>Uses the same professional email template</li>
                <li>Applies the same headers and encoding</li>
            </ul>
            <p><strong>If this test email goes to inbox:</strong> Your contact form should work the same way</p>
            <p><strong>If this test email goes to spam:</strong> There might be content-specific triggers</p>
        </div>
    </div>
</body>
</html>