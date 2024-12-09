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

function getLatestStableVersion() {
    $mydb = new mysqli('localhost', 'testUser', '12345', 'testdb');
    
    if ($mydb->connect_error) {
        die("Connection failed: " . $mydb->connect_error);
    }

    try {
        // First, try to find the newest version with TestStatus 'PASS'
        $queryPass = "SELECT * FROM versionHistory WHERE TestStatus = 'PASS' ORDER BY VersionId DESC LIMIT 1";
        $resultPass = $mydb->query($queryPass);
        
        if ($resultPass && $resultPass->num_rows > 0) {
            $latestStableVersion = $resultPass->fetch_assoc();
        } else {
            // If no 'PASS' versions exist, find the newest version with TestStatus 'NEW'
            $queryNew = "SELECT * FROM versionHistory WHERE TestStatus = 'NEW' ORDER BY VersionId DESC LIMIT 1";
            $resultNew = $mydb->query($queryNew);
            
            if ($resultNew && $resultNew->num_rows > 0) {
                $latestStableVersion = $resultNew->fetch_assoc();
            } else {
                // If no suitable version is found, set $latestStableVersion to null
                $latestStableVersion = null;
            }
        }
        return $latestStableVersion;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    } finally {
        $mydb->close();
    }
}

function getLatestNewVersion($input) {
    $mydb = new mysqli('localhost', 'testUser', '12345', 'testdb');
    
    if ($mydb->connect_error) {
        die("Connection failed: " . $mydb->connect_error);
    }

    try {
        // Check if $input has a 'version' property
        if ($isset($input['version'])) {
            $version = $input['version'];

            // Search for the entry with a matching BundleName
            $queryVersion = "SELECT * FROM versionHistory WHERE BundleName = ?";
            $stmt = $mydb->prepare($queryVersion);
            $stmt->bind_param("s", $version);
            $stmt->execute();
            $resultVersion = $stmt->get_result();

            if ($resultVersion && $resultVersion->num_rows > 0) {
                return $resultVersion->fetch_assoc();
            } else {
                return null; // No matching entry found
            }
        }

        // If no specific version is requested, find the latest 'PASS' or 'NEW' version
        $queryPass = "SELECT * FROM versionHistory WHERE TestStatus = 'PASS' ORDER BY VersionId DESC LIMIT 1";
        $resultPass = $mydb->query($queryPass);

        if ($resultPass && $resultPass->num_rows > 0) {
            $latestStableVersion = $resultPass->fetch_assoc();
        } else {
            $queryNew = "SELECT * FROM versionHistory WHERE TestStatus = 'NEW' ORDER BY VersionId DESC LIMIT 1";
            $resultNew = $mydb->query($queryNew);

            if ($resultNew && $resultNew->num_rows > 0) {
                $latestStableVersion = $resultNew->fetch_assoc();
            } else {
                $latestStableVersion = null; // No suitable version found
            }
        }
        return $latestStableVersion;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return null;
    } finally {
        $mydb->close();
    }
}


function approveBuild($input) {
    $mydb = new mysqli('localhost', 'testUser', '12345', 'testdb');
    
    if ($mydb->connect_error) {
        die("Connection failed: " . $mydb->connect_error);
    }

    $build = $input['build'];
    $status = $input['status'];

    if (!in_array($status, ['PASS', 'FAIL', 'NEW'])) {
        echo "Error: Invalid status provided.\n";
        return false;
    }

    try {
        if ($build === "latest") {
            // Find the newest VersionId and update its TestStatus
            $query = "SELECT VersionId FROM versionHistory ORDER BY VersionId DESC LIMIT 1";
            $result = $mydb->query($query);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $versionId = $row['VersionId'];

                $updateQuery = "UPDATE versionHistory SET TestStatus = '$status' WHERE VersionId = $versionId";
                if ($mydb->query($updateQuery)) {
                    return true;
                } else {
                    echo "Error updating status: " . $mydb->error . "\n";
                    return false;
                }
            } else {
                echo "Error: No records found.\n";
                return false;
            }
        } else {
            // Search for a build by BundleName
            $query = "SELECT VersionId FROM versionHistory WHERE BundleName = ?";
            $stmt = $mydb->prepare($query);
            $stmt->bind_param("s", $build);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $versionId = $row['VersionId'];

                $updateQuery = "UPDATE versionHistory SET TestStatus = '$status' WHERE VersionId = $versionId";
                if ($mydb->query($updateQuery)) {
                    return true;
                } else {
                    echo "Error updating status: " . $mydb->error . "\n";
                    return false;
                }
            } else {
                return false; // No matching build found!
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    } finally {
        $mydb->close();
    }
}



?>
