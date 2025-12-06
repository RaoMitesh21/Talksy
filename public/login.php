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

error_log("Login script executed at " . date('Y-m-d H:i:s'));

include 'db_config.php';

$response = ["status" => "error", "message" => "Something went wrong!"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Handle both JSON and form data
    $email = filter_var(trim($data['email'] ?? $_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($data['password'] ?? $_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $response["message"] = "All fields are required!";
        echo json_encode($response);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["message"] = "Invalid email format!";
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("SELECT username, password, email_verified, display_name, email FROM users WHERE email = ?");
    if ($stmt === false) {
        $response["message"] = "Database prepare failed: " . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        $response["message"] = "Execution failed: " . $stmt->error;
        echo json_encode($response);
        exit;
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['email_verified'] == 0) {
            $response["message"] = "Please verify your email before logging in!";
        } elseif (password_verify($password, $row['password'])) {
            $response = [
                "status" => "success", 
                "message" => "Login successful!",
                "user" => [
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "display_name" => $row['display_name'] ?: $row['username']
                ]
            ];
        } else {
            $response["message"] = "Invalid password!";
        }
    } else {
        $response["message"] = "Invalid email!";
    }

    $stmt->close();
} else {
    $response["message"] = "Invalid request method!";
}

$conn->close();
echo json_encode($response);
?>