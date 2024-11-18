<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLibFanout.inc');

// Call this when sending messages to QA cluster

function sendToQaCluster($request) {
     	$client = new rabbitMQClient("../rabbitmq_files/rabbitMQ_QA_Cluster.ini", "testServer");
    		
    	if (isset($argv[1])){
	       	$msg = $argv[1];
	}
	else{
		$msg = "default message for qa cluster-bound message";
	}
    	
    	$response = $client->send_request($request);

    	return $response;
}
?>
