<?php
// includes/email_helper.php - Email notification helper
class EmailHelper {
    
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->fromEmail = 'noreply@arinasystems.com'; // Your domain
        $this->fromName = 'Arina Systems - Billing Portal';
    }
    
    /**
     * Send document upload notification email
     */
    public function sendDocumentUploadNotification($userEmail, $userName, $documents, $uploadedBy, $category = 'General', $invoiceNumber = null) {
        $categoryCapitalized = ucfirst($category);
        
        // Update subject to include invoice number for invoices
        if ($category === 'invoice' && $invoiceNumber) {
            $subject = "New Invoice \"{$invoiceNumber}\" Assigned to You - Arina Systems";
        } else {
            $subject = "New {$categoryCapitalized} Document(s) Assigned to You - Arina Systems";
        }
        
        $documentCount = count($documents);
        $documentWord = $documentCount > 1 ? 'documents have' : 'document has';
        $articlePrefix = in_array(strtolower($category[0]), ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a';
        
        $documentDetails = '';
        foreach ($documents as $doc) {
            $documentDetails .= "• File Name: " . $doc['original_name'] . "\n";
            
            // Add bill amount if it exists (for invoices)
            if (isset($doc['bill_amount']) && $doc['bill_amount'] > 0) {
                $documentDetails .= "• Billed Amount: $" . number_format($doc['bill_amount'], 2) . "\n";
            }
            
            $documentDetails .= "• File Size: " . $this->formatFileSize($doc['file_size']) . "\n";
            $documentDetails .= "• Uploaded By: " . $uploadedBy . "\n";
            $documentDetails .= "• Upload Date: " . date('F j, Y, \a\t g:i A') . "\n";
            
            // Add separator if multiple documents
            if (count($documents) > 1) {
                $documentDetails .= "\n";
            }
        }
        
        $message = "Dear {$userName},

We would like to inform you that {$articlePrefix} new {$category} {$documentWord} been uploaded and assigned to your account.

Document Details:
{$documentDetails}

You can view or download your {$category} by logging into your customer portal at the link below:
👉 https://billing.arinasystems.com/customer.php

If you have any questions or need assistance regarding this document, please don't hesitate to contact our support team.

Best regards,
Arina Systems Billing Team
📧 support@arinasystems.com

🌐 www.arinasystems.com";
        
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($userEmail, $subject, $message, $headers);
    }
    
    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($userEmail, $userName, $password, $userType) {
        $subject = "Welcome to Arina Systems - Account Created";
        
        $loginUrl = "https://billing.arinasystems.com/login.html";
        
        $message = "
Dear {$userName},

Welcome to Arina Systems Billing Portal! Your account has been successfully created.

Login Details:
Email: {$userEmail}
Temporary Password: {$password}
Account Type: " . ucfirst($userType) . "

Please log in at: {$loginUrl}

For security reasons, we recommend changing your password after your first login.

If you have any questions or need assistance, please contact our support team.

Best regards,
Arina Systems Team

---
This is an automated message from Arina Systems. Please do not reply to this email.
For support, contact: support@arinasystems.com
        ";
        
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($userEmail, $subject, $message, $headers);
    }
    
    /**
     * Send password reset notification
     */
    public function sendPasswordResetNotification($userEmail, $userName) {
        $subject = "Password Reset - Arina Systems";
        
        $message = "
Dear {$userName},

Your password has been reset by an administrator.

Please contact your administrator to get your new login credentials.

Login URL: https://billing.arinasystems.com/login.html

If you did not request this password reset, please contact support immediately.

Best regards,
Arina Systems Team

---
This is an automated message from Arina Systems. Please do not reply to this email.
For support, contact: support@arinasystems.com
        ";
        
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($userEmail, $subject, $message, $headers);
    }
    
    /**
     * Send customer document upload notification to admin
     */
    public function sendCustomerUploadNotificationToAdmin($adminEmail, $customerName, $customerEmail, $document, $category = 'General') {
        $categoryCapitalized = ucfirst($category);
        $subject = "Customer Document Upload Alert - {$categoryCapitalized} - Arina Systems";
        
        $message = "Dear Administrator,

A customer has uploaded a new document to the billing portal.

Customer Information:
• Customer Name: {$customerName}
• Customer Email: {$customerEmail}

Document Details:
• File Name: " . $document['original_name'] . "
• Category: {$categoryCapitalized}
• File Size: " . $this->formatFileSize($document['file_size']) . "
• Upload Date: " . date('F j, Y, \a\t g:i A') . "

You can review and manage this document by logging into the admin panel:
https://billing.arinasystems.com/admin.php

This is an automated notification from the Arina Systems Billing Portal.

Best regards,
Arina Systems
Email: support@arinasystems.com
Phone: +1 (555) 123-4567";

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return mail($adminEmail, $subject, $message, $headers);
    }
    
    /**
     * Send document deletion notification to customer
     */
    public function sendDocumentDeletionNotification($customerEmail, $customerName, $documentTitle, $adminName) {
        $subject = "Document Deleted - Arina Systems Billing Portal";
        
        $message = "Dear {$customerName},

We are writing to inform you that one of your uploaded documents has been removed from your account by our administrative team.

Document Details:
• Document Title: {$documentTitle}
• Deleted By: {$adminName}
• Deletion Date: " . date('F j, Y, \a\t g:i A') . "

Reason for Deletion:
This document was removed by our administrative team. This may be due to:
- Document processing completion
- Policy compliance requirements  
- Administrative cleanup
- Account maintenance

If you have any questions about this deletion or need assistance, please contact our support team.

Best regards,
Arina Systems Support Team
Email: support@arinasystems.com

---
This is an automated notification from Arina Systems Billing Portal.
Please do not reply to this email.";

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: support@arinasystems.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($customerEmail, $subject, $message, $headers);
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize($bytes) {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Test email configuration
     */
    public function testEmail($testEmail) {
        $subject = "Email Test - Arina Systems";
        $message = "This is a test email from Arina Systems Billing Portal. If you receive this, email notifications are working correctly.\n\nFor support, contact: support@arinasystems.com";
        
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($testEmail, $subject, $message, $headers);
    }
}
?>