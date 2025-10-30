<?php
/**
 * Spam Detection Test for ARina Systems
 * Tests the improved spam detection with various scenarios
 */

// Load configuration and handlers
$config = require_once 'contact_config.php';
require_once 'contact_handler.php';
require_once 'schedule_handler.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spam Detection Test - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #dc3545; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .test-case { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #007bff; }
        .result { margin-top: 10px; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .code { background: #e9ecef; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Spam Detection Analysis</h1>
            <p>Testing improved spam detection to prevent false positives</p>
        </div>

        <?php
        // Test cases for spam detection
        $testCases = [
            [
                'name' => 'Legitimate Business Inquiry',
                'data' => [
                    'name' => 'John Smith',
                    'email' => 'john@company.com',
                    'phone' => '+1-555-0123',
                    'message' => 'Hi, I need a website for my business. My current site is www.oldsite.com and I want to upgrade it. Please contact me.',
                    'ip' => '192.168.1.1',
                    'user_agent' => 'Mozilla/5.0',
                    'website' => ''
                ],
                'expected' => false,
                'description' => 'Normal business inquiry mentioning websites should NOT be flagged as spam'
            ],
            [
                'name' => 'Contact with Email Addresses',
                'data' => [
                    'name' => 'Sarah Johnson',
                    'email' => 'sarah@example.org',
                    'phone' => '',
                    'message' => 'Please send the quote to sarah@example.org or backup@company.net. Thanks!',
                    'ip' => '192.168.1.2',
                    'user_agent' => 'Mozilla/5.0',
                    'website' => ''
                ],
                'expected' => false,
                'description' => 'Legitimate emails with .com/.org/.net should NOT be flagged'
            ],
            [
                'name' => 'Website Development Request',
                'data' => [
                    'name' => 'Mike Wilson',
                    'email' => 'mike@startup.com',
                    'phone' => '',
                    'message' => 'I want to build a site like https://example.com but better. Can you help?',
                    'ip' => '192.168.1.3',
                    'user_agent' => 'Mozilla/5.0',
                    'website' => ''
                ],
                'expected' => false,
                'description' => 'Legitimate inquiry with single URL should NOT be flagged'
            ],
            [
                'name' => 'Malicious Script Injection',
                'data' => [
                    'name' => 'Hacker',
                    'email' => 'bad@evil.com',
                    'phone' => '',
                    'message' => '<script>alert("hack")</script> This is malicious content',
                    'ip' => '192.168.1.4',
                    'user_agent' => 'Mozilla/5.0',
                    'website' => ''
                ],
                'expected' => true,
                'description' => 'Malicious script injection SHOULD be flagged as spam'
            ],
            [
                'name' => 'Excessive Link Spam',
                'data' => [
                    'name' => 'Spammer',
                    'email' => 'spam@spam.com',
                    'phone' => '',
                    'message' => 'Check out http://spam1.com and http://spam2.com and http://spam3.com and http://spam4.com and http://spam5.com',
                    'ip' => '192.168.1.5',
                    'user_agent' => 'Mozilla/5.0',
                    'website' => ''
                ],
                'expected' => true,
                'description' => 'Excessive links (5+ URLs) SHOULD be flagged as spam'
            ],
            [
                'name' => 'Honeypot Field Filled',
                'data' => [
                    'name' => 'Bot User',
                    'email' => 'bot@bot.com',
                    'phone' => '',
                    'message' => 'Normal looking message',
                    'ip' => '192.168.1.6',
                    'user_agent' => 'Mozilla/5.0',
                    'website' => 'http://bot-filled-this.com' // This should trigger honeypot
                ],
                'expected' => true,
                'description' => 'Honeypot field filled SHOULD be flagged as spam'
            ]
        ];

        foreach ($testCases as $index => $testCase) {
            echo '<div class="test-case">';
            echo '<h3>🧪 Test Case ' . ($index + 1) . ': ' . $testCase['name'] . '</h3>';
            echo '<p><strong>Description:</strong> ' . $testCase['description'] . '</p>';
            
            echo '<div class="code">';
            echo '<strong>Test Data:</strong><br>';
            echo 'Name: ' . htmlspecialchars($testCase['data']['name']) . '<br>';
            echo 'Email: ' . htmlspecialchars($testCase['data']['email']) . '<br>';
            echo 'Message: ' . htmlspecialchars(substr($testCase['data']['message'], 0, 100)) . '...<br>';
            if (!empty($testCase['data']['website'])) {
                echo 'Honeypot Field: ' . htmlspecialchars($testCase['data']['website']) . '<br>';
            }
            echo '</div>';
            
            // Test contact spam detection
            $isSpamContact = detectSpam($testCase['data'], $config);
            $contactResult = $isSpamContact === $testCase['expected'];
            
            echo '<div class="result ' . ($contactResult ? 'success' : 'error') . '">';
            echo '<strong>Contact Form Result:</strong> ';
            echo $isSpamContact ? 'FLAGGED as spam' : 'ALLOWED (not spam)';
            echo ' - ' . ($contactResult ? '✅ CORRECT' : '❌ INCORRECT');
            echo '</div>';
            
            echo '</div>';
        }
        ?>
        
        <div class="test-case" style="border-left-color: #28a745;">
            <h3>📊 Test Summary</h3>
            <p><strong>What the improved spam detection does:</strong></p>
            <ul>
                <li>✅ <strong>Allows legitimate business inquiries</strong> - No longer flags .com/.org/.net domains</li>
                <li>✅ <strong>Allows single URL mentions</strong> - Website references are now acceptable</li>
                <li>✅ <strong>Allows professional emails</strong> - Business email addresses won't trigger spam</li>
                <li>🛡️ <strong>Blocks malicious scripts</strong> - Still catches dangerous content</li>
                <li>🛡️ <strong>Blocks excessive links</strong> - Prevents link spam (4+ URLs)</li>
                <li>🛡️ <strong>Blocks bot submissions</strong> - Honeypot field still works</li>
            </ul>
            
            <p><strong>Key Improvements:</strong></p>
            <ul>
                <li>Removed aggressive URL pattern matching (.com, .org, .net)</li>
                <li>Increased spam score threshold from 2 to 8</li>
                <li>Added weighted scoring system</li>
                <li>Focused on truly malicious content</li>
            </ul>
        </div>
        
        <div class="test-case" style="border-left-color: #ffc107;">
            <h3>🔄 Next Steps</h3>
            <p>Now that spam detection is improved:</p>
            <ol>
                <li><strong>Test your actual forms</strong> - Submit test inquiries through your website</li>
                <li><strong>Check email delivery</strong> - See if emails now reach inbox instead of spam</li>
                <li><strong>Monitor for false positives</strong> - Watch for legitimate emails being blocked</li>
                <li><strong>Adjust if needed</strong> - Fine-tune spam threshold based on results</li>
            </ol>
        </div>
    </div>
</body>
</html>