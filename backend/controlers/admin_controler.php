<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';

class admin_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
    }

    public function read_errors()
    {
        $result = array(
            "result" => false,
            "response" => ""
        );
        $rows = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_error, "all", "");

        if ($rows != null && is_array($rows) && count($rows) > 0) {
            $result["result"] = true;
            $result["response"] = array_reverse($rows);
        } else {
            $result["response"] = $rows;
        }

        return $result;
    }

    public function error_delete($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );
        if($this->master_handler["database_handler"]->delete_row($this->master_handler["config_handler"]->database_name_error, $parameters["condition"]))
        {
            $result["result"] = true;
        }

        return $result;
    }

    public function error_generate()
    {
        $result = array(
            "result" => true,
            "response" =>""
        );
        trigger_error("This is a test error.", E_USER_NOTICE);
        throw new Exception("This is a test exception.");
    }

    public function user_all()
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        $result["response"] = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, "all", "");

        if($result["response"] != null)
        {
            $result["result"] = true;
        }

        return $result;
    }

    
    public function user_admin()
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        $result["response"] = array();
        $result["response"]["all_permissions"] = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_permissions, array("id", "name"), "1");
        $result["response"]["admin_users_info"] = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, "all", "`admin_user` = 1");

        if($result["response"] != null)
        {
            $result["result"] = true;
        }

        return $result;
    }


    public function change_rank_user($parameters)
    {
        $result = array(
            "result" => false,
            "response" => array(

            )
        );

        if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_users, array("admin_user" => $parameters["promote"],"permission" => ""), "`email` = '".$parameters["email"]."'"))
        {
            $result["result"] = true;
        }
        else
        {
            $result["result"] = false;
            $result["response"] = array(
                    "error_title" => "error",
                    "error_desc" => "error_comm_database"
            );
        }
        

        return $result;

    }

    public function create_test_user()
    {
        $result = array(
            "result" => true,
            "response" =>""
        );

        $this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_users, array("FirstName"=>"Dummy", "LastName" => "User", "email"=>"dummy.user@aaa.cz"));

        return $result;
    }

    public function delete_normal_user($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );


        if($this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array("admin_user"),"`email` = '".$parameters["email"]."'")[0]["admin_user"] == false)
        {
            if($this->master_handler["database_handler"]->delete_row($this->master_handler["config_handler"]->database_name_users, "`email` = '".$parameters["email"]."'"))
            {
                $result["result"] = true;
            }
            else
            {
    
            }
        }
        else{
            $result["result"] = false;
            $result["response"] = array(
                    "error_title" => "admin_user_delet_user_faile_admin_title",
                    "error_desc" => "admin_user_delet_user_faile_admin_desc"
            );
        }
        

        return $result;
        
    }

    
    public function delete_admin_user($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        if($this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array("admin_user"),"`email` = '".$parameters["email"]."'")[0]["admin_user"] == true)
        {
            if($this->master_handler["database_handler"]->delete_row($this->master_handler["config_handler"]->database_name_users, "`email` = '".$parameters["email"]."'"))
            {
                $result["result"] = true;
            }
            else
            {
    
            }
        }
        else{
            $result = array(
                "result" => false,
                "response" =>array(
                    "error_title" => "admin_user_delet_user_faile_admin_title",
                    "error_desc" => "admin_user_delet_user_faile_admin_desc"
                )
            );
        }


        return $result;
        
    }

    public function permission_read()
    {
        $result = array(
            "result" => true,
            "response" =>""
        );


        $database_response = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_permissions, "all" ,"1");

        if($database_response != null)
        {
            $result["result"] = true;
            $result["response"] = array();
            $result["response"]["permissions"] = array();
            foreach($database_response as $row)
            {
                $dummy_array = array();
                $dummy_array["id"] = $row["id"];
                $dummy_array["name"] = $row["name"];
                $dummy_array["scanner_default"] = $row["scanner_default"];
                if($row["pages"] != "")
                {
                    $dummy_array["pages"] = explode(",", $row["pages"]);
                }
                else
                {
                    $dummy_array["pages"] = array();
                }
                if($row["operations"] != "")
                {
                    $dummy_array["operations"] = explode(",",$row["operations"]);
                }
                else
                {
                    $dummy_array["operations"] = array();
                }
                $dummy_array["food_scan"] = $row["food_scan"];

                array_push($result["response"]["permissions"], $dummy_array);
            }
            $i = 0;
            $result["response"]["permissions_pages"] = $this->master_handler["config_handler"]->permissions_pages;
            $result["response"]["permissions_operations"] = $this->master_handler["config_handler"]->permissions_operations;
            $result["response"]["food_scan"] = $this->master_handler["config_handler"]->meals;
            $result["response"]["scanners"] = $this->master_handler["config_handler"]->scanner_options;
        }
        else
        {
            /* not exist*/
        }


        return $result;
    }


    public function permission_update($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        $update_pages = "";
        foreach($parameters["pages"] as $key => $page)
        {
            $update_pages .= $page . ",";
        }
        $update_pages = substr($update_pages, 0, -1);

        $update_operations = "";
        foreach($parameters["operations"] as $key => $operation)
        {
            $update_operations .= $operation . ",";
        }
        $update_operations = substr($update_operations, 0, -1);

        $data = array(
            "name" => $parameters["name"],
            "pages" => $update_pages,
            "operations" => $update_operations,
            "food_scan" => $parameters["food_scan"],
            "scanner_default" => $parameters["scanner_default"],
        );

        if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_permissions, $data ,"`id`='". $parameters["id"] ."'"))
        {
            $result["result"] = true;
            $result["response"] = array(
                "title" => "sucessfull",
                "message" => "admin_permissions_updated"
            );
        }
        else
        {
            $result["result"] = false;
            $result["response"] = array(
                "title" => "error",
                "message" => "admin_permissions_not_updated"
            );
        }

        return $result;
    }

    public function permission_create($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        $update_pages = "";
        foreach($parameters["pages"] as $key => $page)
        {
            $update_pages .= $page . ",";
        }
        $update_pages = substr($update_pages, 0, -1);

        $update_operations = "";
        foreach($parameters["operations"] as $key => $operation)
        {
            $update_operations .= $operation . ",";
        }
        $update_operations = substr($update_operations, 0, -1);

        $uuid = $this->toolbox->generateUuid();
        $data = array(
            "id" => $uuid,
            "name" => $parameters["name"],
            "pages" => $update_pages,
            "operations" => $update_operations,
            "food_scan" => $parameters["food_scan"],
            "scanner_default" => $parameters["scanner_default"],
        );

        if($this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_permissions, $data))
        {
            $result["result"] = true;
            $result["response"] = array(
                "title" => "sucessfull",
                "message" => "admin_permissions_created"
            );
        }
        else
        {
            $result["result"] = false;
            $result["response"] = array(
                "title" => "error",
                "message" => "admin_permissions_not_created"
            );
        }
        
        return $result;
    }

    public function permission_delete($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        if($this->master_handler["database_handler"]->delete_row($this->master_handler["config_handler"]->database_name_permissions, "`id`='".$parameters["id"]."'"))
        {
            $result["result"] = true;
            $result["response"] = array(
                "title" => "sucessfull",
                "message" => "admin_permissions_deleted"
            );
        }
        else
        {
            $result["result"] = false;
            $result["response"] = array(
                "title" => "error",
                "message" => "admin_permissions_not_deleted"
            );
        }

        return $result;
    }

    public function permission_user_update($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );
        $permission_data = $this->get_premission_info($parameters["permission"]);
        if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_users, array("permission" => $parameters["permission"]) ,"`email`='". $parameters["email"] ."'"))
        {
            /* update the default scanner */
            if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_users, array("scanner" => $permission_data["scanner_default"]) ,"`email`='". $parameters["email"] ."'"))
            {
                $result["result"] = true;
                $result["response"] = array(
                    "title" => "sucessfull",
                    "message" => "admin_permissions_user_updated"
                );
            }
            else
            {
                $result["result"] = false;
                $result["response"] = array(
                    "title" => "error",
                    "message" => "admin_permissions_user_not_updated"
                );
            }
        }
        else
        {
            $result["result"] = false;
            $result["response"] = array(
                "title" => "error",
                "message" => "admin_permissions_user_not_updated"
            );
        }

        return $result;
    }


    public function get_qr_reader_info($parameters)
    {
        $result = array(
            "result" => true,
            "response" => array()
        );

        $result["response"]["scanner_options"] = $this->master_handler["config_handler"]->scanner_options;
        $result["response"]["user_info"] = $this->get_user_info($parameters["user_info"]["email"]);

        return $result;
    }

    public function update_user_scanner($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_users, array("scanner" => $parameters["scanner"]) ,"`email`='". $parameters["user_info"]["email"] ."'"))
        {
            $result["result"] = true;
        }
        else
        {
            $result["result"] = false;
            $result["response"] = array(
                "error_title" => "error",
                "error_desc" => "error_comm_database"
            );
        }

        return $result;
    }

    public function scan_registration_get_info($parameters)
    {
        $result = array(
            "result" => false,
            "response" =>array()
        );

        /* Check if participant was registred or not*/

        $participant_info = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("register","pay","email"),"`id` = '".$parameters["id"]."'")[0];
        if($participant_info["register"])
        {
            $result["result"] = true;
            $result["response"] = array(
                "is_registrated" => "true",
                "error_desc" => "error_already_registered",
                "pay" => $participant_info["pay"],
                "email" => $participant_info["email"],
            );
        }
        else
        {
            $result["result"] = true;
            $result["response"] = array();
            $result["response"]["is_registrated"] = "false";
            $result["response"]["pay"] = $participant_info["pay"];
            $result["response"]["email"] = $participant_info["email"];

        }

        return $result;
    }

    
    public function admin_delete_ticket($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );

        if(!isset($parameters["id"]) || empty($parameters["id"])){
            $result["response"] = "error_comm_api";
            return $result;
        }

        $database = $this->master_handler["config_handler"]->database_name_event;
        if($this->master_handler["database_handler"]->delete_row($database, "`id`='".$parameters["id"]."'")){
            $result["result"] = true;
            $result["response"] = "operation_was_successful";
        }
        else{
            $result["response"] = "operation_was_not_successful";
        }
        
        return $result;
    }
    /**************************************
     * 
     * Private functions
     * 
     **************************************/
    private function get_user_info($email)
    {
        $result = array();

        $result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, "all", "`email` = '".$email."'")[0];

        return $result;
    }
     private function get_premission_info($premission_id)
     {
        $result = array();

        $result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_permissions, "all", "`id` = '".$premission_id."'")[0];

        return $result;
     }
    
}
$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new admin_controler($master_handler, $toolbox));
exit();
?>