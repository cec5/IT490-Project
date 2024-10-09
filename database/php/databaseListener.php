<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');


$server = "localhost";
$username = "admin";
$password = "pass";
$db = "project";

$conn = new mysqli($server, $username, $password, $db);


function doLogin($dbUser, $dbPass){
	$conn = new mysqli("localhost", "admin", "pass", "project"); //establish connection to database
	
	$stmt = $conn->prepare("SELECT COUNT(*) FROM userLogin WHERE username = ? AND password = ?"); //search the database to find result where username and password match

	$stmt->bind_param("ss", $dbUser, $dbPass); //correctly assign the username and password that are searched for

	$stmt->execute(); //search

	$stmt->bind_result($count); //counts row of correct username/pass
	$stmt->fetch(); 

	$stmt->close();
	$conn->close();

	return $count > 0; //if entry of correct user/pass exist, return true, else false

}

function requestProcessor($request){
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type'])){
    return "ERROR: unsupported message type";
  }
  switch ($request['type']){
 	 case "test":
		 return "test message recieved".PHP_EOL;
	 case "login":
		 return doLogin($request['username'],$request['password']);
	 case "validate_session":
		 return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Database Server received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_db.ini","testServer");

echo "Database Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "Database Listener Processed Request".PHP_EOL;
exit();
?>
