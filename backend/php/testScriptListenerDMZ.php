<?php
require_once('client_rmq_dmz.php');

//simple test script to see if client_rmq_dmz is functional and can send/receive messages to the dmz listener
//works exactly the same as the original script

$request = array();
$request['type'] = "test";

$returnedResponse = createRabbitMQClientDMZ($request);

echo $returnedResponse;
?>
