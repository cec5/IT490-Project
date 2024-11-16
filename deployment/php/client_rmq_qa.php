<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

// Call this when sending messages to QA cluster

function sendToQaCluster($request) {
    	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

    	$client->routing_key = "deploy.qa";

    	$response = $client->send_request($request);

    	return $response;
}
?>
