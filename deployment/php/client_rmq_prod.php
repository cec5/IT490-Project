<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLibFanout.inc');

// Creates a client instance on deployment queue/exchange that would be recieved by the prod cluster

function sendToProdCluster($request) {
    	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_PROD_Cluster.ini", "testServer");
    		
    	if (isset($argv[1])){
	       	$msg = $argv[1];
	}
	else{
		$msg = "default message for prod cluster-bound message";
	}
    	
    	$response = $client->send_request($request);

    	return $response;
}
?>
