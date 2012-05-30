<?php
/**
* @author Adrian S.
**/
Autoloader::add_core_namespace('SKY');
Autoloader::add_classes(array(
    'SKY\\SKY' => __DIR__.'/classes/sky.php',
    'SKY\\SKYException' => __DIR__.'/classes/sky.php',
	'SKY\\AMQPRPC' => __DIR__.'/classes/amqp.php',
	'SKY\\AMQPRPC_Exception' => __DIR__.'/classes/amqp.php'
));

?>