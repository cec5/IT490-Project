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
        switch ($request['type']){
 		case "test":
			return "test message received from the database server".PHP_EOL;
		case "login":
			return doLogin($request['username'], $request['password']);
		case "register":
	  		return doRegister($request['username'], $request['email'], $request['password']);
        	case "validate_session":
            		return validateToken($request['token']);
	  	case "create_league":
	  		return createLeague($request['user_id'], $request['league_name']);
        	case "join_league":
            		return joinLeague($request['user_id'], $request['league_id']);
            	case "leave_league":
            		return leaveLeague($request['user_id'], $request['league_id']);
            	case "get_user_leagues":
    			return getUserLeagues($request['user_id']);
    		case "validate_league_access":
    			return validateLeagueAccess($request['user_id'], $request['league_id']);
    		case "get_leaderboard":
    			return getLeaderboard($request['league_id']);
    		case "get_all_leagues":
    			return getAllLeagues();
    		case "get_league_name":
    			return getLeagueName($request['league_id']);
        	case "post_message":
            		return postMessage($request['user_id'], $request['league_id'], $request['message']);
        	case "get_messages":
            		return getMessages($request['league_id']);
            	case "get_unselected_players":
    			return getUnselectedPlayers($request['league_id'], $request['filters']);
    		case "draft_player":
    			return draftPlayer($request['user_id'], $request['league_id'], $request['player_id'], $request['status']);
  	}
	return array("returnCode" => '0', 'message'=>"Database Server received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_db.ini","testServer");

echo "Database Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "Database Listener Processed Request".PHP_EOL;
exit();
?>
