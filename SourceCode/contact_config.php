<?php
/**
 * Contact Form Configuration File
 * Update these settings to match your Hostinger hosting configuration
 */

return [
    // Email Configuration
    'email' => [
        'to_email' => 'info@arinasystems.com',           // Where contact emails will be sent
        'from_email' => 'support@arinasystems.com',        // Sender email (should be from your domain)
        'site_name' => 'ARina Systems',
        'reply_to_customer' => true,                       // Whether to set customer email as reply-to
        'sender_name' => 'ARina Systems Contact Form',     // Display name for sender
        'bounce_email' => 'bounce@arinasystems.com',       // Bounce handling email
    ],
    
    // Security Settings
    'security' => [
        'rate_limit' => 10,                                // Max submissions per IP per hour (increased for testing)
        'allowed_origins' => [                             // Domains allowed to submit forms
            'arinasystems.com',
            'www.arinasystems.com',
            'localhost'                                    // Remove this in production
        ],
        'honeypot_field' => 'website',                     // Honeypot field name for spam protection
        'enable_captcha' => false,                         // Set to true if you want to add reCAPTCHA
        'captcha_secret' => '',                            // Your reCAPTCHA secret key
    ],
    
    // Email Method Configuration
    'mail_method' => [
        'use_smtp' => true,                                // Enable SMTP for better deliverability
        
        // SMTP Settings (for Hostinger)
        'smtp' => [
            'host' => 'smtp.hostinger.com',               // Hostinger SMTP server
            'port' => 587,                                 // SMTP port (587 for TLS, 465 for SSL)
            'encryption' => 'tls',                         // 'tls' or 'ssl'
            'username' => 'support@arinasystems.com',      // Fixed typo - removed extra .com
            'password' => 'Animesh&$!&#!%%(&@1230',        // Your email password
            'timeout' => 30,                               // Connection timeout in seconds
            'dkim_enabled' => true,                        // Enable DKIM signing if available
            'spf_enabled' => true,                         // SPF record compliance
        ]
    ],
    
    // Validation Rules
    'validation' => [
        'name_min_length' => 2,
        'name_max_length' => 100,
        'message_min_length' => 10,
        'message_max_length' => 5000,
        'required_fields' => ['name', 'email', 'message'],
        'optional_fields' => ['phone'],
    ],
    
    // Logging Configuration
    'logging' => [
        'enable_logging' => true,
        'log_successful_submissions' => true,
        'log_failed_submissions' => true,
        'log_file_max_size' => 5 * 1024 * 1024,          // 5MB max log file size
        'cleanup_logs_older_than' => 30,                  // Days to keep logs
    ],
    
    // Response Messages
    'messages' => [
        'success' => 'Thank you for your message! We will get back to you within 24 hours.',
        'error_general' => 'Sorry, there was an error sending your message. Please try again later.',
        'error_rate_limit' => 'Too many submissions. Please wait before submitting again.',
        'error_validation' => 'Please fix the validation errors and try again.',
        'error_spam' => 'Your message appears to contain spam content.',
    ],
    
    // Development Settings (remove or set to false in production)
    'development' => [
        'debug_mode' => false,                             // Set to false in production
        'test_mode' => false,                              // Set to false in production
        'log_all_requests' => false,                       // Set to false in production
    ]
];
?>