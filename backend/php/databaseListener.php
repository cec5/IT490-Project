<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');
require_once('client_rmq_log.php');
require_once('databaseFunctions.php');

function requestProcessor($request){
	echo "received request".PHP_EOL;
	// Log the request locally and to other servers
    	logToFile("Received request: " . json_encode($request));
	var_dump($request);
	if(!isset($request['type'])){
		return "ERROR: unsupported message type";
	}
        switch ($request['type']){
 		case "test":
			return "test message received from the database server".PHP_EOL;
		case "login":
			$store = doLogin($request['username'], $request['password']);
			responseLog($store);
			return $store;
		case "register":
	  		$store = doRegister($request['username'], $request['email'], $request['password']);
	  		responseLog($store);
			return $store;
        	case "validate_session":
            		$store = validateToken($request['token']);
            		responseLog($store);
			return $store;
	  	case "create_league":
	  		$store = createLeague($request['user_id'], $request['league_name']);
	  		responseLog($store);
			return $store;
        	case "join_league":
            		$store = joinLeague($request['user_id'], $request['league_id']);
            		responseLog($store);
			return $store;
            	case "leave_league":
            		return leaveLeague($request['user_id'], $request['league_id']);
            	case "get_user_leagues":
    			$store = getUserLeagues($request['user_id']);
    			responseLog($store);
    			return $store;
    		case "validate_league_access":
    			$store = validateLeagueAccess($request['user_id'], $request['league_id']);
    			responseLog($store);
    			return $store;
    		case "get_leaderboard":
    			$store = getLeaderboard($request['league_id']);
    			responseLog($store);
    			return $store;
    		case "get_all_leagues":
    			$store = getAllLeagues();
    			responseLog($store);
    			return $store;
    		case "get_league_name":
    			$store = getLeagueName($request['league_id']);
    			responseLog($store);
    			return $store;
        	case "post_message":
            		$store = postMessage($request['user_id'], $request['league_id'], $request['message']);
            		responseLog($store);
    			return $store;
        	case "get_messages":
            		$store = getMessages($request['league_id']);
            		responseLog($store);
    			return $store;
            	case "get_unselected_players":
    			$store = getUnselectedPlayers($request['league_id'], $request['filters']);
    			responseLog($store);
    			return $store;
    		case "draft_player":
    			$store = draftPlayer($request['user_id'], $request['league_id'], $request['player_id'], $request['status']);
    			responseLog($store);
    			return $store;
		case "get_user_roster":
    			$store = getUserRoster($request['user_id'], $request['league_id']);
    			responseLog($store);
    			return $store;
		case "swap_players":
    			$store = swapPlayers($request['user_id'], $request['league_id'], $request['active_player_id'], $request['reserve_player_id']);
    			responseLog($store);
    			return $store;
		case "promote_reserve":
    			$store = promoteReservePlayer($request['user_id'], $request['league_id'], $request['reserve_player_id']);
    			responseLog($store);
    			return $store;
		case "remove_reserve":
    			$store = removeReservePlayer($request['user_id'], $request['league_id'], $request['reserve_player_id']);
    			responseLog($store);
    			return $store;
    		case "propose_trade":
            		$store = proposeTrade($request['proposing_user_id'], $request['receiving_user_id'], $request['league_id'], $request['proposed_player_id'], $request['requested_player_id']);
            		responseLog($store);
    			return $store;
        	case "accept_trade":
            		$store = acceptTrade($request['trade_id']);
            		responseLog($store);
    			return $store;
        	case "update_trade_status":
            		$store = updateTradeStatus($request['trade_id'], $request['status']);
            		responseLog($store);
    			return $store;
        	case "get_pending_trades":
            		$store = getPendingTrades($request['user_id'], $request['league_id']);
            		responseLog($store);
    			return $store;
            	case "get_user_reserve_players":
            		$store = getUserReservePlayers($request['user_id'], $request['league_id']);
            		responseLog($store);
    			return $store;
            	case "get_league_members":
    			$store = getLeagueMembers($request['league_id'], $request['user_id'] ?? null);
    			responseLog($store);
    			return $store;
    		case "get_other_reserve_players":
   			$store = getOtherReservePlayers($request['user_id'], $request['league_id']);
   			responseLog($store);
    			return $store;
  	}
  	// Log the response locally
    	logToFile("Response: " . json_encode($response));
	return array("returnCode" => '0', 'message'=>"Database Server received request and processed");
}

// Function to store logging info locally
function logToFile($message) {
    	$logFile = '../logging/backend.log';
    	$timestamp = date('Y-m-d H:i:s');
    	file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    	distributeLogs($message, $timestamp);
}

function distributeLogs($message, $timestamp) {
    	$logRequest = array();
    	$logRequest['type'] = 'backend';
    	$logRequest['message'] = $message;
    	$logRequest['source'] = 'backend';
    	$logRequest['timestamp'] = $timestamp;
    	createRabbitMQClientLogFanout($logRequest);
}

function responseLog($array){
	try {
		logToFile("Response: " . json_encode($array['message']));
	} catch (Exception $e){ echo "No message or unknown error".PHP_EOL;}
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_db.ini","testServer");

echo "Database Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "Database Listener Processed Request".PHP_EOL;
exit();
?>
