<?php

require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLibFanout.inc');

function createRabbitMQClientLogFanout($request){

	//Use this to handle rmq logging fanout requests
	
	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_log.ini","testServer");

	if (isset($argv[1])){
	       	$msg = $argv[1];
	}
	else{
		$msg = "default message for logging fanout";
	}

	$response = $client->send_request($request);

	return $response;
}
?>
