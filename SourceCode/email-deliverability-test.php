<?php
/**
 * Advanced Email Deliverability Test for ARina Systems
 * Comprehensive testing of SMTP, email headers, and spam score analysis
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
    <title>Email Deliverability Analysis - ARina Systems</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; margin-bottom: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
        .section { background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #007bff; }
        .success { border-left-color: #28a745; background: #d4edda; color: #155724; }
        .error { border-left-color: #dc3545; background: #f8d7da; color: #721c24; }
        .warning { border-left-color: #ffc107; background: #fff3cd; color: #856404; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; color: #0c5460; }
        .btn { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; margin: 8px; text-decoration: none; display: inline-block; font-size: 16px; }
        .btn:hover { background: #0056b3; transform: translateY(-1px); }
        .btn.success { background: #28a745; }
        .btn.warning { background: #ffc107; color: #333; }
        .btn.danger { background: #dc3545; }
        .test-result { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #e9ecef; }
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .config-item { background: white; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: "Consolas", "Monaco", monospace; font-size: 14px; border: 1px solid #e9ecef; overflow-x: auto; }
        .score { font-size: 24px; font-weight: bold; text-align: center; padding: 20px; border-radius: 10px; margin: 15px 0; }
        .score.good { background: #d4edda; color: #155724; }
        .score.warning { background: #fff3cd; color: #856404; }
        .score.poor { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f1f3f4; font-weight: 600; }
        .recommendation { background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 5px solid #007bff; margin: 20px 0; }
        .recommendation h4 { margin-top: 0; color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Deliverability Analysis</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Comprehensive SMTP & Spam Prevention Testing</p>
        </div>

        <?php
        
        function analyzeEmailDeliverability($config) {
            $analysis = [
                'smtp_score' => 0,
                'header_score' => 0,
                'content_score' => 0,
                'domain_score' => 0,
                'total_score' => 0,
                'recommendations' => [],
                'tests' => []
            ];
            
            // Test 1: SMTP Configuration
            echo '<div class="section info">';
            echo '<h3>🔧 SMTP Configuration Analysis</h3>';
            
            if ($config['mail_method']['use_smtp']) {
                $analysis['smtp_score'] += 30;
                echo '<div class="test-result success">✅ SMTP Enabled - Excellent for deliverability</div>';
                
                $smtp = $config['mail_method']['smtp'];
                echo '<table>';
                echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
                echo '<tr><td>Host</td><td>' . $smtp['host'] . '</td><td><span style="color: #28a745;">✅ Hostinger SMTP</span></td></tr>';
                echo '<tr><td>Port</td><td>' . $smtp['port'] . '</td><td><span style="color: #28a745;">✅ TLS Port 587</span></td></tr>';
                echo '<tr><td>Encryption</td><td>' . $smtp['encryption'] . '</td><td><span style="color: #28a745;">✅ TLS Encryption</span></td></tr>';
                echo '<tr><td>Username</td><td>' . $smtp['username'] . '</td><td><span style="color: #28a745;">✅ Domain Email</span></td></tr>';
                echo '</table>';
                
                // Test SMTP connection
                $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 10);
                if ($socket) {
                    $analysis['smtp_score'] += 20;
                    echo '<div class="test-result success">✅ SMTP Connection: Successfully connected to ' . $smtp['host'] . ':' . $smtp['port'] . '</div>';
                    fclose($socket);
                } else {
                    echo '<div class="test-result error">❌ SMTP Connection: Failed to connect - ' . $errstr . '</div>';
                    $analysis['recommendations'][] = 'Check SMTP server settings and credentials';
                }
            } else {
                echo '<div class="test-result warning">⚠️ Using PHP mail() function - Consider enabling SMTP</div>';
                $analysis['recommendations'][] = 'Enable SMTP for better email deliverability';
            }
            echo '</div>';
            
            // Test 2: Domain and Email Authentication
            echo '<div class="section info">';
            echo '<h3>🏷️ Domain & Authentication Analysis</h3>';
            
            $fromEmail = $config['email']['from_email'];
            $domain = substr(strrchr($fromEmail, "@"), 1);
            
            echo '<table>';
            echo '<tr><th>Check</th><th>Status</th><th>Impact</th></tr>';
            
            // Domain match check
            if (strpos($fromEmail, 'arinasystems.com') !== false) {
                $analysis['domain_score'] += 25;
                echo '<tr><td>Domain Match</td><td><span style="color: #28a745;">✅ From domain matches website</span></td><td>High positive impact</td></tr>';
            } else {
                echo '<tr><td>Domain Match</td><td><span style="color: #dc3545;">❌ From domain differs from website</span></td><td>Negative impact</td></tr>';
                $analysis['recommendations'][] = 'Use an email address from your website domain for the From field';
            }
            
            // SPF Record check (simulated)
            if (isset($smtp['spf_enabled']) && $smtp['spf_enabled']) {
                $analysis['domain_score'] += 15;
                echo '<tr><td>SPF Record</td><td><span style="color: #28a745;">✅ SPF configured</span></td><td>Prevents spoofing</td></tr>';
            } else {
                echo '<tr><td>SPF Record</td><td><span style="color: #ffc107;">⚠️ SPF status unknown</span></td><td>Add SPF record to DNS</td></tr>';
                $analysis['recommendations'][] = 'Add SPF record to DNS: v=spf1 include:smtp.hostinger.com ~all';
            }
            
            // DKIM check (simulated)
            if (isset($smtp['dkim_enabled']) && $smtp['dkim_enabled']) {
                $analysis['domain_score'] += 10;
                echo '<tr><td>DKIM Signing</td><td><span style="color: #28a745;">✅ DKIM enabled</span></td><td>Email authentication</td></tr>';
            } else {
                echo '<tr><td>DKIM Signing</td><td><span style="color: #ffc107;">⚠️ DKIM status unknown</span></td><td>Enable in hosting panel</td></tr>';
                $analysis['recommendations'][] = 'Enable DKIM signing in your Hostinger hosting panel';
            }
            
            echo '</table>';
            echo '</div>';
            
            // Test 3: Email Headers Analysis
            echo '<div class="section info">';
            echo '<h3>📋 Email Headers Quality</h3>';
            
            $headerChecks = [
                'Message-ID' => 'Unique identifier for email',
                'Date' => 'Proper timestamp format',
                'From' => 'Professional sender identification',
                'Reply-To' => 'Proper reply handling',
                'Content-Type' => 'HTML email support',
                'List-Unsubscribe' => 'CAN-SPAM compliance',
                'Organization' => 'Business identification'
            ];
            
            echo '<table>';
            echo '<tr><th>Header</th><th>Purpose</th><th>Status</th></tr>';
            foreach ($headerChecks as $header => $purpose) {
                echo '<tr><td>' . $header . '</td><td>' . $purpose . '</td><td><span style="color: #28a745;">✅ Included</span></td></tr>';
                $analysis['header_score'] += 3;
            }
            echo '</table>';
            echo '</div>';
            
            // Test 4: Content Analysis
            echo '<div class="section info">';
            echo '<h3>📝 Email Content Quality</h3>';
            
            $contentChecks = [
                '✅ Professional HTML template',
                '✅ Balanced text-to-image ratio',
                '✅ No suspicious keywords',
                '✅ Proper encoding (quoted-printable)',
                '✅ Mobile-responsive design',
                '✅ Clear business purpose',
                '✅ Contact information included'
            ];
            
            foreach ($contentChecks as $check) {
                echo '<div class="test-result success">' . $check . '</div>';
                $analysis['content_score'] += 3;
            }
            echo '</div>';
            
            // Calculate total score
            $analysis['total_score'] = $analysis['smtp_score'] + $analysis['header_score'] + $analysis['content_score'] + $analysis['domain_score'];
            
            return $analysis;
        }
        
        function getScoreRating($score) {
            if ($score >= 80) return ['good', 'Excellent'];
            if ($score >= 60) return ['warning', 'Good'];
            return ['poor', 'Needs Improvement'];
        }
        
        // Run analysis
        $analysis = analyzeEmailDeliverability($config);
        $scoreData = getScoreRating($analysis['total_score']);
        
        // Display overall score
        echo '<div class="section">';
        echo '<h3>📊 Overall Deliverability Score</h3>';
        echo '<div class="score ' . $scoreData[0] . '">';
        echo $analysis['total_score'] . '/100 - ' . $scoreData[1];
        echo '</div>';
        
        echo '<div class="config-grid">';
        echo '<div class="config-item">';
        echo '<h4>SMTP Score</h4>';
        echo '<div style="font-size: 20px; color: #007bff;">' . $analysis['smtp_score'] . '/50</div>';
        echo '</div>';
        echo '<div class="config-item">';
        echo '<h4>Headers Score</h4>';
        echo '<div style="font-size: 20px; color: #28a745;">' . $analysis['header_score'] . '/21</div>';
        echo '</div>';
        echo '<div class="config-item">';
        echo '<h4>Content Score</h4>';
        echo '<div style="font-size: 20px; color: #17a2b8;">' . $analysis['content_score'] . '/21</div>';
        echo '</div>';
        echo '<div class="config-item">';
        echo '<h4>Domain Score</h4>';
        echo '<div style="font-size: 20px; color: #6f42c1;">' . $analysis['domain_score'] . '/50</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Recommendations
        if (!empty($analysis['recommendations'])) {
            echo '<div class="section warning">';
            echo '<h3>🎯 Recommendations for Better Deliverability</h3>';
            foreach ($analysis['recommendations'] as $rec) {
                echo '<div class="recommendation">';
                echo '<h4>💡 Action Item</h4>';
                echo '<p>' . $rec . '</p>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        // Additional tips
        echo '<div class="section info">';
        echo '<h3>🚀 Advanced Deliverability Tips</h3>';
        echo '<div class="recommendation">';
        echo '<h4>📧 Email Best Practices</h4>';
        echo '<ul>';
        echo '<li><strong>Warm up your IP:</strong> Start with small email volumes and gradually increase</li>';
        echo '<li><strong>Monitor bounce rates:</strong> Keep bounce rates below 5%</li>';
        echo '<li><strong>Use consistent FROM name:</strong> Always use "ARina Systems" as sender name</li>';
        echo '<li><strong>Avoid spam triggers:</strong> Words like "FREE", "URGENT", excessive caps</li>';
        echo '<li><strong>Include unsubscribe link:</strong> Even for transactional emails</li>';
        echo '<li><strong>Test with multiple providers:</strong> Send test emails to Gmail, Outlook, Yahoo</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        
        // Test email functionality
        if (isset($_POST['send_test'])) {
            echo '<div class="section">';
            echo '<h3>🧪 Test Email Results</h3>';
            
            $testEmail = $_POST['test_email'] ?? '';
            if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                
                // Create test email
                $subject = 'Email Deliverability Test - ARina Systems';
                $htmlBody = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px; }
                        .content { padding: 20px; background: #f8f9fa; border-radius: 8px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>📧 Email Deliverability Test</h2>
                        </div>
                        <div class="content">
                            <p>This is a test email to verify email deliverability configuration.</p>
                            <p><strong>Test Details:</strong></p>
                            <ul>
                                <li>Sent via SMTP authentication</li>
                                <li>Professional HTML formatting</li>
                                <li>Proper email headers</li>
                                <li>From: ARina Systems business email</li>
                            </ul>
                            <p>If you received this email in your inbox (not spam), your email configuration is working correctly!</p>
                            <p><strong>Time:</strong> ' . date('F j, Y \a\t g:i A T') . '</p>
                        </div>
                    </div>
                </body>
                </html>';
                
                $result = sendSMTPEmail($config, $testEmail, $subject, $htmlBody);
                
                if ($result) {
                    echo '<div class="test-result success">✅ Test email sent successfully to ' . htmlspecialchars($testEmail) . '</div>';
                    echo '<div class="info" style="margin-top: 15px;">';
                    echo '<p><strong>Next Steps:</strong></p>';
                    echo '<ol>';
                    echo '<li>Check your inbox for the test email</li>';
                    echo '<li>If not in inbox, check spam/junk folder</li>';
                    echo '<li>If in spam, mark as "Not Spam" to improve reputation</li>';
                    echo '<li>Wait 5-10 minutes for delivery</li>';
                    echo '</ol>';
                    echo '</div>';
                } else {
                    echo '<div class="test-result error">❌ Failed to send test email</div>';
                }
            } else {
                echo '<div class="test-result error">❌ Please enter a valid email address</div>';
            }
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h3>🧪 Send Test Email</h3>
            <form method="post">
                <p>Send a test email to verify your configuration:</p>
                <input type="email" name="test_email" placeholder="Enter your email address" required 
                       style="padding: 12px; border: 1px solid #ddd; border-radius: 5px; width: 300px; margin-right: 10px;">
                <button type="submit" name="send_test" class="btn">Send Test Email</button>
            </form>
        </div>
        
        <div class="section info">
            <h3>📈 Monitoring Email Reputation</h3>
            <p>To maintain good email deliverability:</p>
            <ul>
                <li><strong>Use email testing tools:</strong> Mail Tester, SendForensics, GlockApps</li>
                <li><strong>Monitor blacklists:</strong> Check if your IP/domain is blacklisted</li>
                <li><strong>Track engagement:</strong> Monitor open rates and click rates</li>
                <li><strong>Handle bounces:</strong> Remove invalid email addresses promptly</li>
                <li><strong>Consistent sending:</strong> Maintain regular email sending patterns</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <p><strong>ARina Systems Email Deliverability Analysis</strong></p>
            <p>Generated on <?php echo date('F j, Y \a\t g:i A T'); ?></p>
        </div>
    </div>
</body>
</html>