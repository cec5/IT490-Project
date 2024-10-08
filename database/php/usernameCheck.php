<?php

// require doLogin() file
require_once '../php/databaseListener.php';

if (doLogin('test', 'user')) {
	echo "Username exists" . PHP_EOL;
} else {
	echo "Username does not";
}

if (doLogin('testt', 'user')) {
        echo "Username exists" . PHP_EOL;
} else {
        echo "Username does not";
}


?>

