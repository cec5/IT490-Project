<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');
require_once('databaseFunctions.php');

function requestProcessor($request){
	echo "received request".PHP_EOL;
	var_dump($request);
	if(!isset($request['type'])){
		return "ERROR: unsupported message type";
	}
	
  	// Check if the request includes a token for validation
  	if (isset($request['token'])) {
  		// Validate the token
        	$isValid = validateToken($request['token']);
        	if (!$isValid) {
        		return array("success" => false, "message" => "Invalid or expired token.");
        	}
        	// Extract the user ID from the token
        	$userId = $isValid->sub;
        } else {
        	return array("success" => false, "message" => "Authentication token required.");
        }
        
        switch ($request['type']){
 		case "test":
			return "test message recieved from the database server".PHP_EOL;
		case "login":
			return doLogin($request['username'], $request['password']);
		case "register":
	  		$result = doRegister($request['username'], $request['email'], $request['password']);
	  		return $result; // Return the array with success and message
        	case "validate_session":
            		return validateToken($request['sessionId']);
	  	case "create_league":
	  		return createLeague($userId, $request['league_name']);
        	case "join_league":
            		return joinLeague($userId, $request['league_id']);
            	case "leave_league":
            		return leaveLeague($userId, $request['league_id']);
        	case "post_message":
            		return postMessage($userId, $request['league_id'], $request['message']);
        	case "get_messages":
            		return getMessages($request['league_id']);
  	}
	return array("returnCode" => '0', 'message'=>"Database Server received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_db.ini","testServer");

echo "Database Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "Database Listener Processed Request".PHP_EOL;
exit();
?>
