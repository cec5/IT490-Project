<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

function requestProcessor($request) {
    	echo "Received request" . PHP_EOL;
    	var_dump($request);
    	if (!isset($request['type'])) {
        	return "ERROR: unsupported message type";
    	}
    	switch ($request['type']) {
        	case "test":
            		return "Test message received from Deployment Server" . PHP_EOL;
        	//case "deploy_package":
        		//return deployPackage($request['package_info']);
    	}
    	return array("returnCode" => '0', 'message' => "Deployment Server received request and processed");
}

$routing_key = "deploy"; // Ensures that this listener only handles messages with key "deploy"

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

echo "Deployment Listener Active" . PHP_EOL;

$server->process_requests('requestProcessor', $routing_key);

echo "Deployment Listener Processed Request" . PHP_EOL;
exit();
?>
