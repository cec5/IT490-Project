<?php

// require doLogin() file
require_once '../php/databaseListener.php';

if (doLogin('user', 'test')) {
	echo "Username exists" . PHP_EOL;
} else {
	echo "Username does not";
}

if (doLogin('testt', 'usefsafsr')) {
        echo "Username exists" . PHP_EOL;
} else {
        echo "Username does not";
}

?>

