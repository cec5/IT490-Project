<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');
require_once('dmzFunctions.php');

function requestProcessor($request){
  	echo "received request".PHP_EOL;
  	var_dump($request);
  	if(!isset($request['type'])){
    		return "ERROR: unsupported message type";
  	}
  	switch ($request['type']){
 	 	case "test":
		 	return "test message recieved from DMZ Server".PHP_EOL;
		case "get_league_players":
			return getPremierLeaguePlayers();
  	}
  	return array("returnCode" => '0', 'message'=>"DMZ Server received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_dmz.ini","testServer");

echo "DMZ Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "DMZ Listener Processed Request".PHP_EOL;
exit();
?>
