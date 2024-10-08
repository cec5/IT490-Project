<?php

// require doLogin() file
require_once '../php/databaseListener.php';

if (doLogin('test', 'user')) {
	echo "Username exists" . PHP_EOL;
} else {
	echo "Username does not";
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>

