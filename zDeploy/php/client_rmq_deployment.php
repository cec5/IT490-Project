<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

// Creates a client instance that would be received only by the Deployment Server's Listener
// Use for direct communication with the deployment, usually by DEV to deployment, but also for updating version status from QA or PROD 

function createRabbitMQClientDeployment($request) {
    	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

	if (isset($argv[1])){
	       	$msg = $argv[1];
	}
	else{
		$msg = "default message for deployment server-bound client";
	}

    	$response = $client->send_request($request);

    	return $response;
}
?>
