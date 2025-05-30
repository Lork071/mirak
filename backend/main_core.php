<?php 
require_once 'config/config.php';
require_once 'controlers/database_controler.php';
require_once 'controlers/error_controler.php';
require_once 'test/master_test.php';


header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');


/**************************************
 * Master handler
 **************************************/
$master_handler = array();
/**************************************
 * Create config 
 **************************************/
$master_handler["config_handler"]= new config();

/**************************************
 * database handler
 **************************************/
$master_handler["database_handler"] = new database_controler($master_handler["config_handler"]);

/**************************************
 * error handler
 **************************************/
$master_handler["error_handler"] = new error_controler($master_handler["database_handler"], $master_handler["config_handler"]);

$master_handler["error_handler"]->register();



/**************************************
 * create debug features
 **************************************/
if($master_handler["config_handler"]->debug == true)
{
    $master_test_handler = new master_test();
}
else
{
    $master_test_handler = null;   
}


?>
