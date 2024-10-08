<?php

$server = "localhost"; //any host
$username = "admin";
$password = "pass";
$db = "project";

$conn = new mysqli($server, $username, $password, $db);

if (checkConnection($conn)) {
	echo "db connected";
} else {
	echo "db fail";
}	


function checkConnection($conn) {
	if ($conn->connect_error) { //check if connection fails
		return false;
	}
	return true;
}


$conn->close();
?>
