<?php
/**
 * Mail configuration
 */

// Mail settings
$mail_host = 'smtp.yourdomain.com';  // SMTP server
$mail_port = 587;                     // SMTP port (usually 587 for TLS)
$mail_username = 'noreply@yourdomain.com';  // SMTP username
$mail_password = 'your_email_password';      // SMTP password
$mail_from_name = 'UIC School';              // Sender name
$mail_from_email = 'noreply@yourdomain.com'; // Sender email
$mail_reply_to = 'info@yourdomain.com';      // Reply-to email

// Include PHPMailer library
require_once __DIR__ . '/../vendor/autoload.php';