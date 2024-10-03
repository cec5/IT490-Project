<?php

require_once('../rabbitmqphp_example/path.inc');
require_once('../rabbitmqphp_example/get_host_info.inc');
require_once('../rabbitmqphp_example/rabbitMQLib.inc');

function createRabbitMQClientDatabase($request){

	//Largely copied from the test client
	//Use this to handle rmq requests to the database
	//Still using testServer for now as it works but may change later
	
	$client = new rabbitMQClient("../rabbitmqphp_example/rabbitMQ_db.ini","testServer");

	if (isset($argv[1])){
	       	$msg = $argv[1];
	}
	else{
		$msg = "default message for the database-bound  client";
	}

	$response = $client->send_request($request);

	return $response;
}
?>
