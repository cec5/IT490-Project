<?php
require_once('client_rmq_deploy.php');
require_once('clusterFunctions.php');

$connection = createSSHConnection();

$request = array();
$request['type'] = 'approveBuild';
$request['build'] = 'latest';
$request['status'] = 'FAIL';

echo var_dump($request);

$returnedResponse = sendToDeployment($request);
echo $returnedResponse;

exit();
?>
