<?php
/**
*@package SKY
*@author Adrian S.
**/
namespace SKY;

class AMQPRPC_Exception extends \Fuel_Exception {}

class AMQPRPC
{
	private $_broker_host;
	private $_broker_port;
	private $_broker_user;
	private $_broker_password;
	private $_rpc_dest_exchange;
	private $_rpc_dest_key;
	private $_rpc_return_exchange;
	private $_rpc_return_queue;
	private $_rpc_return_key;
	private $_rpc_timeout;
	
	// RPC call loop sleep in microseconds
	private $_rpc_sleep_loop = 25000;
	
	
	function __construct($rpc_timeout = 30) 
	{		
		\Config::load('sky', true);
		
		//set host IP
		ini_set("amqp.host", \Config::get('sky.broker_host'));
				
		// Store the rpc destination information
		$this->_rpc_dest_exchange = \Config::get('sky.rpc_dest_exchange');
		$this->_rpc_dest_key = \Config::get('sky.rpc_dest_key');
		
		// Store the rpc return information
		$this->_rpc_return_exchange = \Config::get('sky.rpc_return_exchange');
		$this->_rpc_return_queue = \Config::get('sky.rpc_return_queue');
		$this->_rpc_return_key = \Config::get('sky.rpc_return_key');
		
		// Store the rpc timeout in milliseconds
		$this->_rpc_timeout = $rpc_timeout * (1000000 / $this->_rpc_sleep_loop);
		
	}
	
	//make the call and wait for response :
	public function call($message)
	{
		// RPC result object
		$return = null;	
     
			// Generate the rpc return path id
			$rpc_return_id = base_convert(microtime(true), 10, 36) . rand() .  session_id();
			$rpc_return_queue = $this->_rpc_return_queue . "." . $rpc_return_id;
			$rpc_return_key = $this->_rpc_return_key . "." . $rpc_return_id;
			
			/* SENDING A MSQ */
			$connection = new \AMQPConnection();
			$connection->connect();
			
			//channel
			$channel = new \AMQPChannel($connection);
			
			//exchange
			$exchange = new \AMQPExchange($channel);
			$exchange->setName($this->_rpc_dest_exchange);
			$exchange->setType(AMQP_EX_TYPE_DIRECT);
			
			$rex = new \AMQPExchange($channel);
			$rex->setName($this->_rpc_return_exchange);
			$rex->setType(AMQP_EX_TYPE_DIRECT);
			$rex->declare();
			
			//create callback queue
			$callback_queue = new \AMQPQueue($channel);
			$callback_queue->setName($rpc_return_queue);
			$callback_queue->setFlags(AMQP_AUTODELETE);
			$callback_queue->declare();
			$callback_queue->bind($this->_rpc_return_exchange, $rpc_return_queue);		
			
			$timeout = 5000;
			$start_ts = time();

			$params = array(
				'Content-type'=>'application/json',
				'Content-encoding'=>NULL,			
				'message_id'=>$rpc_return_id,
				'user_id'=>NULL,
				'app_id'=>NULL,
				'delivery_mode'=>2,
				'priority'=>NULL,
				'timestamp'=>time(),
				'expiration'=>$timeout,
				'type'=>NULL,
				'reply_to'=>$rpc_return_queue
			);
						
			$exchange->declare();
			//send to AMQP
			$exchange->publish($message, $this->_rpc_dest_exchange, null, $params);
			
			//lets get the messages
			do {
				$msg = $callback_queue->get(AMQP_AUTOACK);
				//$callback_queue->nack($message['delivery_tag']);				
				
				if ($timeout && ((time() - $start_ts) > $timeout)) {
					echo "RPC read loop exceeded required timeout";
					$callback_queue->delete();
					break;					
				}
				
				if($msg)
				{
					$body = $msg->getBody();
					$dtag = $msg->getDeliveryTag();
					
					$return = $body;				
					
					//$callback_queue->ack($dtag);
					$callback_queue->delete();					
									
					/*
					unset($callback_queue);
					unset($exchange);
					unset($channel);
					unset($connection);
					*/
					break;
				}else{
					usleep(500000);
					continue;
				}					
						
				
			} while(true);		
			
				
			
		return $return;
	}
	
	
}

?>