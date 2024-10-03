<?php
require_once('client_rmq_db.php');

//simple test script to see if client_rmq_db is functional and can send/receive messages to the database listener

$request = array();
$request['type'] = "test";

$returnedResponse = createRabbitMQClientDatabase($request);

echo $returnedResponse;
?>
