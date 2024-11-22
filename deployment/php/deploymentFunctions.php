<?php

$configIni = parse_ini_file("../config/source.ini");
$deployUsername = $configIni["username"];
$deployPassword = $configIni["password"];
$deployDefaultHostname = $configIni["hostname"];
$deployDefaultPort = $configIni["port"];

function generateRandomName() {
    // Lists of words
    $adjectives = [
        'fast', 'silly', 'happy', 'lazy', 'brave', 'gentle', 'kind', 'smart', 'witty', 'calm'
    ];
    $colors = [
        'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'black', 'white', 'pink', 'brown'
    ];
    $animals = [
        'cat', 'dog', 'fox', 'bear', 'tiger', 'lion', 'wolf', 'rabbit', 'eagle', 'panda'
    ];

    // Pick one word from each list randomly
    $adjective = $adjectives[array_rand($adjectives)];
    $color = $colors[array_rand($colors)];
    $animal = $animals[array_rand($animals)];

    // Combine the words with a separator (e.g., '-')
    return "{$adjective}-{$color}-{$animal}";
}

function getFormattedDateTime() {
    $currentDateTime = new DateTime();
    $formattedDateTime = $currentDateTime->format('Ymd-His');
    return $formattedDateTime;
}


// Gets SSH Connection
function createSSHConnection($hostname = 'localhost', $port = 22) {
    $configIni = parse_ini_file("../config/source.ini");
    $deployUsername = $configIni["username"];
    $deployPassword = $configIni["password"];
	$connection = ssh2_connect($hostname, $port);
    if (ssh2_auth_password($connection, $deployUsername, $deployPassword)) {
        echo "Auth success\n";
    } else {
        echo "Auth failure\n";
        die('AUTHENTICATION FAILURE');
    }
    return $connection;
}

function sendFile($conn, $localFileLocation, $remoteFileLocation) {
    ssh2_scp_send($conn, $localFileLocation, $remoteFileLocation);
}

function getFile($conn, $remoteFileLocation, $localFileLocation) {
    ssh2_scp_recv($conn, $remoteFileLocation, $localFileLocation);
}

function sendProperFile($conn) {
    sendFile($conn, '/', '/');
}

function getVersionPath($job, $version = 'latest') {
    return $version . '/dev/' . $job . '/';
}

function storeBundle($requestObj) {
    //
}

function writeFileToDB($requestObj) {
    $mydb = new mysqli('localhost','testUser','12345','testdb');
    $filePath = $requestObj['filePath'];
    $bundleName = $requestObj['bundleName'];
    $testStatus = $requestObj['status'];

    try{
		$query = "INSERT INTO versionHistory (BundleName, TestStatus, FilePath) VALUES ('$bundleName', '$testStatus', '$filePath')";
		$response = $mydb->query($query);
		
		if ($response) {
			return "IT WORKS";
		} else {
			throw new Exception("Database error: " . $mydb->error);
		}
    } catch (Exception $e) {
		return "Error: " . $e->getMessage();
	}
}

function doFanout($toWho, $sendObj) {
    switch ($toWho) {
        case "prod":
            require_once("client_rmq_prod.php");
            //
            $ret = sendToProdCluster($sendObj);
            return $ret;
        case "qa":
            require_once("client_rmq_qa.php");
            //
            $ret = sendToQaCluster($sendObj);
            return $ret;
        default:
            return;
    }
}

?>
