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

// https://stackoverflow.com/a/2638272/22172110

function getRelativePath($from, $to) {
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
    $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
    $from = str_replace('\\', '/', $from);
    $to   = str_replace('\\', '/', $to);

    $from     = explode('/', $from);
    $to       = explode('/', $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }
    return implode('/', $relPath);
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

    $bundleName = $input['bundleName'];
    $version = $input['version'];
    $status = $input['status'];

    if (!in_array($status, ['PASS', 'FAIL', 'NEW'])) {
        echo "Error: Invalid status provided.\n";
        return false;
    }

    try {
        $stmt = "UPDATE versionHistory SET TestStatus = '$status' WHERE BundleName = '$bundleName' AND VersionId = '$version'";
	    $res = $mydb->query($stmt);
	    return $res;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    } finally {
        $mydb->close();
    }
}


function getLatestByBundle($bundleName) {
    $mydb = new mysqli('localhost', 'testUser', '12345', 'testdb');
    
    if ($mydb->connect_error) {
        die("Connection failed: " . $mydb->connect_error);
    }

    $sql = "SELECT ifnull((SELECT MAX(VersionId) FROM versionHistory WHERE BundleName = '$bundleName'), 0) AS versionNumber";
    $stmt = $mydb->query($sql);

    $result = $stmt->fetch_assoc();
    if ($result == null) {
        return 0;
    } else {
        $version = $result["versionNumber"];
    }

    $stmt->close();
    $mydb->close();
    return $version + 1;
}

function dryAddBundle($requestObj) {
    $mydb = new mysqli('localhost','testUser','12345','testdb');
    $bundleName = $requestObj['bundleName'];
    $bundlePath = $requestObj['bundlePath'];
    $bundleMachine = $requestObj['bundleMachine'];
    $currentBundleVersion = getLatestByBundle($bundleName);

    $assembledPath = "/opt/store/$bundleName-v$currentBundleVersion";
    
    //bundleName" => $bundleName, "bundlePath" => $bundlePath, "bundleMachine" => $bundleMachine

    try{
		$query = "INSERT INTO versionHistory (VersionId, BundleName, TestStatus, TargetMachine, FilePath) VALUES ('$currentBundleVersion', '$bundleName', 'NEW', '$bundleMachine', '$assembledPath')";
		$response = $mydb->query($query);
		
		if ($response) {
			return array("version" => $currentBundleVersion);
		} else {
			throw new Exception("Database error: " . $mydb->error);
		}
    } catch (Exception $e) {
		return "Error: " . $e->getMessage();
	}
}

function newFanout($requestObj) {
    $mydb = new mysqli('localhost','testUser','12345','testdb');
    $bundleName = $requestObj["bundleName"];
    $cluster = $requestObj["cluster"];
    $ipMapsWithMainUsers = [
        "QA" => [
            "FRONTEND" => "vboxuser@172.23.213.242",
            "DMZ" => "cortez@172.23.96.14",
            "BACKEND" => "dane-b@172.23.0.118"
        ],
        "PROD" => [
            "FRONTEND" => "vboxuser@172.23.19.155",
            "DMZ" => "cortez@172.23.138.156",
            "BACKEND" => "dane-b@172.23.90.234"
        ]
    ];
    $ipMaps = [
        "QA" => [
            "FRONTEND" => "172.23.213.242",
            "DMZ" => "172.23.96.14",
            "BACKEND" => "172.23.0.118"
        ],
        "PROD" => [
            "FRONTEND" => "172.23.19.155",
            "DMZ" => "172.23.138.156",
            "BACKEND" => "172.23.90.234"
        ]
    ];
    $query = "SELECT * FROM versionHistory WHERE VersionId IN (SELECT MAX(VersionId) FROM versionHistory WHERE BundleName = '$bundleName') AND BundleName = '$bundleName'";
    // some case to filter to only PASSing builds
    $res = $mydb->query($query);
    $data = $res->fetch_assoc();
    if ($data != null) {
        $version = $data["VersionId"];
        $targetMachine = $data["TargetMachine"];
        echo var_dump($data);
        echo var_dump($version);
        echo var_dump($targetMachine);
        $bundlePath = $data["FilePath"]; // slightly misleading, THIS IS THE STORED PATH ON DEPLOYMENT
        //
        $location = $ipMaps[$cluster][$targetMachine];
        echo $location;
    
        // LOCALHOST OVERRIDE
        // $location = "172.23.193.68";
        //
        $conn = createSSHConnection($location, 22);
    
        $allBundles = parse_ini_file('../config/bundles.ini', true);
        $filePath = $allBundles[$bundleName]["BUNDLE_PATH"]; // THIS IS THE LOCAL/CLIENT FILE PATH
        // $exec = ssh2_exec($conn, "ls $filePath"); 
        // $exec = ssh2_exec($conn, "chmod o+rwx -R /home");
        // echo var_dump($exec);
    
        echo "rsync -av $bundlePath/ deployer@$location:$filePath/";
        // $res = exec("whoami");
        echo var_dump($res);
        $res = exec("rsync -av $bundlePath/ deployer@$location:$filePath/");

        // "rsync -e "ssh" -av deployer@172.23.193.68:/opt/store/FrontendPhp-v2/ /home/vboxuser/IT490-Project/frontend/php/"
        // $res = ssh2_exec($conn, "rsync deployer@172.23.193.68:$bundlePath/ $filePath/");

        // echo "rsync -av deployer@172.23.193.68:$bundlePath/ $filePath/";
        // $res = ssh2_exec($conn, "rsync -a deployer@172.23.193.68:$bundlePath/ $filePath/");
        // echo var_dump("rsync -av deployer@172.23.193.68:$bundlePath/ $filePath/");
        // echo var_dump($res);
        // echo var_dump($res);

        /*
        $errs = ssh2_fetch_stream($res, 0);
        stream_set_blocking($errs, true);
        $result_err = stream_get_contents($errs);
        echo 'stderr: ' . $result_err;
        */
    
    
        return true;
    } else {
        return false;
    }

    die();
}

