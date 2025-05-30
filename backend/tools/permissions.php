<?php
require_once 'toolbox.php';
require_once '../main_core.php';
require_once '../controlers/database_controler.php';


class permissions{

    private $email;
    private $master_handler;
    private $permissions_id;

    public function __construct($master_handler, $email)
    {
        $this->email = $email;
        $this->master_handler = $master_handler;

        /* read permission ID */
        $this->permissions_id = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array("permission"), "`email`='" . $email . "'")[0]["permission"];
    }

    public function get_permissions_id()
    {
        return $this->permissions_id;
    }

    public function get_pages()
    {
        
        $id = $this->permissions_id;
        $permissions_string = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_permissions, array("pages"), "`id`='" . $id . "'")[0]["pages"];
        return explode(",", $permissions_string); 
    }

    public function get_operations()
    {
        $id = $this->permissions_id;
        $permissions_string = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_permissions, array("operations"), "`id`='" . $id . "'")[0]["operations"];
    
        return explode(",", $permissions_string); 
    }
}


?>