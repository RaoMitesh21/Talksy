<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enhanced CORS headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log("Registration script executed at " . date('Y-m-d H:i:s'));

include 'db_config.php';
include 'email_templates.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$response = ["status" => "error", "message" => "Something went wrong!"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Handle both JSON and form data
    $username = trim($data['username'] ?? $_POST['username'] ?? '');
    $email = filter_var(trim($data['email'] ?? $_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($data['password'] ?? $_POST['password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $response["message"] = "All fields are required!";
        echo json_encode($response);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["message"] = "Invalid email format!";
        echo json_encode($response);
        exit;
    }

    $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    if ($checkUser === false) {
        $response["message"] = "Database prepare failed: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    $checkUser->bind_param("ss", $username, $email);
    $checkUser->execute();
    $checkUser->store_result();

    if ($checkUser->num_rows > 0) {
        $response["message"] = "Username or Email already exists!";
        echo json_encode($response);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $verification_token = bin2hex(random_bytes(16)); // Unique token

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, email_verified, verification_token) VALUES (?, ?, ?, 0, ?)");
    if ($stmt === false) {
        $response["message"] = "Database prepare failed: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $verification_token); // Corrected to 4 parameters

    if ($stmt->execute()) {
        // Send beautiful verification email using templates
        try {
            $verificationLink = "http://localhost/Talksy/public/verify.php?token=" . urlencode($verification_token);
            
            // Get beautiful HTML email template
            $emailHtml = TalksyEmailTemplates::getVerificationEmail($username, $verificationLink);
            $emailSubject = "🎉 Welcome to Talksy - Verify Your Email";
            
            // Send email using enhanced function
            $emailResult = sendTalksyEmail($email, $username, $emailSubject, $emailHtml);
            
            if ($emailResult['success']) {
                $response = [
                    "status" => "success", 
                    "message" => "🎉 Registration successful! Please check your email to verify your account.",
                    "details" => "We've sent a beautiful verification email to $email"
                ];
                error_log("Registration successful for user: $username ($email)");
            } else {
                // Email failed but user was created - provide fallback
                $response = [
                    "status" => "warning", 
                    "message" => "Account created successfully, but verification email failed to send.",
                    "details" => "Please contact support or try registering again.",
                    "email_error" => $emailResult['message']
                ];
                error_log("Email sending failed for user $username: " . $emailResult['message']);
                // Don't delete the user - they can still verify manually
            }
            
        } catch (Exception $e) {
            // Email failed but user was created
            $response = [
                "status" => "warning", 
                "message" => "Account created but verification email failed.",
                "details" => "Please contact support for manual verification.",
                "error" => $e->getMessage()
            ];
            error_log("Email exception for user $username: " . $e->getMessage());
        }
    } else {
        $response["message"] = "Database error: " . $stmt->error;
    }

    $stmt->close();
} else {
    $response["message"] = "Invalid request method!";
}

$conn->close();
echo json_encode($response);
?>