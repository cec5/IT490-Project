<?php

// Gets SSH Connection
function createSSHConnection($hostname = 'hashbeep.me', $port = 2020) {
	$connection = ssh2_connect($hostname, $port);
    if (ssh2_auth_password($connection, 'deployer', 'password')) {
        echo "Auth success\n";
    } else {
        echo "Auth failure\n";
        die('AUTHENTICATION FAILURE');
    }
}

function sendFile($conn, $localFileLocation, $remoteFileLocation) {
    ssh2_scp_send($conn, $localFileLocation, $remoteFileLocation, 0644);
}

function getFile($conn, $remoteFileLocation, $localFileLocation) {
    ssh2_scp_recv($conn, $remoteFileLocation, $localFileLocation);
}

function sendProperFile($conn) {
    sendFile($conn, '/', '/');
}
?>
