<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLibFanout.inc');

function writeLog($logMessage, $logFile) {
    	file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

function requestProcessor($request) {
    	echo "received request" . PHP_EOL;
    	var_dump($request);

    	if (!isset($request['type'])) {
        	return "ERROR: unsupported message type";
    	}

    	switch ($request['type']) {
        	case "test":
            		return "test message received from log listener" . PHP_EOL;
       		case "backend":
            		$logFile = 'backend.log';

            		if (isset($request['timestamp'], $request['source'], $request['message'])) {
                		$logMessage = "[{$request['timestamp']}] {$request['source']}: {$request['message']}";
                		writeLog($logMessage, $logFile);
            		} else {
                		writeLog("Invalid log message format received: " . json_encode($request), $logFile);
            		}
            		break;
    	}
    	return array("returnCode" => '0', 'message' => "Logging Listener received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_log.ini", "testServer");

echo "Log Listener Active" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "Log Listener Processed Logs" . PHP_EOL;
?>
