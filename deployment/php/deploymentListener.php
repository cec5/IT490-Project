<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

require_once('deploymentFunctions.php');

function requestProcessor($request) {
    	echo "Received request" . PHP_EOL;
    	var_dump($request);
    	if (!isset($request['type'])) {
        	return "ERROR: unsupported message type";
    	}
    	switch ($request['type']) {
			case "doFanout":
				if (!isset($request['target'])) {
					return "Missing deploy target group\n";
				}
				if (($request['target'] != 'qa') && ($request['target'] != 'prod')) {
					return "Invalid deploy target group\n";
				}
				$latestStableVersion = getLatestStableVersion($request);
				echo var_dump($latestStableVersion);
				if ($latestStableVersion != null) {
					$sendOff = array(
						'type' => 'deploy_package',
						'FilePath' => $latestStableVersion['FilePath'],
						'DeploymentIp' => '172.23.193.68',
					);
					echo var_dump($sendOff);
					$ret = doFanout($request['target'], $sendOff);
					// $ret = doFanout("prod", $sendOff);
				} else {
					return "Unable to locate or missing latest stable version in database\n";
				}
				return "Fanout request received\n" . PHP_EOL;
			case "fileUpload":
				$ret = writeFileToDB($request);
				return "File upload msg receieved" . $ret . PHP_EOL;
			case "approveBuild":
				$ret = approveBuild($request);
				return $ret ? "Build approved successfully." . PHP_EOL : "Failed to approve build.\n" . PHP_EOL;
			case "dryAddBundle":
				$ret = dryAddBundle($request);
				return $ret;
			case "newFanout":
				$ret = newFanout($request);
				return $ret;
    	}
    	return array("returnCode" => '0', 'message' => "Deployment Server received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_deploy.ini", "testServer");

echo "Deployment Listener Active" . PHP_EOL;

$server->process_requests('requestProcessor');

echo "Deployment Listener Processed Request" . PHP_EOL;
?>
