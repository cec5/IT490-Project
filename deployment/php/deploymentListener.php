<?php
require_once('../rabbitmq_files/path.inc');
require_once('../rabbitmq_files/get_host_info.inc');
require_once('../rabbitmq_files/rabbitMQLib.inc');

function requestProcessor($request){
    echo "received request".PHP_EOL;
    var_dump($request);
    if(!isset($request['type'])){
          return "ERROR: unsupported message type";
    }
    switch ($request['type']){
        case "test":
            return "test message recieved from Deployment Server".PHP_EOL;
        // case "get_league_players":
            // return getPremierLeaguePlayers();
    }
    return array("returnCode" => '0', 'message'=>"Deployment Server received request and processed");
}

$server = new rabbitMQServer("../rabbitmq_files/rabbitMQ_deploy.ini","testServer");

echo "Deployment Listener Active".PHP_EOL;
$server->process_requests('requestProcessor');
echo "Deployment Listener Processed Request".PHP_EOL;
exit();

?>