function requestVersion($requestObj) {
    $mydb = new mysqli('localhost','testUser','12345','testdb');
    $bundleName = $requestObj["bundleName"];
    $cluster = $requestObj["cluster"];
    $version = $requestObj["version"];
    $ipMaps = [
        "QA" => [
            "FRONTEND" => "172.23.213.242",
            "DMZ" => "172.23.96.14",
            "BACKEND" => "172.23.0.118"
        ],
        "PROD" => [
            "FRONTEND" => "172.23.19.155",
            "DMZ" => "172.23.138.156",
            "BACKEND" => "172.23.90.234"
        ]
    ];
    $query = "SELECT * FROM versionHistory WHERE VersionId IN (SELECT MAX(VersionId) FROM versionHistory WHERE BundleName = '$bundleName' AND VersionId = '$version') AND BundleName = '$bundleName'";
    // some case to filter to only PASSing builds
    $res = $mydb->query($query);
    $data = $res->fetch_assoc();
    if ($data != null) {
        $targetMachine = $data["TargetMachine"];
        $bundlePath = $data["FilePath"]; // slightly misleading, THIS IS THE STORED PATH ON DEPLOYMENT
        $location = $ipMaps[$cluster][$targetMachine];
    
        // LOCALHOST OVERRIDE
        // $location = "172.23.193.68";
    
        $conn = createSSHConnection($location, 22);
    
        $allBundles = parse_ini_file('../config/bundles.ini', true);
        $filePath = $allBundles[$bundleName]["BUNDLE_PATH"]; // THIS IS THE LOCAL/CLIENT FILE PATH
    
        exec("rsync -av $bundlePath/ deployer@$location:$filePath");
        return true;
    } else {
        return false;
    }
}

function rollback($requestObj) {
    $mydb = new mysqli('localhost','testUser','12345','testdb');
    $bundleName = $requestObj["bundleName"];
    $cluster = $requestObj["cluster"];
    $ipMaps = [
        "QA" => [
            "FRONTEND" => "172.23.213.242",
            "DMZ" => "172.23.96.14",
            "BACKEND" => "172.23.0.118"
        ],
        "PROD" => [
            "FRONTEND" => "172.23.19.155",
            "DMZ" => "172.23.138.156",
            "BACKEND" => "172.23.90.234"
        ]
    ];
    $query = "SELECT * FROM versionHistory WHERE VersionId IN (SELECT MAX(VersionId) FROM versionHistory WHERE BundleName = '$bundleName' AND TestStatus = 'PASS') AND BundleName = '$bundleName'";
    $res = $mydb->query($query);
    $data = $res->fetch_assoc();
    $version = $data["VersionId"];
    $targetMachine = $data["TargetMachine"];
    $bundlePath = $data["FilePath"]; // slightly misleading, THIS IS THE STORED PATH ON DEPLOYMENT
    //
    $location = $ipMaps[$cluster][$targetMachine];
    echo $location;

    // LOCALHOST OVERRIDE. UNCOMMENT!!!!!!!!!
    $location = "172.23.193.68";
    //
    $conn = createSSHConnection($location, 22);

    // figure out the remote path to be replaced
    $allBundles = parse_ini_file('../config/bundles.ini', true);
    $filePath = $allBundles[$bundleName]["BUNDLE_PATH"]; // THIS IS THE LOCAL/CLIENT FILE PATH
    // $exec = ssh2_exec($conn, "rm -rf $filePath");
    $exec = ssh2_exec($conn, "ls $filePath");

    echo "rsync -av $bundlePath/ deployer@$location:$filePath";

    exec("rsync -av $bundlePath/ deployer@$location:$filePath");

    return true;

    die();
}



?>
