<?php
// Include the RabbitMQ client functions
require_once 'client_rmq_db.php';

// Helper function to get the JWT token from the cookie
function getJwtTokenFromCookie() {
    	return isset($_COOKIE['jwt_token']) ? $_COOKIE['jwt_token'] : null;
}

// Check if the user is logged in by checking the JWT token
$token = getJwtTokenFromCookie();

if (!$token) {
   	// No token found, redirect to the homepage with a message
    	echo "<script>
        	alert('You must be logged in to access this page.');
        	window.location.href = 'index.php';
    	</script>";
    	exit();
}

// Validate the token by sending it to the backend
$request = array();
$request['type'] = 'validate_session';
$request['token'] = $token;

$response = createRabbitMQClientDatabase($request);

if (!$response['success']) {
    	// Token is invalid or expired, redirect to the login page
    	echo "<script>
        	alert('Session expired or invalid. Please log in again.');
        	window.location.href = 'login.php';
    	</script>";
    	exit();
}

// Store the user ID for use in the restricted page
$userId = $response['userId'];
?>
