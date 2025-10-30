<?php
/**
 * Simple SMTP Mailer for ARina Systems
 * Lightweight SMTP implementation for better email deliverability
 */

function sendSMTPEmail($config, $to, $subject, $htmlBody, $replyTo = null) {
    if (!$config['mail_method']['use_smtp']) {
        // Fallback to regular mail if SMTP is disabled
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    $smtp = $config['mail_method']['smtp'];
    
    // Create socket connection
    $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, $smtp['timeout']);
    if (!$socket) {
        error_log("SMTP Connection failed: $errstr ($errno)");
        // Fallback to regular mail
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Read server greeting
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Send EHLO
    fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    $response = fgets($socket, 515);
    
    // Start TLS if required
    if ($smtp['encryption'] === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = fgets($socket, 515);
    }
    
    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Send username
    fputs($socket, base64_encode($smtp['username']) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Send password
    fputs($socket, base64_encode($smtp['password']) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Send MAIL FROM - Use the same email as username for alignment
    fputs($socket, "MAIL FROM:<" . $smtp['username'] . ">\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Send RCPT TO
    fputs($socket, "RCPT TO:<$to>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Send DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '354') {
        fclose($socket);
        return sendRegularMail($to, $subject, $htmlBody, $config['email']['from_email'], $config['email']['site_name'], $replyTo);
    }
    
    // Build message with enhanced deliverability headers
    $messageId = '<' . time() . '-' . uniqid() . '@arinasystems.com>';
    $headers = "Message-ID: $messageId\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "From: " . $config['email']['site_name'] . " <" . $smtp['username'] . ">\r\n";
    $headers .= "Sender: " . $smtp['username'] . "\r\n";  // Align sender with from
    $headers .= "To: $to\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: $replyTo\r\n";
    }
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 3 (Normal)\r\n";
    $headers .= "X-MSMail-Priority: Normal\r\n";
    $headers .= "Importance: Normal\r\n";
    $headers .= "Return-Path: " . $smtp['username'] . "\r\n";
    $headers .= "List-Unsubscribe: <mailto:unsubscribe@arinasystems.com>\r\n";
    $headers .= "X-Sender: " . $smtp['username'] . "\r\n";
    $headers .= "X-Organization: ARina Systems\r\n";
    $headers .= "Organization: ARina Systems\r\n";
    $headers .= "\r\n";
    
    // Send headers and body (encode for quoted-printable)
    $encodedBody = quoted_printable_encode($htmlBody);
    fputs($socket, $headers . $encodedBody . "\r\n.\r\n");
    $response = fgets($socket, 515);
    
    // Send QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return substr($response, 0, 3) == '250';
}

/**
 * Fallback regular mail function with enhanced headers for better deliverability
 */
function sendRegularMail($to, $subject, $htmlBody, $fromEmail, $siteName, $replyTo = null) {
    $messageId = '<' . time() . '-' . uniqid() . '@arinasystems.com>';
    
    $headers = [
        "From: $siteName <$fromEmail>",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Content-Transfer-Encoding: quoted-printable",
        "Message-ID: $messageId",
        "Date: " . date('r'),
        "Return-Path: $fromEmail",
        "X-Mailer: PHP/" . phpversion(),
        "X-Priority: 3 (Normal)",
        "X-MSMail-Priority: Normal",
        "Importance: Normal",
        "List-Unsubscribe: <mailto:unsubscribe@arinasystems.com>",
        "X-Sender: $fromEmail",
        "X-Organization: ARina Systems",
        "Organization: ARina Systems"
    ];
    
    if ($replyTo) {
        $headers[] = "Reply-To: $replyTo";
    }
    
    // Encode body for better deliverability
    $encodedBody = quoted_printable_encode($htmlBody);
    
    return mail($to, $subject, $encodedBody, implode("\r\n", $headers));
}
?>