<?php
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

include 'db_config.php';
include 'email_templates.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

/**
 * Show beautiful success page for web browsers
 */
function showSuccessPage($title, $message, $redirectUrl = null, $buttonText = "Continue") {
    // Set proper headers for HTML content
    header('Content-Type: text/html; charset=UTF-8');
    
    $redirectScript = '';
    if ($redirectUrl) {
        $redirectScript = "
        <script>
            let countdown = 5;
            const countdownElement = document.getElementById('countdown');
            const redirectTimer = setInterval(() => {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(redirectTimer);
                    window.location.href = '$redirectUrl';
                }
            }, 1000);
        </script>";
    }

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Talksy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #00d4ff 0%, #0ea5e9 50%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 50%, rgba(0, 212, 255, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(59, 130, 246, 0.3) 0%, transparent 50%);
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { transform: translate(0, 0); }
            50% { transform: translate(-50px, -50px); }
            100% { transform: translate(0, 0); }
        }
        .container {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            margin: 20px;
            position: relative;
            z-index: 1;
        }
        .logo-container {
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
            display: inline-block;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 28px;
        }
        p {
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.6;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
            color: white;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);
            font-size: 16px;
        }
        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5);
        }
        .countdown {
            color: #0ea5e9;
            font-weight: bold;
            margin-top: 20px;
            font-size: 14px;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: white;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            animation: successPulse 2s ease-in-out infinite;
        }
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .error-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: white;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="../talksy_logo.png" alt="Talksy Logo" class="logo">
        </div>';
        
    if (strpos($title, '✅') !== false || strpos($title, '🎉') !== false) {
        echo '<div class="success-icon">✓</div>';
    } elseif (strpos($title, '❌') !== false) {
        echo '<div class="error-icon">✗</div>';
    }
    
    echo '<h1>' . htmlspecialchars($title) . '</h1>
        <p>' . htmlspecialchars($message) . '</p>';
        
    if ($redirectUrl) {
        echo '<a href="' . htmlspecialchars($redirectUrl) . '" class="button">' . htmlspecialchars($buttonText) . '</a>
        <div class="countdown">Redirecting in <span id="countdown">5</span> seconds...</div>';
    }
    
    echo '</div>' . $redirectScript . '</body></html>';
    exit; // Important: exit after outputting HTML
}

$response = ["status" => "error", "message" => "Invalid verification request!"];

// Check if this is a web browser request or API request
$isWebRequest = !isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                !isset($_SERVER['HTTP_ACCEPT']) || 
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false);

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    $token = trim($_GET['token']);
    error_log("Verifying token: $token");
    error_log("Token length: " . strlen($token));

    // First check if token exists in database
    $checkStmt = $conn->prepare("SELECT username, email, email_verified FROM users WHERE verification_token = ?");
    $checkStmt->bind_param("s", $token);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        error_log("Token not found in database");
        $response["message"] = "Invalid verification token!";
        if ($isWebRequest) {
            showSuccessPage("Invalid Token ❌", "This verification link is invalid or has expired. Please try registering again.");
        } else {
            echo json_encode($response);
        }
        exit;
    }
    
    $userRow = $checkResult->fetch_assoc();
    if ($userRow['email_verified'] == 1) {
        $response = ["status" => "success", "message" => "Email already verified! You can now login."];
        if ($isWebRequest) {
            showSuccessPage("Already Verified! ✅", "Your email is already verified. You can proceed to login.", "http://localhost:3000/login", "Login Now");
        } else {
            echo json_encode($response);
        }
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE verification_token = ? AND email_verified = 0");
    if ($stmt === false) {
        $response["message"] = "Database prepare failed: " . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("s", $token);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = ["status" => "success", "message" => "Email verified successfully!"];
            
            // Fetch user details for success email
            $userStmt = $conn->prepare("SELECT username, email FROM users WHERE verification_token = ?");
            $userStmt->bind_param("s", $token);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            
            if ($userRow = $userResult->fetch_assoc()) {
                try {
                    // Send beautiful welcome email
                    $emailHtml = TalksyEmailTemplates::getWelcomeEmail($userRow['username']);
                    $emailSubject = "🎉 Welcome to Talksy - Account Activated!";
                    
                    $emailResult = sendTalksyEmail($userRow['email'], $userRow['username'], $emailSubject, $emailHtml);
                    
                    if ($emailResult['success']) {
                        error_log("✅ Welcome email sent to {$userRow['email']}");
                    } else {
                        error_log("⚠️ Welcome email failed: " . $emailResult['message']);
                    }
                } catch (Exception $e) {
                    error_log("⚠️ Welcome email exception: " . $e->getMessage());
                }
                $userStmt->close();
            }
            
            // Show appropriate response
            if ($isWebRequest) {
                showSuccessPage(
                    "Email Verified! 🎉", 
                    "Congratulations! Your email has been verified successfully. You can now log in to your Talksy account.", 
                    "http://localhost:3000/login", 
                    "Login to Talksy"
                );
            } else {
                echo json_encode($response);
            }
        } else {
            $response["message"] = "Invalid or expired token!";
            if ($isWebRequest) {
                showSuccessPage("Verification Failed ❌", "This verification link is invalid or has expired. Please try registering again.");
            } else {
                echo json_encode($response);
            }
        }
    } else {
        $response["message"] = "Update failed: " . $stmt->error;
        if ($isWebRequest) {
            showSuccessPage("Error ❌", "An error occurred during verification. Please try again later.");
        } else {
            echo json_encode($response);
        }
    }

    $stmt->close();
} else {
    $response["message"] = "No token provided!";
    if ($isWebRequest) {
        showSuccessPage("Invalid Access ❌", "No verification token was provided. Please check your email for the verification link.");
    } else {
        echo json_encode($response);
    }
}

$conn->close();

// Only output JSON if it's an API request
if (!$isWebRequest) {
    echo json_encode($response);
}
?>