<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

function requestProcessor($request) {
    	echo "Received request:" . PHP_EOL;
    	var_dump($request);

    	if (!isset($request['type'])) {
        	return "ERROR: unsupported message type";
    	}

   	switch ($request['type']) {
        	case "test":
            		return "Test message received by the cluster listener.";
        	case "deploy_package":
            		// Simulate deployment processing, write and call a seperate function that would get the files from deployment and overwrite/replace the current ones
    	}
}

//SET # AS EITHER "qa" OR "prod" DEPENDING ON THE CLUSTER THIS LISTENER IS IN (dev does not need)
$routingKey = "deploy.#";

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

$server->routing_key = $routingKey;

echo "Cluster Listener Active for Routing Key: " . $routingKey . PHP_EOL;

$server->process_requests('requestProcessor');

echo "Cluster Listener Processed Request" . PHP_EOL;

exit();
?>
