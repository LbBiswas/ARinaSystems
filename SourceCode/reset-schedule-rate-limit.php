<?php
/**
 * Schedule Form Rate Limit Reset Tool for ARina Systems
 * Clears rate limiting logs to allow immediate testing of schedule forms
 */

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Form Rate Limit Reset - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #28a745; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .btn { background: #dc3545; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px; }
        .btn:hover { background: #c82333; }
        .btn.success { background: #28a745; }
        .result { margin-top: 20px; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Schedule Form Rate Limit Reset</h1>
            <p>Clear rate limiting logs for consultation booking forms</p>
        </div>

        <?php
        if (isset($_POST['reset_schedule_rate_limit'])) {
            echo '<div class="result info">';
            echo '<h3>🔄 Clearing Schedule Form Rate Limit Logs...</h3>';
            
            $logFiles = [
                'schedule_success.log',
                'schedule_failed.log', 
                'schedule_spam.log',
                'schedule_exception.log'
            ];
            
            $clearedFiles = [];
            $notFoundFiles = [];
            
            foreach ($logFiles as $logFile) {
                if (file_exists($logFile)) {
                    if (unlink($logFile)) {
                        $clearedFiles[] = $logFile;
                    }
                } else {
                    $notFoundFiles[] = $logFile;
                }
            }
            
            echo '<div class="result success">';
            echo '<h3>✅ Schedule Form Rate Limit Reset Complete!</h3>';
            
            if (!empty($clearedFiles)) {
                echo '<p><strong>Cleared Files:</strong></p>';
                echo '<ul>';
                foreach ($clearedFiles as $file) {
                    echo '<li>' . htmlspecialchars($file) . '</li>';
                }
                echo '</ul>';
            }
            
            if (!empty($notFoundFiles)) {
                echo '<p><strong>Files Not Found (already clean):</strong></p>';
                echo '<ul>';
                foreach ($notFoundFiles as $file) {
                    echo '<li>' . htmlspecialchars($file) . '</li>';
                }
                echo '</ul>';
            }
            
            echo '<p><strong>You can now submit schedule consultation forms immediately!</strong></p>';
            echo '</div>';
            echo '</div>';
        }
        ?>

        <form method="post">
            <h3>📅 For Schedule Form Testing</h3>
            <p>If you're getting "Too many booking requests" errors while testing the schedule consultation form, click below to reset:</p>
            
            <div class="result warning">
                <h4>⚠️ Important Notes:</h4>
                <ul>
                    <li>This tool is for <strong>testing purposes only</strong></li>
                    <li>Rate limiting protects your forms from spam attacks</li>
                    <li>Current rate limit: <strong>50 submissions per hour</strong> (increased for testing)</li>
                    <li>Works for schedule/consultation booking forms</li>
                </ul>
            </div>
            
            <button type="submit" name="reset_schedule_rate_limit" class="btn">🗑️ Clear Schedule Form Rate Limit</button>
        </form>
        
        <div class="result info" style="margin-top: 30px;">
            <h3>📊 Schedule Form Configuration</h3>
            <p><strong>Rate Limit:</strong> 50 submissions per hour (increased for testing)</p>
            <p><strong>Honeypot Field:</strong> honeypot</p>
            <p><strong>Required Fields:</strong> first_name, last_name, phone, email, call_time</p>
            
            <h4>📋 Schedule Form Rate Limiting Files</h4>
            <ul>
                <li><code>schedule_success.log</code> - Successful consultation bookings</li>
                <li><code>schedule_failed.log</code> - Failed email sends</li>
                <li><code>schedule_spam.log</code> - Spam detected</li>
                <li><code>schedule_exception.log</code> - System errors</li>
            </ul>
        </div>
        
        <div class="result info">
            <h3>🧪 Now Test Your Forms!</h3>
            <p>With the DNS records properly configured and rate limits increased:</p>
            <ol>
                <li><strong>Test Contact Form</strong> - Submit a test inquiry</li>
                <li><strong>Test Schedule Form</strong> - Book a test consultation</li>
                <li><strong>Check Email Delivery</strong> - Should now reach inbox instead of spam!</li>
                <li><strong>Monitor Results</strong> - With SPF and DKIM enabled, emails should authenticate properly</li>
            </ol>
        </div>
    </div>
</body>
</html>