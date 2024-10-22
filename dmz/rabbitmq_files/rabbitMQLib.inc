<?php

require_once('get_host_info.inc');

class rabbitMQServer {
    	private $machine = "";
    	public  $BROKER_HOST;
    	private $BROKER_PORT;
    	private $USER;
    	private $PASSWORD;
    	private $VHOST;
    	private $exchange;
    	private $queue;
    	private $routing_key = '*';
    	private $exchange_type = "topic";
    	private $auto_delete = false;

    	function __construct($machine, $server = "rabbitMQ"){
        	$this->machine = getHostInfo(array($machine));
        	$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"];
        	$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];
        	$this->USER     = $this->machine[$server]["USER"];
        	$this->PASSWORD = $this->machine[$server]["PASSWORD"];
        	$this->VHOST = $this->machine[$server]["VHOST"];
        	if (isset( $this->machine[$server]["EXCHANGE_TYPE"])) {
            		$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
        	}
        	if (isset( $this->machine[$server]["AUTO_DELETE"])) {
            		$this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
        	}
        	$this->exchange = $this->machine[$server]["EXCHANGE"];
        	$this->queue = $this->machine[$server]["QUEUE"];
    	}
	function process_message($msg){
		try {
            		$body = $msg->getBody();
            		$payload = json_decode($body, true);
            		if ($msg->getReplyTo()) {
                		// Process the request and generate a response
                		if (isset($this->callback)) {
                    			$response = call_user_func($this->callback, $payload);
                		}

                		// Respond to the message
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
                		$replykey = $this->routing_key.".response";
                		$conn_queue->bind($exchange->getName(), $replykey);

                		// Send the response back
                		$exchange->publish(json_encode($response), $replykey, AMQP_NOPARAM, array('correlation_id' => $msg->getCorrelationId()));
            		}

            		// Acknowledge after processing
            		$this->conn_queue->ack($msg->getDeliveryTag());
        	} catch (Exception $e) {
            		echo "Error processing message: " . $e->getMessage() . PHP_EOL;
        	}
    	}
    	
	function process_requests($callback) {
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
            		$this->conn_queue->bind($exchange->getName(), $this->routing_key);

            		$this->conn_queue->consume(array($this,'process_message'));

            		// Loop as long as the channel has callbacks registered
            		while (count($channel->callbacks)) {
                		$channel->wait();
            		}
        	} catch (Exception $e) {
            		trigger_error("Failed to start request processor: ".$e,E_USER_ERROR); 
        	}
    }
}

class rabbitMQClient {
    	private $machine = "";
    	public  $BROKER_HOST;
    	private $BROKER_PORT;
    	private $USER;
   	private $PASSWORD;
   	private $VHOST;
    	private $exchange;
    	private $queue;
    	private $routing_key = '*';
    	private $response_queue = array();
    	private $exchange_type = "topic";
    
    	// Declare conn_queue as a class property
    	public $conn_queue;
    
    	function __construct($machine, $server = "rabbitMQ") {
        	$this->machine = getHostInfo(array($machine));
        	$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"];
        	$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];
        	$this->USER     = $this->machine[$server]["USER"];
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

    	function process_response($response, $uid) {
        	// Check if the conn_queue is initialized before acknowledging the response
        	if ($this->conn_queue) {
            		$this->conn_queue->ack($response->getDeliveryTag()); // Acknowledge the message
        	} else {
            		echo "Error: conn_queue is not initialized, cannot ack message.\n";
            	return true; // Skip processing if conn_queue is not initialized
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


    	function send_request($message) {
   		$uid = uniqid(); // Generate a unique ID for each request
   		$json_message = json_encode($message);

    		try {
        		// Set up the RabbitMQ connection parameters
        		$params = array(
            			'host' => $this->BROKER_HOST,
            			'port' => $this->BROKER_PORT,
            			'login' => $this->USER,
            			'password' => $this->PASSWORD,
            			'vhost' => $this->VHOST
        		);

        		// Create a new AMQP connection
        		$conn = new AMQPConnection($params);
        		$conn->connect();

        		// Open a channel for communication
        		$channel = new AMQPChannel($conn);

        		// Declare and set up the exchange
        		$exchange = new AMQPExchange($channel);
        		$exchange->setName($this->exchange);
       		 	$exchange->setType($this->exchange_type);

        		// Declare and set up a callback queue for the response
			$callback_queue = new AMQPQueue($channel);
			$callback_queue->setFlags(AMQP_AUTODELETE); // Auto-delete the queue when it's no longer used
			$callback_queue->declareQueue(); // RabbitMQ generates a unique queue name
			$callback_queue_name = $callback_queue->getName(); // Get the generated queue name

			// Bind the callback queue to the exchange
			$callback_queue->bind($exchange->getName(), $this->routing_key . ".response");

			// Set up the main queue (this is the queue where the message will be sent)
			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(), $this->routing_key);

			// Publish the message, specifying the callback queue for the response
			$exchange->publish($json_message, $this->routing_key, AMQP_NOPARAM, array(
			    'reply_to' => $callback_queue_name, // Specify the unique callback queue
			    'correlation_id' => $uid
			));

			// Wait for the response from the callback queue
			$this->response_queue[$uid] = "waiting";
			$callback_queue->consume(function ($response) use ($uid) {
			    return $this->process_response($response, $uid);
			});

        		// Retrieve the response after processing
        		$response = $this->response_queue[$uid];
        		unset($this->response_queue[$uid]); // Clean up the response queue entry

        		return $response;
    		} catch (Exception $e) {
        		die("Failed to send message to exchange: " . $e->getMessage() . "\n");
    		}
	}


    	function publish($message) {
        	$json_message = json_encode($message);
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
            	$this->conn_queue->bind($exchange->getName(), $this->routing_key);
            	return $exchange->publish($json_message, $this->routing_key);
		} catch (Exception $e) {
            		die("Failed to send message to exchange: " . $e->getMessage() . "\n");
        	}
    	}
}
?>

