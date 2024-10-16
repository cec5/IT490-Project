<?php

// require doLogin() file
require_once '../php/databaseListener.php';

if (doLogin('user', 'test')) {
	echo "Login" . PHP_EOL;
} else {
	echo "No match";
}

if (doLogin('user', 'usefsafsr')) {
        echo "Login" . PHP_EOL;
} else {
        echo "No match";
}

?>

