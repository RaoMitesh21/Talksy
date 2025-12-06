<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';
require_once 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get and validate input data
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required_fields = ['name', 'email', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Sanitize input data
    $name = filter_var(trim($input['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    $company = isset($input['company']) ? filter_var(trim($input['company']), FILTER_SANITIZE_STRING) : '';
    $subject = isset($input['subject']) ? filter_var(trim($input['subject']), FILTER_SANITIZE_STRING) : 'General Inquiry';
    $message = filter_var(trim($input['message']), FILTER_SANITIZE_STRING);
    
    if (!$email) {
        throw new Exception('Invalid email address');
    }
    
    if (strlen($name) < 2 || strlen($name) > 100) {
        throw new Exception('Name must be between 2 and 100 characters');
    }
    
    if (strlen($message) < 10 || strlen($message) > 5000) {
        throw new Exception('Message must be between 10 and 5000 characters');
    }
    
    // Store contact inquiry in database
    $stmt = $pdo->prepare("
        INSERT INTO contact_inquiries (name, email, company, subject, message, created_at, ip_address) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt->execute([
        $name,
        $email,
        $company,
        $subject,
        $message,
        $ip_address
    ]);
    
    // Log successful submission
    error_log("Contact form submission successful - Name: {$name}, Email: {$email}, Subject: {$subject}");
    
    // Send email notifications using PHPMailer SMTP
    $emailSent = false;
    $emailError = '';
    
    try {
        $mail = new PHPMailer(true);
        
        // SMTP configuration with extended timeout
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Disable SSL verification if needed (for local development)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Email content
        $mail->setFrom('your-email@gmail.com', 'Talksy Contact Form');
        $mail->addAddress('your-email@gmail.com', 'Talksy Support');
        $mail->addReplyTo($email, $name);
        
        $mail->Subject = "New Contact Form Submission: " . $subject;
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2 style='color: #3b82f6;'>New Contact Form Submission</h2>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>
                <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Message:</strong></p>
                <div style='background: white; padding: 15px; border-left: 4px solid #3b82f6; margin: 10px 0;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>IP Address:</strong> " . htmlspecialchars($ip_address) . "</p>
            </div>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->send();
        
        error_log("Contact form email sent successfully to your-email@gmail.com");
        
        // Send auto-reply to user
        $replyMail = new PHPMailer(true);
        $replyMail->isSMTP();
        $replyMail->Host = 'smtp.gmail.com';
        $replyMail->SMTPAuth = true;
        $replyMail->Username = 'your-email@gmail.com';
        $replyMail->Password = 'your-app-password';
        $replyMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $replyMail->Port = 587;
        $replyMail->Timeout = 30;
        $replyMail->SMTPKeepAlive = false;
        
        // Same SSL options as main email
        $replyMail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $replyMail->setFrom('your-email@gmail.com', 'Talksy Team');
        $replyMail->addAddress($email, $name);
        $replyMail->Subject = "Thank you for contacting Talksy";
        $replyMail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2 style='color: #3b82f6;'>Thank You for Contacting Talksy!</h2>
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>Thank you for reaching out to us. We have received your message and our team will review it shortly.</p>
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>Your message details:</strong></p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
            <p>We typically respond to inquiries within 24 hours. If your message is urgent, please don't hesitate to contact us directly.</p>
            <p>Best regards,<br><strong>The Talksy Team</strong></p>
        </body>
        </html>";
        
        $replyMail->isHTML(true);
        $replyMail->send();
        
        error_log("Auto-reply sent successfully to " . $email);
        
    } catch (Exception $email_error) {
        $errorDetails = "Email sending error: " . $email_error->getMessage();
        error_log($errorDetails);
        error_log("Email error trace: " . $email_error->getTraceAsString());
        // Continue even if email fails, but log detailed error
    }
    
    // Return success response regardless of email status
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We\'ll get back to you soon.'
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Contact form error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'There was an error sending your message. Please try again later.'
    ]);
}
?>
