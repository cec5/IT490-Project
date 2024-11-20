<?php
require_once('client_rmq_qa.php');

// test script for sending?

$request = array();
$request['type'] = "test";

// $returnedResponse = sendToQaCluster($request);

// echo $returnedResponse;

require_once('deploymentFunctions.php');

$connection = createSSHConnection();
$versionPath = getVersionPath('frontend');
$fullPath = '/home/luke/git/IT490-Project/deployment/store/' . $versionPath . 'example.tar.gz';
echo "WARNING: Uploading to the following directory. Probably won't work, make sure 'deployer' or their group has access to the directory, or change the target directory in simulatePush.php!\n";
$fullPath = '/opt/' . $versionPath . 'example.tar.gz';
echo "Xferring to " . $fullPath . "\n";
$xferSuccess = sendFile($connection, '../example.tar.gz', $fullPath);

ssh2_exec($connection, 'exit');

echo "If that failed, please run the following:\n\n";
echo "cd /opt; mkdir store; mkdir store/latest; mkdir store/latest/dev; mkdir store/latest/dev/frontend; chmod o+rw -R store;\n";
echo "\nAnd then remind Luke to write a script for that that also chown's it to deployer. Thanks!\n";
?>
