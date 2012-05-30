<?php


namespace SKY;
/**
* SKY Exception
*/
class SKYException extends \Fuel_Exception {}


/**
* @package SKY
*/
class SKY
{
	
	protected static $instance;
	protected static $amqp_rpc = null;	
	protected static $response = null;
	protected static $request_id = null;		
	
	public static function instance()
	{
		if(is_null(static::$instance))
		{
			static::$instance = new self();
		}
		
		return static::$instance;
	}
	
	//initiate amqp_rpc object for calls
	public static function _init()
	{
		//\Config::load('config', 'sky');
		static::$amqp_rpc = new AMQPRPC(30);		
	}
	
	//make a call with the specified method and parameters
	public static function call($method, $params)
	{
		//generate a random id for response verification.
		static::$request_id = base_convert(microtime(true), 10, 36);
		
		//prepare data
		$data = array(
			"method" => $method,
			"params" => $params,
			"id" => static::$request_id
		);
		
		//send data away and good luck
		if(\Config::get('sky.simulate_bs'))
		{
			$msg = array(
				'result' => array(),
				'id' => static::$request_id
			);
			static::$response = array(
				'msg'=> json_encode($msg)
			);
			
		}else{			
			static::$response = static::$amqp_rpc->call(json_encode($data));
		}
		
	}
	
	public static function result()
	{
		$return = json_decode(static::$response);
		/*
		echo "<pre>";
		print_r($return);
		echo "</pre>";
		*/	
		//verify that we got the same id
		if($return->id != static::$request_id) return false;
		else if(!empty($return->error)) return false;			
		return $return->result;
	}
	
	public static function get_error()
	{
		$return = json_decode(static::$response['msg']);
		
		if(isset($return->error)) return $return->error;
		
		return false;
	}
	
	/**
	 * Prevent instantiation
	 */
	final private function __construct() {}
	
}


?>