<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

function doLogin($username,$password){
    // lookup username in databas
    // check password
    return true;
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
		 return "test message recieved from the database server".PHP_EOL;
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
