fuelphp-amqp
============

A fuelphp package that perform RPC connection to an amqp server broker. 

=== Requirements ===
This package requires the AMQP PECL extension to be installed. 
[AMQP](http://www.php.net/manual/en/intro.amqp.php "")

Example call:

`SKY::call('_method_name_', $args);`
`$result = SKY::result();`
`$error = SKY::error();`
