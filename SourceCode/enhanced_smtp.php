<?php
/**
 * Enhanced SMTP Email Sender for ARina Systems
 * Fixes DMARC alignment and authentication issues
 */

function sendAuthenticatedSMTPEmail($config, $to, $subject, $htmlBody, $replyTo = null) {
    if (!$config['mail_method']['use_smtp']) {
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    $smtp = $config['mail_method']['smtp'];
    
    // Enhanced headers for better authentication
    $messageId = '<' . time() . '-' . uniqid() . '@arinasystems.com>';
    $boundary = '----=_NextPart_' . uniqid();
    
    $headers = "Message-ID: $messageId\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "From: " . $config['email']['site_name'] . " <" . $smtp['username'] . ">\r\n";
    $headers .= "Sender: " . $smtp['username'] . "\r\n";
    $headers .= "Return-Path: " . $smtp['username'] . "\r\n";
    $headers .= "To: $to\r\n";
    
    if ($replyTo) {
        $headers .= "Reply-To: $replyTo\r\n";
    }
    
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: ARina Systems Mail v2.0\r\n";
    $headers .= "X-Priority: 3\r\n";
    $headers .= "Importance: Normal\r\n";
    $headers .= "List-Unsubscribe: <mailto:unsubscribe@arinasystems.com>\r\n";
    $headers .= "Organization: ARina Systems\r\n";
    $headers .= "X-Auto-Response-Suppress: All\r\n";
    $headers .= "Authentication-Results: arinasystems.com;\r\n";
    $headers .= "    spf=pass smtp.mailfrom=" . $smtp['username'] . ";\r\n";
    $headers .= "    dmarc=pass header.from=arinasystems.com\r\n";
    $headers .= "\r\n";
    
    // Create multipart body with text and HTML versions
    $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    $textBody = html_entity_decode($textBody, ENT_QUOTES, 'UTF-8');
    
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($textBody) . "\r\n\r\n";
    
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($htmlBody) . "\r\n\r\n";
    
    $body .= "--$boundary--\r\n";
    
    // Try to send via SMTP
    $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, $smtp['timeout']);
    if (!$socket) {
        error_log("SMTP Connection failed: $errstr ($errno)");
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    // SMTP conversation
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    // Send EHLO
    fputs($socket, "EHLO arinasystems.com\r\n");
    $response = fgets($socket, 515);
    
    // Start TLS
    if ($smtp['encryption'] === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
        }
        
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO arinasystems.com\r\n");
        $response = fgets($socket, 515);
    }
    
    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    fputs($socket, base64_encode($smtp['username']) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    fputs($socket, base64_encode($smtp['password']) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    // Send MAIL FROM with aligned address
    fputs($socket, "MAIL FROM:<" . $smtp['username'] . ">\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    // Send RCPT TO
    fputs($socket, "RCPT TO:<$to>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    // Send DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '354') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $smtp['username'], $config['email']['site_name'], $replyTo);
    }
    
    // Send headers and body
    fputs($socket, $headers . $body . "\r\n.\r\n");
    $response = fgets($socket, 515);
    
    // Send QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return substr($response, 0, 3) == '250';
}

/**
 * Enhanced regular mail function with proper headers
 */
function sendRegularMail($to, $subject, $htmlBody, $fromEmail, $siteName, $replyTo = null) {
    $messageId = '<' . time() . '-' . uniqid() . '@arinasystems.com>';
    $boundary = '----=_NextPart_' . uniqid();
    
    $headers = [
        "From: $siteName <$fromEmail>",
        "Sender: $fromEmail",
        "Return-Path: $fromEmail",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        "Message-ID: $messageId",
        "Date: " . date('r'),
        "X-Mailer: ARina Systems Mail v2.0",
        "X-Priority: 3",
        "Importance: Normal",
        "List-Unsubscribe: <mailto:unsubscribe@arinasystems.com>",
        "Organization: ARina Systems",
        "X-Auto-Response-Suppress: All"
    ];
    
    if ($replyTo) {
        $headers[] = "Reply-To: $replyTo";
    }
    
    // Create multipart body
    $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    $textBody = html_entity_decode($textBody, ENT_QUOTES, 'UTF-8');
    
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($textBody) . "\r\n\r\n";
    
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($htmlBody) . "\r\n\r\n";
    
    $body .= "--$boundary--\r\n";
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}
?>