# HCPB
HC PHP Bridge is a simple interface that allows to send data or commands between different php projects.

##Features
- Add functions to the handler to extend functionality;
- Passkey for basic security.

##Requirements
- PHP 5.3+

##Usage example
First you need to configure the handler. For example, create a file "q.php" and add the code:

```php
<?php
require_once 'HCJB.php';

//add new function to the handler
HCJB::addFunction('test', function() {
   $a = new stdClass;
   $a->message = 'Test ok!';
   return $a; 
});

//start handler
HCJB::query();
```

Let's consider that handler is located at: http://sandbox/q.php
And make a query:
```php
require_once("HCJB.php");

$a = HCJB::get("http://sandbox/q.php", 'test');
var_dump($a);
```
Output:
```
object(stdClass)#2 (1) { ["message"]=> string(8) "Test ok!" }
```

##Passkey
To configure a passkey, you must setup the configuration file (hcjb.json):
```json
{
   "secured": true,
   "passkey": "your_passkey"
}
```

Or to load a configuration from the code:
```php
HCJB::config(array('secured'=>true, 'passkey'=>'your_passkey'));
```
