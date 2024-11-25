<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLibFanout.inc');

require_once('clusterFunctions.php');

function requestProcessor($request) {
    	echo "Received request:" . PHP_EOL;
    	var_dump($request);

    	if (!isset($request['type'])) {
        	return "ERROR: unsupported message type";
    	}

   	switch ($request['type']) {
        	case "test":
				return "Test message received by prod cluster listener.";
        	case "deploy_package":
				// Simulate deployment processing, write and call a seperate function that would get the files from deployment and overwrite/replace the current ones
				$success = getDeploymentFromRequest($request);
				return $success;
    	}
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_PROD_Cluster.ini", "testServer");

echo "PROD Cluster Listener Active" . PHP_EOL;

$server->process_requests('requestProcessor');

echo "Cluster Listener Processed Request" . PHP_EOL;
?>
