<?php
require_once('client_rmq_deployment.php');

// test script for testing deployment RMQ

$request = array();
$request['type'] = "test";

$returnedResponse = createRabbitMQClientDeployment($request);

echo $returnedResponse;
?>
