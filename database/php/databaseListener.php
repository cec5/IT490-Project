<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

$host = "aws-0-us-east-1.pooler.supabase.com";
$dbname = "postgres";
$user = "postgres.scpoojzcwikmbjwjabua";
$password = "5TI6sqXVtZIKD411";


function doLogin($username,$password){

// lookup username in databas	
//WILL BE SET TO .ENV WITH UPDATED PASSWORD	
$host = "aws-0-us-east-1.pooler.supabase.com";
$dbname = "postgres";
$user = "postgres.scpoojzcwikmbjwjabua";
$password = "5TI6sqXVtZIKD411";
$port = '6543';


	try {
		$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$stmt = $pdo->prepare("SELECT COUNT(*) FROM userLogin WHERE username = :username");
		$stmt->execute(['username' => $username]);

		$count = $stmt->fetchColumn();

		return $count > 0;
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
		return false;
	}
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
