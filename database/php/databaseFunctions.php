<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;

// Initialize the MySQLi connection
function dbConnect(){
	$db = new mysqli('localhost', 'admin', 'pass', 'project');
	if ($db->connect_error) {
		die("Connection failed: " . $db->connect_error);
	}
	return $db;
}

function doRegister($username, $email, $password) {
    $db = dbConnect();

    // Check if username already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        return "Prepare failed: " . $db->error;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Username is taken
        $stmt->close();
        $db->close();
        return array("success" => false, "message" => "Username is already taken.");
    }

    // Username is available, proceed with registration
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if (!$stmt) {
        return "Prepare failed: " . $db->error;
    }

    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    $stmt->execute();

    $isRegistered = $stmt->affected_rows > 0;
    $stmt->close();
    $db->close();

    return array("success" => $isRegistered, "message" => $isRegistered ? "Registration successful!" : "Registration failed.");
}

function doLogin($username, $password) {
    $db = dbConnect();

    // Prepare and execute the statement
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        return array("success" => false, "message" => "Database error: Unable to prepare statement.");
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Validate the password
    if ($user && password_verify($password, $user['password'])) {
        // Generate JWT token
        $token = generateJWT($user['id']);
        $message = array("success" => true, "token" => $token);
    } else {
        $message = array("success" => false, "message" => "Invalid username or password.");
    }

    $stmt->close();
    $db->close();
    return $message;
}

// Session Validation (JWT)
function validateToken($token) {
    // Decode and verify the JWT
    try {
        $decoded = JWT::decode($token, 'it490key', ['HS256']);
        return $decoded; // User is authenticated
    } catch (Exception $e) {
        return false; // Invalid token
    }
}

function generateJWT($userId) {
    $key = 'it490key';
    $payload = [
        'iss' => 'IT490-Project',
        'sub' => $userId,
        'iat' => time(),
        'exp' => time() + (60 * 60) // 1-hour expiration
    ];

    return JWT::encode($payload, $key,'HS256');
}
?>
