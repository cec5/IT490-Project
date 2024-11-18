<?php
require_once('client_rmq_qa.php');

// test script for testing deployment RMQ

$request = array();
$request['type'] = "test";

$returnedResponse = sendToQaCluster($request);

echo $returnedResponse;
?>
