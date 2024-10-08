s<?php

$host = 'aws-0-us-east-1.pooler.supabase.com';
$db = 'postgress';
$user = 'postgres.scpoojzcwikmbjwjabua';
$pass = '5TI6sqXVtZIKD411';
$port = '6543';

try {
	$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	echo "db success";
} catch (PDOException $e) {
	echo "db fail" . $e->getMessage();
}
?>
