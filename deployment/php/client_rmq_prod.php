<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

// Creates a client instance on deployment queue/exchange that would be recieved by the prod cluster

function sendToProdCluster($request) {
    	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

    	$client->routing_key = "deploy.prod";

    	$response = $client->send_request($request);

    	return $response;
}
?>
