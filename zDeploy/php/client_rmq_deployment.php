<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

// Creates a client instance that would be received only by the Deployment Server's Listener, I guess it would primarily be used by dev vm's to start the process of sending over files

function createRabbitMQClientDeployment($request, $routingKey = "deploy") {
    	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

    	$request['routing_key'] = $routingKey;

    	if (!isset($request['message'])) {
        	$request['message'] = "Default message for deployment-bound client";
    	}

    	$response = $client->send_request($request);

    	return $response;
}
?>
