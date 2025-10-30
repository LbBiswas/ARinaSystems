<?php
/**
 * Rate Limit Reset Tool for ARina Systems
 * Clears rate limiting logs to allow immediate testing
 */

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Limit Reset - ARina Systems</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #ffc107; color: #333; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
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
            <h1>⏱️ Rate Limit Reset Tool</h1>
            <p>Clear rate limiting logs to allow immediate form testing</p>
        </div>

        <?php
        if (isset($_POST['reset_rate_limit'])) {
            echo '<div class="result info">';
            echo '<h3>🔄 Clearing Rate Limit Logs...</h3>';
            
            $logFiles = [
                'contact_success.log',
                'contact_failed.log', 
                'contact_spam.log',
                'contact_exception.log'
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
            echo '<h3>✅ Rate Limit Reset Complete!</h3>';
            
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
            
            echo '<p><strong>You can now submit forms immediately without rate limiting!</strong></p>';
            echo '</div>';
            echo '</div>';
        }
        ?>

        <form method="post">
            <h3>🧪 For Testing Purposes</h3>
            <p>If you're getting "Too many submissions" errors while testing, click the button below to reset the rate limiting.</p>
            
            <div class="result warning">
                <h4>⚠️ Important Notes:</h4>
                <ul>
                    <li>This tool is for <strong>testing purposes only</strong></li>
                    <li>Rate limiting protects your forms from spam attacks</li>
                    <li>In production, rate limiting should be enabled</li>
                    <li>Current rate limit: <strong>50 submissions per hour</strong> (increased for testing)</li>
                </ul>
            </div>
            
            <button type="submit" name="reset_rate_limit" class="btn">🗑️ Clear Rate Limit Logs</button>
        </form>
        
        <div class="result info" style="margin-top: 30px;">
            <h3>📊 Current Configuration</h3>
            <?php
            $config = require_once 'contact_config.php';
            echo '<p><strong>Rate Limit:</strong> ' . $config['security']['rate_limit'] . ' submissions per hour</p>';
            echo '<p><strong>Honeypot Field:</strong> ' . $config['security']['honeypot_field'] . '</p>';
            echo '<p><strong>Logging Enabled:</strong> ' . ($config['logging']['enable_logging'] ? 'Yes' : 'No') . '</p>';
            ?>
            
            <h4>📋 Rate Limiting Files</h4>
            <ul>
                <li><code>contact_success.log</code> - Successful submissions</li>
                <li><code>contact_failed.log</code> - Failed email sends</li>
                <li><code>contact_spam.log</code> - Spam detected</li>
                <li><code>contact_exception.log</code> - System errors</li>
            </ul>
        </div>
        
        <div class="result info">
            <h3>🔄 After Testing</h3>
            <p>Remember to:</p>
            <ol>
                <li>Reset rate limit back to 5 submissions per hour for production</li>
                <li>Remove localhost from allowed origins</li>
                <li>Monitor logs for any issues</li>
            </ol>
        </div>
    </div>
</body>
</html>