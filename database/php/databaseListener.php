<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');


$server = "localhost"; //any host
$username = "admin";
$password = "pass";
$db = "project";

$conn = new mysqli($server, $username, $password, $db);


function doLogin($username){
// lookup username in databas	
	$conn = new mysqli("localhost", "admin", "pass", "project");
	
	$stmt = $conn->prepare("SELECT COUNT(*) FROM userLogin WHERE username = ?");

	$stmt->bind_param("s", $username);

	$stmt->execute();

	$stmt->bind_result($count);
	$stmt->fetch();

	$stmt->close();
	$conn->close();

	return $count > 0;
    // check password
    //return false if not valid
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
/*
$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_db.ini","testServer");

echo "Database Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "Database Listener Processed Request".PHP_EOL;
exit();*/
?>
