fuelphp-amqp
============

A fuelphp package that perform RPC connection to an amqp server broker. 

Example call:

`
SKY::call('_method_name_', $args);
$result = SKY::result();
$error = SKY::error();
`
