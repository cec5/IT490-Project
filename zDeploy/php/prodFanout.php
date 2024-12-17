<?php
require_once('client_rmq_deploy.php');
require_once('clusterFunctions.php');

$connection = createSSHConnection();

$request = array();
$request['type'] = 'doFanout';
// $request['target'] = 'qa';
$request['target'] = 'prod';

echo var_dump($request);

$returnedResponse = sendToDeployment($request);
echo $returnedResponse;

?>
