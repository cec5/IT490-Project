<?php

require_once('get_host_info.inc');

class rabbitMQServer {
    	private $machine = "";
    	public $BROKER_HOST;
    	private $BROKER_PORT;
    	private $USER;
    	private $PASSWORD;
    	private $VHOST;
    	private $exchange;
    	private $queue;
    	private $routing_key = '#'; // Default to wildcard to capture all
    	private $exchange_type = "topic";
    	private $auto_delete = false;

    	function __construct($machine, $server = "rabbitMQ") {
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT = $this->machine[$server]["BROKER_PORT"];
		$this->USER = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"] ?? "topic"; // Default to topic
		$this->auto_delete = $this->machine[$server]["AUTO_DELETE"] ?? false;
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
	}	

    	function process_message($msg) {
        	try {
            		$body = $msg->getBody();
            		$payload = json_decode($body, true);

		    	if ($msg->getReplyTo()) {
		        	if (isset($this->callback)) {
		            		$response = call_user_func($this->callback, $payload);
		        	}

		        	$params = array(
		            		'host' => $this->BROKER_HOST,
		            		'port' => $this->BROKER_PORT,
		            		'login' => $this->USER,
		            		'password' => $this->PASSWORD,
		            		'vhost' => $this->VHOST
		        	);

		        	$conn = new AMQPConnection($params);
		        	$conn->connect();
		        	$channel = new AMQPChannel($conn);
		        	$exchange = new AMQPExchange($channel);
		        	$exchange->setName($this->exchange);
		        	$exchange->setType($this->exchange_type);

		        	$conn_queue = new AMQPQueue($channel);
		        	$conn_queue->setName($msg->getReplyTo());
		        	$replykey = $this->routing_key . ".response";
		        	$conn_queue->bind($exchange->getName(), $replykey);

		        	$exchange->publish(json_encode($response), $replykey, AMQP_NOPARAM, array('correlation_id' => $msg->getCorrelationId()));
		    	}

            	$this->conn_queue->ack($msg->getDeliveryTag());
        	} catch (Exception $e) {
            		echo "Error processing message: " . $e->getMessage() . PHP_EOL;
        	}
    	}

    	function process_requests($callback, $custom_routing_key = null) {
        	try {
            		$this->callback = $callback;
            		$params = array(
                		'host' => $this->BROKER_HOST,
                		'port' => $this->BROKER_PORT,
                		'login' => $this->USER,
                		'password' => $this->PASSWORD,
                		'vhost' => $this->VHOST
            		);
            		
            		$conn = new AMQPConnection($params);
            		$conn->connect();
            		$channel = new AMQPChannel($conn);

            		$exchange = new AMQPExchange($channel);
            		$exchange->setName($this->exchange);
            		$exchange->setType($this->exchange_type);

            		$this->conn_queue = new AMQPQueue($channel);
            		$this->conn_queue->setName($this->queue);
            		$routing_key = $custom_routing_key ?? $this->routing_key;
            		$this->conn_queue->bind($exchange->getName(), $routing_key);

            		$this->conn_queue->consume(array($this, 'process_message'));

            		while (count($channel->callbacks)) {
                		$channel->wait();
            		}
        	} catch (Exception $e) {
            		trigger_error("Failed to start request processor: " . $e, E_USER_ERROR);
        	}
    	}
}

class rabbitMQClient {
	private $machine = "";
	public $BROKER_HOST;
	private $BROKER_PORT;
	private $USER;
	private $PASSWORD;
	private $VHOST;
	private $exchange;
	private $queue;
	private $routing_key = '#'; // Default to wildcard
	private $response_queue = array();
	private $exchange_type = "topic";

    	public $conn_queue;

    	function __construct($machine, $server = "rabbitMQ") {
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT = $this->machine[$server]["BROKER_PORT"];
		$this->USER = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"] ?? "topic";
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
    	}

   	function process_response($response, $uid) {
        	if ($this->conn_queue) {
            		$this->conn_queue->ack($response->getDeliveryTag());
        	} else {
            		echo "Error: conn_queue is not initialized, cannot ack message.\n";
            		return true;
        	}

        	$body = $response->getBody();
        	$payload = json_decode($body, true);

       	 	if (!isset($this->response_queue[$uid])) {
            		echo "Unknown UID: $uid\n";
            		return true;
        	}

        	$this->response_queue[$uid] = $payload ?: "[empty response]";
        	return false;
    	}

    	function send_request($message, $routing_key = null) {
        	$uid = uniqid();
        	$json_message = json_encode($message);

        	try {
            		$routing_key = $routing_key ?? $this->routing_key;

            		$params = array(
                		'host' => $this->BROKER_HOST,
                		'port' => $this->BROKER_PORT,
                		'login' => $this->USER,
                		'password' => $this->PASSWORD,
                		'vhost' => $this->VHOST
            		);

            		$conn = new AMQPConnection($params);
            		$conn->connect();

            		$channel = new AMQPChannel($conn);
            		$exchange = new AMQPExchange($channel);
            		$exchange->setName($this->exchange);
            		$exchange->setType($this->exchange_type);

            		$callback_queue = new AMQPQueue($channel);
            		$callback_queue->setFlags(AMQP_AUTODELETE);
            		$callback_queue->declareQueue();
            		$callback_queue_name = $callback_queue->getName();

            		$callback_queue->bind($exchange->getName(), $this->routing_key . ".response");

            		$this->conn_queue = new AMQPQueue($channel);
            		$this->conn_queue->setName($this->queue);
            		$this->conn_queue->bind($exchange->getName(), $routing_key);

            		$exchange->publish($json_message, $routing_key, AMQP_NOPARAM, array(
                		'reply_to' => $callback_queue_name,
                		'correlation_id' => $uid
            		));

            		$this->response_queue[$uid] = "waiting";
            		$callback_queue->consume(function ($response) use ($uid) {
                		return $this->process_response($response, $uid);
            		});

            		$response = $this->response_queue[$uid];
            		unset($this->response_queue[$uid]);

            		return $response;
        		} catch (Exception $e) {
            			die("Failed to send message to exchange: " . $e->getMessage() . "\n");
        	}
    	}

    	function publish($message, $routing_key = null) {
        	$json_message = json_encode($message);
        	$routing_key = $routing_key ?? $this->routing_key;

       		try {
            		$params = array(
                		'host' => $this->BROKER_HOST,
                		'port' => $this->BROKER_PORT,
                		'login' => $this->USER,
                		'password' => $this->PASSWORD,
                		'vhost' => $this->VHOST
            		);

            		$conn = new AMQPConnection($params);
            		$conn->connect();
            		$channel = new AMQPChannel($conn);
            		$exchange = new AMQPExchange($channel);
            		$exchange->setName($this->exchange);
            		$exchange->setType($this->exchange_type);

            		$this->conn_queue = new AMQPQueue($channel);
            		$this->conn_queue->setName($this->queue);
            		$this->conn_queue->bind($exchange->getName(), $routing_key);

            		return $exchange->publish($json_message, $routing_key);
        	} catch (Exception $e) {
            		die("Failed to send message to exchange: " . $e->getMessage() . "\n");
        	}
    	}
}
?>
