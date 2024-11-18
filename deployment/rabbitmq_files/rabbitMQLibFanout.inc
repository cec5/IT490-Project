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
    	private $routing_key = '*';
    	private $exchange_type = "fanout";
    	private $auto_delete = false;
    	private $conn;
    	private $channel;

    	function __construct($machine, $server = "rabbitMQ") {
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT = $this->machine[$server]["BROKER_PORT"];
		$this->USER = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		if (isset($this->machine[$server]["EXCHANGE_TYPE"])) {
		    $this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
		}
		if (isset($this->machine[$server]["AUTO_DELETE"])) {
		    $this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
		}
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
    	}

    	private function setupConnection() {
        	$params = array(
            		'host' => $this->BROKER_HOST,
            		'port' => $this->BROKER_PORT,
            		'login' => $this->USER,
            		'password' => $this->PASSWORD,
            		'vhost' => $this->VHOST
        	);

        	$this->conn = new AMQPConnection($params);
        	$this->conn->connect();
        	$this->channel = new AMQPChannel($this->conn);

        	$exchange = new AMQPExchange($this->channel);
		$exchange->setName($this->exchange);
		$exchange->setType($this->exchange_type);
		$exchange->setFlags(AMQP_DURABLE);
		$exchange->declareExchange();

		$queue = new AMQPQueue($this->channel);
		$queue->setName($this->queue);
		$queue->setFlags(AMQP_DURABLE);
		$queue->declareQueue();
		$queue->bind($this->exchange);

		$this->conn_queue = $queue;
    	}

    	public function process_message($msg) {
        	try {
            		$body = $msg->getBody();
            		$payload = json_decode($body, true);

            		if (!isset($this->callback)) {
                		throw new Exception("No callback defined to process messages");
            		}

            		$response = call_user_func($this->callback, $payload);

            		// Acknowledge the message
            		$this->conn_queue->ack($msg->getDeliveryTag());

            		return $response;
        	} catch (Exception $e) {
            		echo "Error processing message: " . $e->getMessage() . PHP_EOL;
        	}
    	}

    	public function process_requests($callback) {
        	try {
            		$this->callback = $callback;
            		$this->setupConnection();

            		$this->conn_queue->consume(function ($msg) {
                		$this->process_message($msg);
            		});

            		while (count($this->channel->callbacks)) {
                		$this->channel->wait();
            		}
        	} catch (Exception $e) {
            		trigger_error("Failed to start request processor: " . $e->getMessage(), E_USER_ERROR);
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
    	private $exchange_type = "topic";
    	private $routing_key = '*';
    	private $response_queue = array();
    	private $conn;
    	private $channel;

    	function __construct($machine, $server = "rabbitMQ") {
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT = $this->machine[$server]["BROKER_PORT"];
		$this->USER = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		if (isset($this->machine[$server]["EXCHANGE_TYPE"])) {
            		$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
        	}
        	$this->exchange = $this->machine[$server]["EXCHANGE"];
    	}

    	private function setupConnection() {
        	$params = array(
            		'host' => $this->BROKER_HOST,
            		'port' => $this->BROKER_PORT,
            		'login' => $this->USER,
            		'password' => $this->PASSWORD,
            		'vhost' => $this->VHOST
        	);

        	$this->conn = new AMQPConnection($params);
        	$this->conn->connect();
        	$this->channel = new AMQPChannel($this->conn);

        	$exchange = new AMQPExchange($this->channel);
        	$exchange->setName($this->exchange);
        	$exchange->setType($this->exchange_type);
        	$exchange->setFlags(AMQP_DURABLE);
        	$exchange->declareExchange();

        	$this->conn_exchange = $exchange;
    	}

    	public function send_request($message) {
        	$uid = uniqid();
        	$json_message = json_encode($message);

        	try {
            		$this->setupConnection();

            		// Publish the message
            		$this->conn_exchange->publish($json_message, $this->routing_key);

            		echo "Message published: " . $json_message . PHP_EOL;
        	} catch (Exception $e) {
            		die("Failed to send message to exchange: " . $e->getMessage() . "\n");
        	}
    	}
}
