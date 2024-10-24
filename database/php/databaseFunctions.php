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

    	//current epoch time
    	$epochTime = time();

    	$stmt = $db->prepare("INSERT INTO users (username, email, password, epoch) VALUES (?, ?, ?, ?)");
    	if (!$stmt) {
        	return "Prepare failed: " . $db->error;
    	}

	$stmt->bind_param("sssi", $username, $email, $hashedPassword, $epochTime);
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
	try {
        	// Decoding the token with the secret key and allowed algorithms
        	$decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('it490key', 'HS256'));

        	// Return success and the decoded token data
        	return array("success" => true, "message" => "Token is valid.", "userId" => $decoded->sub);
    	} catch (Exception $e) {
        	// Handle token validation errors (e.g., expired token)
        	return array("success" => false, "message" => "Invalid or expired token.");
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

// League-Related Functions
function createLeague($userId, $leagueName) {
    	$db = dbConnect();
    	$stmt = $db->prepare("INSERT INTO leagues (name, created_by) VALUES (?, ?)");
    	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->bind_param("si", $leagueName, $userId);
    	$stmt->execute();

    	if ($stmt->affected_rows > 0) {
        	// Add the user to the league they just created
        	$leagueId = $stmt->insert_id;
        	$stmt = $db->prepare("INSERT INTO user_league (user_id, league_id) VALUES (?, ?)");
        	$stmt->bind_param("ii", $userId, $leagueId);
        	$stmt->execute();
        	$stmt->close();
        	$db->close();
        	return array("success" => true, "message" => "League created successfully.");
    	}	

    	$stmt->close();
    	$db->close();
    	return array("success" => false, "message" => "Failed to create league.");
}


function joinLeague($userId, $leagueId) {
    	$db = dbConnect();

    	// Check if user is already in the league
    	$stmt = $db->prepare("SELECT * FROM user_league WHERE user_id = ? AND league_id = ?");
    	$stmt->bind_param("ii", $userId, $leagueId);
    	$stmt->execute();
    	$result = $stmt->get_result();

    	if ($result->num_rows > 0) {
        	$stmt->close();
        	$db->close();
        	return array("success" => false, "message" => "User is already a member of this league.");
    	}

    	// Add the user to the league if they are not already a member
    	$stmt = $db->prepare("INSERT INTO user_league (user_id, league_id) VALUES (?, ?)");
    	$stmt->bind_param("ii", $userId, $leagueId);
    	$stmt->execute();
    	$stmt->close();
    	$db->close();

    	return array("success" => true, "message" => "User successfully joined the league.");
}

function leaveLeague($userId, $leagueId) {
    	$db = dbConnect();

    	// Check if the user is a part of the league
    	$stmt = $db->prepare("SELECT * FROM user_league WHERE user_id = ? AND league_id = ?");
    	$stmt->bind_param("ii", $userId, $leagueId);
    	$stmt->execute();
    	$result = $stmt->get_result();

    	if ($result->num_rows === 0) {
        	// The user is not part of the league
        	$stmt->close();
        	$db->close();
        	return array("success" => false, "message" => "User is not a member of this league.");
    	}

    	// Remove the user from the user_league table
    	$stmt = $db->prepare("DELETE FROM user_league WHERE user_id = ? AND league_id = ?");
    	$stmt->bind_param("ii", $userId, $leagueId);
    	$stmt->execute();
    
    	// Check if the deletion was successful
    	if ($stmt->affected_rows > 0) {
        	$stmt->close();
        	$db->close();
        	return array("success" => true, "message" => "User successfully left the league.");
    	} else {
        	$stmt->close();
        	$db->close();
        	return array("success" => false, "message" => "Error occurred while removing the user from the league.");
    	}
}

function getUserLeagues($userId) {
    	$db = dbConnect();
   	$stmt = $db->prepare("SELECT leagues.id, leagues.name FROM user_league
   				JOIN leagues ON user_league.league_id = leagues.id
                          	WHERE user_league.user_id = ?");
    	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->bind_param("i", $userId);
    	$stmt->execute();
    	$result = $stmt->get_result();
    	$leagues = $result->fetch_all(MYSQLI_ASSOC);
    	$stmt->close();
    	$db->close();

    	if (empty($leagues)) {
        	return array("success" => false, "message" => "No leagues found for this user.");
    	}
    	return array("success" => true, "leagues" => $leagues);
}

function validateLeagueAccess($userId, $leagueId) {
    	$db = dbConnect();
    	$stmt = $db->prepare("SELECT * FROM user_league WHERE user_id = ? AND league_id = ?");
    	$stmt->bind_param("ii", $userId, $leagueId);
    	$stmt->execute();
    	$result = $stmt->get_result();

   	if ($result->num_rows > 0) {
   		$stmt->close();
    		$db->close();
        	return array("success" => true, "message" => "User has access to the league.");
    	} else {
    		$stmt->close();
    		$db->close();
        	return array("success" => false, "message" => "User does not have access to the league.");
    	}
}

function getLeaderboard($leagueId) {
    	$db = dbConnect();
    	$stmt = $db->prepare("SELECT users.username, user_league.points 
                          	FROM user_league 
                          	JOIN users ON user_league.user_id = users.id 
                          	WHERE user_league.league_id = ? 
                          	ORDER BY points DESC");
    	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->bind_param("i", $leagueId);
    	$stmt->execute();
    	$result = $stmt->get_result();
    	$leaderboard = $result->fetch_all(MYSQLI_ASSOC);
    	$stmt->close();
    	$db->close();

    	if (empty($leaderboard)) {
        	return array("success" => false, "message" => "No leaderboard data found.");
    	}
    	return array("success" => true, "leaderboard" => $leaderboard);
}
function getAllLeagues() {
    	$db = dbConnect();
    	$stmt = $db->prepare("SELECT id, name FROM leagues");
    	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->execute();
    	$result = $stmt->get_result();
    	$leagues = $result->fetch_all(MYSQLI_ASSOC);
    	$stmt->close();
    	$db->close();

    	if (empty($leagues)) {
        	return array("success" => false, "message" => "No leagues found.");
    	}
    	return array("success" => true, "leagues" => $leagues);
}

function getLeagueName($leagueId) {
    	$db = dbConnect();
    	$stmt = $db->prepare("SELECT name FROM leagues WHERE id = ?");
    	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->bind_param("i", $leagueId);
    	$stmt->execute();
    	$stmt->bind_result($leagueName);
    	$stmt->fetch();
    	$stmt->close();
    	$db->close();

    	if (empty($leagueName)) {
        	return array("success" => false, "message" => "League not found.");
    	}
    	return array("success" => true, "league_name" => $leagueName);
}

// League Message Functions
function postMessage($userId, $leagueId, $message) {
    	$db = dbConnect();
    	$stmt = $db->prepare("INSERT INTO messages (user_id, league_id, message) VALUES (?, ?, ?)");
   	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->bind_param("iis", $userId, $leagueId, $message);
    	$stmt->execute();

    	if ($stmt->affected_rows > 0) {
        	$stmt->close();
        	$db->close();
        	return array("success" => true, "message" => "Message posted successfully.");
    	}	

    	$stmt->close();
    	$db->close();
    	return array("success" => false, "message" => "Failed to post message.");
}

function getMessages($leagueId) {
    	$db = dbConnect();
    	$stmt = $db->prepare("SELECT users.username, messages.message, messages.created_at
        	FROM messages
        	JOIN users ON messages.user_id = users.id
        	WHERE league_id = ?
        	ORDER BY messages.created_at DESC");
    	if (!$stmt) {
        	return array("success" => false, "message" => "Failed to prepare statement.");
    	}
    	$stmt->bind_param("i", $leagueId);
    	$stmt->execute();
    	$result = $stmt->get_result();
    	$messages = $result->fetch_all(MYSQLI_ASSOC);
    	$stmt->close();
    	$db->close();

    	if (empty($messages)) {
        	return array("success" => false, "message" => "No messages found.");
    	}
    	return array("success" => true, "messages" => $messages);
}
?>
