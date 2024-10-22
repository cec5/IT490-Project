<?php
// Include the RabbitMQ client
require_once 'client_rmq_db.php';
require_once 'header.php';

// Helper function to get the JWT token from the cookie
function getJwtTokenFromCookie() {
    	if (isset($_COOKIE['jwt_token'])) {
        	return $_COOKIE['jwt_token'];
    	}
    	return null;
}

// Check if a JWT token exists in the cookies
$token = getJwtTokenFromCookie();

if ($token) {
    	// Create a request to validate the token
    	$request = array();
    	$request['type'] = 'validate_session';
    	$request['token'] = $token;

    	// Send the request to RabbitMQ
    	$response = createRabbitMQClientDatabase($request);

    	// Check the response and output the validation result
    	if ($response['success']) {
       		echo "Token is valid. User ID: " . $response['userId'];
    	} else {
        	echo "Token is invalid or expired.";
    	}
} else {
    	echo "No token found in cookies.";
}
?>

