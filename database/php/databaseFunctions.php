<?php

// Initialize the MySQLi connection
function dbConnect(){
	$db = new mysqli('dbHost', 'dbUser', 'dbPass', 'dbName');
	if ($db->connect_error) {
		die("Connection failed: " . $db->connect_error);
	}
	return $db;
}

function doRegister($username, $email, $password) {
    $db = dbConnect();

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Prepare and bind the statement
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if (!$stmt) {
        return "Prepare failed: " . $db->error;
    }

    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "User registered successfully!";
    } else {
        $message = false;
    }

    $stmt->close();
    $db->close();
    return $message;
}

function login($username, $password) {
    $db = dbConnect();

    // Prepare and execute the statement
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        return "Prepare failed: " . $db->error;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Validate the password
    if ($user && password_verify($password, $user['password'])) {
        // Generate JWT token
        $token = generateJWT($user['id']);
        $message = $token; // Send this token to the frontend
    } else {
        $message = false;
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

    return JWT::encode($payload, $key);
}
?>
