<?php
require_once('client_rmq_deploy.php');
require_once('clusterFunctions.php');

$connection = createSSHConnection();

// prep work
$packageName = generateRandomName();
$fileName = 'code_package_' . getFormattedDateTime() . '.tar.gz';
$fullPath = '/opt/store/' . $packageName . '/' . $fileName;

$request = array();
$request['type'] = 'fileUpload';
$request['filePath'] = $fullPath;
$request['bundleName'] = $packageName;
$request['fileName'] = $fileName;
$request['status'] = 'NEW';

$outie = shell_exec('../../installScripts/upload.sh');
echo $outie;
echo "Xferring to " . $fullPath . "\n";

// exit();
$cmd = 'mkdir -p /opt/store/' . $packageName;
ssh2_exec($connection, $cmd);
$cmd = 'chmod ugo+rw /opt/store/' . $packageName;
ssh2_exec($connection, $cmd);
$xferSuccess = sendFile($connection, '../upload/sendoff.tar.gz', $fullPath);

ssh2_exec($connection, 'exit');

echo var_dump($request);

$returnedResponse = sendToDeployment($request);
echo $returnedResponse;

?>
