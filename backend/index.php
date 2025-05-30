<?php
require_once 'config/config.php';
require_once 'controlers/database_controler.php';
require_once 'controlers/error_controler.php';
require_once 'controlers/login_controler.php';
require_once 'test/master_test.php';


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


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
if($config_handler->debug == true)
{
    $master_test_handler = new master_test();
}
else
{
    $master_test_handler = null;   
}

/**************************************
 * Request handler
 **************************************/
header("Content-Type: application/json");
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $master_handler["error_handler"]->log_user_error(null,"REST APP - bad data", "Json data is not exist.");
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

if (!isset($data['operation'])) {
    $master_handler["error_handler"]->log_user_error(null,"REST APP - bad data", "The key Operation is missing.");
    echo json_encode(['error' => 'The key "operation" is important']);
    exit;
}

switch($data['operation'])
{
    case 'test_error_insert':
        if($config_handler->debug == true)
        {
            $master_test_handler->error_trigger();
        }
        else
        {

        }
        break;
    case 'login_controler':
        
        $login_handler = new login_controler($master_handler);
        echo $login_handler.$data["method"]($data["parameters"]);
        break;
}

?>