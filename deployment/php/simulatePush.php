<?php
require_once('client_rmq_deploy.php');
require_once('deploymentFunctions.php');

// test script for sending?

$connection = createSSHConnection();
$versionPath = getVersionPath('frontend');

// $fullPath = '/home/luke/git/IT490-Project/deployment/store/' . $versionPath . 'example.tar.gz';
// echo "WARNING: Uploading to the following directory. Probably won't work, make sure 'deployer' or their group has access to the directory, or change the target directory in simulatePush.php!\n";
// $fullPath = '/opt/store/' . $versionPath . 'example.tar.gz';

$fileName = 'code_package_' . getFormattedDateTime() . '.tar.gz';
$fullPath = '/opt/store/' . $versionPath . $fileName;
echo "Xferring to " . $fullPath . "\n";
$xferSuccess = sendFile($connection, '../example.tar.gz', $fullPath);

ssh2_exec($connection, 'exit');


$request = array();
$request['type'] = 'fileUpload';
$request['filePath'] = $fullPath;
$request['bundleName'] = generateRandomName();
$request['fileName'] = $fileName;
$request['status'] = 'NEW';

echo var_dump($request);

$returnedResponse = sendToDeployment($request);
echo $returnedResponse;

// wait?

$request = array();
$request['type'] = 'doFanout';
// $request['target'] = 'qa';
$request['target'] = 'prod';

echo var_dump($request);

$returnedResponse = sendToDeployment($request);
echo $returnedResponse;

/*
echo "If that failed, please run the following:\n\n";
echo "cd /opt; mkdir store; mkdir store/latest; mkdir store/latest/dev; mkdir store/latest/dev/frontend; chmod o+rw -R store;\n";
echo "\nAnd then remind Luke to write a script for that that also chown's it to deployer. Thanks!\n";
*/
?>
