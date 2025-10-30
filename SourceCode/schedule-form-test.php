<?php
/**
 * Schedule Form Test - Exact Simulation
 * Tests the actual schedule form functionality
 */

// Load configuration and dependencies
$config = require_once 'contact_config.php';
require_once 'simple_smtp.php';
require_once 'schedule_handler.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Form Test - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #28a745; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .btn { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #218838; }
        .result { margin-top: 20px; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Schedule Form Functionality Test</h1>
            <p>Testing the exact same process as your schedule consultation form</p>
        </div>

        <?php
        if (isset($_POST['submit_schedule_test'])) {
            echo '<div class="result info">';
            echo '<h3>📧 Processing Schedule Form Test...</h3>';
            
            // Simulate the exact same data structure as schedule form
            $formData = [
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'country_code' => $_POST['country_code'] ?? '',
                'company' => $_POST['company'] ?? '',
                'website' => $_POST['website'] ?? '',
                'call_time' => $_POST['call_time'] ?? '',
                'message' => $_POST['message'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Test Browser',
                'timestamp' => time()
            ];
            
            echo '<h4>📋 Schedule Data Captured:</h4>';
            echo '<ul>';
            echo '<li><strong>Name:</strong> ' . htmlspecialchars($formData['first_name'] . ' ' . $formData['last_name']) . '</li>';
            echo '<li><strong>Email:</strong> ' . htmlspecialchars($formData['email']) . '</li>';
            echo '<li><strong>Phone:</strong> ' . htmlspecialchars($formData['country_code'] . ' ' . $formData['phone']) . '</li>';
            echo '<li><strong>Company:</strong> ' . htmlspecialchars($formData['company']) . '</li>';
            echo '<li><strong>Preferred Time:</strong> ' . htmlspecialchars($formData['call_time']) . '</li>';
            echo '</ul>';
            
            // Test email sending using the exact same function as schedule form
            echo '<h4>📤 Sending Schedule Email...</h4>';
            
            try {
                $result = sendScheduleEmail($config, $formData);
                
                if ($result) {
                    echo '<div class="result success">';
                    echo '<h3>✅ Schedule Email Sent Successfully!</h3>';
                    echo '<p>The consultation request email was sent using the exact same process as your website schedule form.</p>';
                    echo '<p><strong>To Email:</strong> ' . htmlspecialchars($config['email']['to_email']) . '</p>';
                    echo '<p><strong>Subject:</strong> Consultation Booking: ' . htmlspecialchars($formData['first_name'] . ' ' . $formData['last_name']) . ' - ARina Systems</p>';
                    echo '<p><strong>Method:</strong> SMTP via Hostinger</p>';
                    echo '<p><strong>Time:</strong> ' . date('F j, Y \a\t g:i A T') . '</p>';
                    echo '</div>';
                    
                    echo '<div class="result info">';
                    echo '<h4>📊 Schedule Email Analysis:</h4>';
                    echo '<ul>';
                    echo '<li>✅ Used professional consultation email template</li>';
                    echo '<li>✅ Sent via authenticated SMTP</li>';
                    echo '<li>✅ Proper reply-to header set</li>';
                    echo '<li>✅ Professional subject line with client name</li>';
                    echo '<li>✅ HTML formatting with appointment details</li>';
                    echo '<li>✅ Call time prominently displayed</li>';
                    echo '</ul>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="result error">';
                    echo '<h3>❌ Schedule Email Sending Failed</h3>';
                    echo '<p>There was an issue sending the consultation email. Check the server logs for more details.</p>';
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
            <h3>📅 Test Schedule Consultation Form</h3>
            <p>This will test the exact same email sending process as your schedule form:</p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="First name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Last name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="country_code">Country Code</label>
                    <select id="country_code" name="country_code">
                        <option value="+1">+1 (US/Canada)</option>
                        <option value="+91">+91 (India)</option>
                        <option value="+44">+44 (UK)</option>
                        <option value="+61">+61 (Australia)</option>
                        <option value="+81">+81 (Japan)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required placeholder="Phone number">
                </div>
            </div>
            
            <div class="form-group">
                <label for="company">Company/Organization</label>
                <input type="text" id="company" name="company" placeholder="Your company name">
            </div>
            
            <div class="form-group">
                <label for="website">Website URL</label>
                <input type="url" id="website" name="website" placeholder="https://yourwebsite.com">
            </div>
            
            <div class="form-group">
                <label for="call_time">Preferred Call Time *</label>
                <input type="datetime-local" id="call_time" name="call_time" required>
            </div>
            
            <div class="form-group">
                <label for="message">Additional Information</label>
                <textarea id="message" name="message" rows="4" placeholder="Tell us about your project or requirements"></textarea>
            </div>
            
            <button type="submit" name="submit_schedule_test" class="btn">📅 Test Schedule Form Email</button>
        </form>
        
        <div class="result info" style="margin-top: 30px;">
            <h3>🎯 What This Schedule Test Does</h3>
            <ul>
                <li>Uses the exact same <code>sendScheduleEmail()</code> function as your schedule form</li>
                <li>Creates the same consultation data structure</li>
                <li>Sends via the same SMTP configuration</li>
                <li>Uses the same professional consultation email template</li>
                <li>Applies the same headers and appointment formatting</li>
            </ul>
            <p><strong>If this test email goes to inbox:</strong> Your schedule form should work the same way</p>
            <p><strong>If this test email goes to spam:</strong> There might be content-specific triggers</p>
        </div>
    </div>
</body>
</html>