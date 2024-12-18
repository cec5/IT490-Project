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
function createSSHConnection($hostname = '172.23.193.68', $port = 22) {
    $configIni = parse_ini_file("../config/source.ini");
    $deployUsername = $configIni["username"];
    $deployPassword = $configIni["password"];
	$connection = ssh2_connect($hostname, $port);
    var_dump($connection);
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

function getVersionPath($job, $version = 'latest') {
    return $version . '/dev/' . $job . '/';
}

function getDeploymentFromRequest($requestObj) {
    $host = $requestObj['DeploymentIp'];
    $filePath = $requestObj['FilePath'];
    $fileName = basename($filePath);
    $connection = createSSHConnection($host);
    echo var_dump($connection);
    if ($connection != NULL) {
        $localFilePath = '../bundles/' . $fileName;
        $xferSuccess = getFile($connection, $filePath, $localFilePath);
        ssh2_exec($connection, 'exit');
        // TODO: run deploy.sh on the newly deployed code_package
        // $filename stores the name of the file, but $localFilePath stores its relative path to this file
        // dew it
        extractAndMoveTarFile($fileName);
        return true;
    } else {
        return NULL;
    }
}

function extractAndMoveTarFile($fileName) {
    // Define the paths
    $repoRootDir = '../../';  // Path to the repository root (adjust if needed)
    $filePath = "/zDeploy/bundles/$fileName";  // Path to the tar.gz file
    $newFilePath = "$repoRootDir/$fileName";  // Destination path in the repo root

    $preFilePath = '../bundles/'.$fileName;

    // Check if the tar.gz file exists
    if (!file_exists($preFilePath)) {
        echo "Error: $fileName does not exist.\n";
        return false;
    }

    // Step 1: Move the .tar.gz file to the repository's root directory
    if (!rename($preFilePath, '../../' . $fileName)) {
        echo "Error: Failed to move $fileName to the repository root.\n";
        return false;
    }

    // Step 2: Move all current directories in the root folder to an 'old' folder
    $oldDir = "$repoRootDir" . "old";
    $outla = shell_exec('rm -rf ' . $oldDir);
    var_dump($outla);
    if (!is_dir($oldDir)) {
        // system('rm -rf -- ' . escapeshellarg($oldDir), $retval);
        mkdir($oldDir);  // Create the 'old' folder if it doesn't exist
    } else if (is_dir($oldDir)) {
        // rmrf($oldDir);
        shell_exec('rm -rf ' . $oldDir);
    }

    // Get a list of directories in the repository root (excluding 'old' and the tar.gz file)
    $directories = glob("$repoRootDir/*", GLOB_ONLYDIR);
    foreach ($directories as $dir) {
        // Avoid moving 'old' and the tar.gz file itself
        if (basename($dir) !== 'old' && basename($dir) !== $fileName && basename($dir) !== 'zDeploy') {
            $newDir = "$oldDir/" . basename($dir);
            if (!rename($dir, $newDir)) {
                echo "Error: Failed to move directory $dir to $oldDir.\n";
                return false;
            }
        }
    }

    // Step 3: Unpack the tar.gz file into the repository root directory
    $command = "tar -xzvf $newFilePath -C $repoRootDir";
    exec($command, $output, $return_var);

    // Check if extraction was successful
    if ($return_var === 0) {
        echo "Extraction of $fileName completed successfully.\n";
        return true;
    } else {
        echo "Error: Extraction failed.\n";
        return false;
    }
}

function rmrf($dir) {
    foreach (glob($dir) as $file) {
        if (is_dir($file)) { 
            rmrf("$file/*");
            rmdir($file);
        } else {
            unlink($file);
        }
    }
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


?>
