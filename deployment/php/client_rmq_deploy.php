<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

// Creates a client instance on deployment queue/exchange that would be recieved by deployment server. really just for testing
// 1:1, not fanout

function sendToDeployment($request) {
    	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");
    		
    	if (isset($argv[1])){
	       	$msg = $argv[1];
	}
	else{
		$msg = "default message";
	}
    	
    	$response = $client->send_request($request);

    	return $response;
}
?>
