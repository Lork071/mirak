<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';

class scanner_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
    }


    public function register_participant($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );

        /* Check if the user was registredread_row*/
        $result["response"] = $this->master_handler->database->read_row($this->master_handler->config->database_name_event, array("money,registred"), "id = '".$parameters["id"]."'");
        

        if($this->master_handler->database->update_row($this->master_handler->config->database_name_event, array("registred" => true, "registred_name" => $parameters["user_info"]["email"]), "id = '".$parameters["id"]."'"))
        {
            $result["result"] = true;
        }
        
        return $result;
    }

    public function participant_food()
    {
        return $this->master_handler->database->select_rows($this->master_handler->config->database_name_participants);
    }

    public function admin_scan($data)
    {
        $this->master_handler->database->insert_row($this->master_handler->config->database_name_admin_scan, $data);
    }
  
    
}
$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new scanner_controler($master_handler, $toolbox));
exit();
?>