<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';
require_once '../tools/email_sender.php';
require_once '../tools/permissions.php';

class ticket_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
    }

    public function get_info(){
        $result = array(
            "result" => true,
            "response" => array()
        );

        $result['response']['meal'] = $this->get_meal_info();
        $result['response']['prices'] = array();
        $result['response']['prices']['ticket_meal'] = $this->master_handler["config_handler"]->price_ticket_meal;
        $result['response']['prices']['price_ticket_no_meal'] = $this->master_handler["config_handler"]->price_ticket_no_meal;
        $result['response']['prices']['price_ticket_only_friday'] = $this->master_handler["config_handler"]->price_ticket_only_friday;
        $result['response']['prices']['price_ticket_no_pii'] = $this->master_handler["config_handler"]->price_ticket_no_pii;
        $result['response']['prices']['price_one_night'] = $this->master_handler["config_handler"]->price_one_night;
        $result["response"]["workshops"]= $this->get_workshop_info();
        $result["response"]["accormodation"]= $this->get_accormodation_info();

        return $result;
    }

    public function qr_scanned($parameters)
    {
        $result = array(
            "result" => false,
            "response" => array()
        );
        /* Get from database which scanner shall be used */
        if($this->toolbox->is_sha256($parameters["id"])) 
        {
            $scanner_type = $this->master_handler["database_handler"]->read_row(
                $this->master_handler["config_handler"]->database_name_users,
                array("scanner"),
                "`email`='".$parameters["user_info"]["email"]."'"
            )[0]["scanner"];
            switch($scanner_type)
            {
                case "scanner_registration":
                    /* registration scanner */
                    $result = $this->scanner_registration($parameters["id"], $parameters["user_info"]["email"]);
                    break;
                case "scanner_meal":
                    /* food scanner */
                    $result = $this->scanner_meal($parameters["id"],  $parameters["user_info"]["email"]);
                    break;
                case "scanner_admin":
                    /* admin scanner */
                    $result["result"] = true;
                    $result["link"] = $this->master_handler["config_handler"]->url_participant_data."?id=".$parameters["id"];
                    break;
                default:
                    /* no scanner */
                    $result["response"] = "scanner_type_of_scanner_not_found";
                    $result["result"] = false;
                    break;
            }

            $result["scanner_type"] = $scanner_type;
        }
        else
        {
            $result["response"] = "participant_id_not_valid";
            $result["result"] = false;
        }
        return $result;
    }
    public function check_money_items($parameters)
    {
        $result = array(
            "result" => true,
            "response" => '',
            "errors" => array(),
            "money" => 0
        );
        if($parameters["role"] != "organizer")
        {
            $only_friday = false;
            if($parameters["part_sat1"] == false && $parameters["part_sat2"] == false && $parameters["part_sat3"] == false)
            {
                /* only friday */
                $result["money"] += $this->master_handler["config_handler"]->price_ticket_only_friday;
                $only_friday = true;
            }
            else
            {
                /* saturday and friday */
                $result["money"] += $this->master_handler["config_handler"]->price_ticket_no_meal;
            }
            /* ticket items */
            foreach($this->master_handler["config_handler"]->ticket_items["money"] as $key_item => $item_value)
            {
                if($parameters[$key_item] == $item_value["no_money"])
                {
                    /* no money */;
                    continue;
                }
                $method_name = $item_value["enable_condition"];
                if($this->$method_name($key_item, $parameters) == true)
                {
                    if($only_friday == true && $item_value["sat_required"] == true)
                    {
                        $result["errors"][] = $item_value["error_message"] . "_saturday_required";
                        $result["result"] = false;
                        continue;
                    }
                    $result["money"] += $item_value["price"];
                }
                else
                {
                    $result["errors"][] = $item_value["error_message"];
                    $result["result"] = false;
                }
            }
            /* no pii */
            if($parameters["no_pii"] == true)
            {
                $result["money"] += $this->master_handler["config_handler"]->price_ticket_no_pii;
            }
        }
        else
        {
            /* organizer */
            $result["money"] = 0;
        }

        return $result;
    }

    public function save_participant($parameters)
    {
        /*
         parameters.new = true if new participant
         parameters.data = array with participant data
        */

        $result = array(
            "result" => false,
            "response" => '',
            "errors" => array()
        );

        $check = $this->check_money_items($parameters["data"]);

        if($check["result"] == false)
        {
            $result["errors"] = $check["errors"];
            return $result;
        }

        /* check if other parameters as name etc. is ok  */

        if($parameters["new"] == true)
        {
            /* new participant */
            $parameters["data"]["id"] = $this->toolbox->cmac_sha256($parameters["data"]["email"].$parameters["data"]["first_name"].$parameters["data"]["last_name"].date("Y"));
            if($this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_event, $parameters["data"]))
            {
                $result["result"] = true;
                $result["response"] = "participant_added";
            }
            else
            {
                $result["response"] = "error_comm_database";
            }
        }
        else
        {
            /* update participant */
            if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_event, $parameters["data"], "`id`='".$parameters["data"]["id"]."'"))
            {
                $result["result"] = true;
                $result["response"] = "participant_updated";
            }
            else
            {
                $result["response"] = "error_comm_database";
            }
        }
    }

    public function get_ticket($parameters)
    {
        $result = array(
            "result" => true,
            "response" => ''
        );
        $namePattern = '/^[\p{L}]+(?: [\p{L}]+)*$/u';
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        $zipPattern = '/^\d{3}\s?\d{2}$/';

        $info = $this->get_info();

        $parameters["form"]["role"] = $this->master_handler["config_handler"]->default_role;
        $parameters["form"]["pay"] = $parameters["price"];

        $cal_price = $this->check_ticket_price($parameters["form"],$parameters["static_cfg"]);
        if($cal_price != $parameters["price"])
        {
            $result["result"] &= false;
            $result["response"] .= "price_mess, ";
            $parameters["form"]["pay"] = $cal_price;
        }

        /* Check email */
        $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_emails, array("verify"), "`email` = '".$parameters["form"]["email"]."'");

        if($database_result != -1)
        {
            $database_result = $database_result[0];
            if(!$database_result["verify"] == 1)
            {
                /* email not verified */
                $result["result"] &= false;
                $result["response"] .= "email_not_verified, ";
            }

        }
        if($parameters["form"]["no_pii"] == false)
        {
            if(!preg_match($namePattern,trim($parameters["form"]["first_name"])) )
            {
                $result["result"] &= false;
                $result["response"] .= "ticket_first_name_invalid, ";
            }

            if(!preg_match($namePattern,trim($parameters["form"]["last_name"])) )
            {
                $result["result"] &= false;
                $result["response"] .= "ticket_last_name_invalid, ";
            }

            if(!preg_match($datePattern,trim($parameters["form"]["birthday"])) )
            {
                $result["result"] &= false;
                $result["response"] .= "ticket_birthday_invalid, ";
            }

            if($parameters["form"]["address"] == null)
            {
                $result["result"] &= false;
                $result["response"] .= "ticket_address_invalid, ";
            }

            if(!preg_match($zipPattern,trim($parameters["form"]["zip"])) )
            {
                $result["result"] &= false;
                $result["response"] .= "ticket_zip_invalid, ";
            }
            
            if($parameters["form"]["gender"] != 'male' && $parameters["form"]["gender"] != 'female')
            {
                $result["result"] &= false;
                $result["response"] .= "ticke_gender_invalid, ";
            }
        }

        /* parts */
        if($parameters["static_cfg"]["only_friday"] == true)
        {
            $parameters["form"]["part_fri"] = true;
            $parameters["form"]["part_sat1"] = false;
            $parameters["form"]["part_sat2"] = false;
            $parameters["form"]["part_sat3"] = false;
        }
        else
        {
            if(!($parameters["form"]["part_sat1"] == true || $parameters["form"]["part_sat2"] == true || $parameters["form"]["part_sat3"] == true))
            {
                $result["result"] &= false;
                $result["response"] .= "part_mess, ";
            }
        }

        /* check meal */
        if($parameters["static_cfg"]["meal"] == true)
        {
            if($info['response']['meal'][$parameters["form"]["meal"]]["disable"] == true)
            {
                $parameters["form"]["meal"] = null;
                /* put info to user and update the price */
                $result["result"] &= false;
                $result["response"] .= "meal_not_available, ";
            }
        }
        else
        {
            $parameters["form"]["meal"] = null;
        }

        /* check workshops */
        if($info["response"]["workshops"][$parameters["form"]["workshop"]]["disable"] == true)
        {
            $parameters["form"]["workshop"] = null;
            $result["result"] &= false;
            $result["response"] .= "workshop_not_available, ";
            /* put info to user and update the price */
        }

        if($result["result"] == true)
        {
            if($this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("id"), "`email` = '".$parameters["form"]["email"]."' AND `first_name` = '".$parameters["form"]["first_name"]."'AND `last_name` = '".$parameters["form"]["last_name"]."'") != null)
            {
                $result["result"] &= false;
                $result["response"] .= "ticket_already_exists";
            }
            else
            {
                $id_string = $parameters["form"]["email"].$parameters["form"]["first_name"].$parameters["form"]["last_name"];
                $parameters["form"]["id"] = $this->toolbox->cmac_sha256($id_string);
                $result["response"] = print_r($parameters["form"],true);
                if(!$this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_event, $parameters["form"]))
                {
                    $result["result"] &= false;
                    $result["response"] .= "error_comm_database";
                }
                else
                {
                    $sign = $this->toolbox->cmac_sha256($parameters["form"]["id"]);
                    $email_sender = new EmailSender();
                    $result["result"] = true;
                    $result["response"] = $this->master_handler["config_handler"]->url_ticket."?id=".$parameters["form"]["id"]."&sign=".$sign;
                    $email_sender->send_ticket($parameters["form"]["email"],
                        $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_ticket_title"],
                        $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_ticket_desc"],
                        $this->master_handler["config_handler"]->frontend_url."ticket?id=".$parameters["form"]["id"]."&sign=".$sign,
                        $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["open_ticket"],
                        $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["best_regards"],
                        $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["mirak_team"]);
                }

                
            }
        }
        return $result;
    }

    public function get_ticket_info_participant($parameters)
    {
        $result = array(
            "result" => false,
            "response" => 'sing_not_valid'
        );
        if($this->toolbox->verify_hmac($parameters["id"],$parameters["sign"]))
        {
            
            $ticket_data = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("email","want_accommodation","fri_to_sat","sat_to_sun","meal","food_delivered", "pay", "workshop"), "`id`='".$parameters["id"]."'")[0];
            if($ticket_data != null)
            {
                $result["response"] = array();
                $result["response"]["email"]= $ticket_data["email"];
                $result["response"]["food_delivered"]= $ticket_data["food_delivered"];
                $result["response"]["meal"]= $this->master_handler["config_handler"]->meals[$ticket_data["meal"]]["title"];
                $result["response"]["pay"]= $ticket_data["pay"];
                if($ticket_data["register"] == 1)
                {
                    $result["response"]["paid"]= true;
                }
                else
                {
                    $result["response"]["paid"]= false;
                }
                $result["response"]["want_accommodation"]= $ticket_data["want_accommodation"];
                $result["response"]["fri_to_sat"]= $ticket_data["fri_to_sat"];
                $result["response"]["sat_to_sun"]= $ticket_data["sat_to_sun"];
                $result["response"]["workshop"]=  $this->master_handler["config_handler"]->workshops[$ticket_data["workshop"]]["title"];
                $result["result"] = true;
            }
        }

        return $result;
    }

    public function delete_ticket_otp_get($parameters)
    {
        $result = array(
            "result" => false,
            "response" => 'operation_failed'
        );
        $otp = $this->toolbox->generateOtp();
        if($this->master_handler["database_handler"]->update_row( $this->master_handler["config_handler"]->database_name_event,array("otp"=>$otp), "`email` = '".$parameters["email"]."'"))
        {
            $email_sender = new EmailSender();
            $email_sender->send_otp($parameters["email"],$otp, $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_otp_title"], $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_otp_desc"], $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["best_regards"], $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["mirak_team"]);
            $result["result"] = true;
        }

        return $result;
    }

    public function delete_ticket_otp_verify($parameters)
    {
        $result = array(
            "result" => false,
            "response" => 'message_otp_verification_failed'
        );

        $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("otp"), "`id` = '".$parameters["id"]."'");

        if($database_result != -1)
        {
            $database_result = $database_result[0];

            if($database_result["otp"] == $parameters["otp"])
            {

                if($this->delete_ticket($parameters["id"]) == false)
                {
                    $result["result"] = false;
                    $result["response"] = "error_comm_database";
                }
                else
                {
                    $result["result"] = true;
                }
            }
            else
            {
                    $result["result"] = false;
                    $result["response"] = "message_otp_verification_failed";
            }
        }
        else
        {
            $result["result"] = false;
            $result["response"] = "error_comm_database";
        }

        return $result;
    }

    public function read_all_participant()
    {
        $result = array(
            "result" => false,
            "response" =>""
        );

        $result["response"] = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("id","first_name","last_name","role","email"),"1");
        if($result["response"] != null)
        {  
            $result["result"] = true;
        }
        return $result;
    }

    public function check_participant_data($parameters)
    {

    }

    public function read_one_participant($parameters)
    {
        $result = array(
            "result" => false,
            "response" => array()
        );

        $result["response"]["participant"] = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("id","email","birthday","gender","address","zip","meal","state","first_name","last_name","role", "part_fri", "part_sat1", "part_sat2","part_sat3","register","register_time","register_person","want_accommodation","fri_to_sat", "sat_to_sun","food_delivered","food_delivered_time","food_delivered_person","email_notify","pay","workshop","no_pii"),"`id`='".$parameters["id"]."'")[0];
        $result["response"]["workshops"] = $this->get_workshop_info_admin();
        $result["response"]["food"] = $this->get_food_info_admin();
        $result["response"]["accommodation"] = $this->get_accormondation_info();
        $result['response']['prices'] = array();
        $result['response']['prices']['ticket_meal'] = $this->master_handler["config_handler"]->price_ticket_meal;
        $result['response']['prices']['price_ticket_no_meal'] = $this->master_handler["config_handler"]->price_ticket_no_meal;
        $result['response']['prices']['price_ticket_only_friday'] = $this->master_handler["config_handler"]->price_ticket_only_friday;
        $result['response']['prices']['price_ticket_no_pii'] = $this->master_handler["config_handler"]->price_ticket_no_pii;
        $result['response']['prices']['price_one_night'] = $this->master_handler["config_handler"]->price_one_night;
        if($result["response"] != null)
        {
            $result["result"] = true;
        }
        return $result;
    }

    public function fast_update_participant($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );

        if($this->update_status_field($parameters["id"], $parameters["update_data"]["field"], $parameters["update_data"]["value"], $parameters["user_info"]["email"]))
        {
            $result["result"] = true;
            $result["response"] = "operation_was_successful";
        }
        else
        {
            $result["response"] = "operation_was_not_successful";
        }
  
        return $result;
    }


    /**********************************
     * 
     * Private function
     * 
     *********************************/

    
     /**
      * Update the status field of a participant
      * @param mixed $id
      * @param mixed $field (only 'register', 'food_delivered')
      * @param mixed $value
      * @return array{response: string, result: bool|bool}
      */
     private function update_status_field($id, $field, $value, $email_initiator)
    {
        $result = false;

        /* prepare data to update */
        $data_update = array(
            $field => $value,
            $field."_time" => date("Y-m-d H:i:s"),
            $field."_person" => $this->toolbox->user_name_by_email($email_initiator)
        );

        $result= $this->master_handler["database_handler"]->update_row(
            $this->master_handler["config_handler"]->database_name_event,
            $data_update,
            "`id`='".$id."'"
        );
        return $result;
    }

    private function delete_ticket($id)
    {
        $result = true;
        $sql = "DELETE FROM `".$this->master_handler["config_handler"]->database_name_event."` WHERE `id`='".$id."'";

        if(!$this->master_handler["database_handler"]->sql_cmd_execute($sql))
        {
            $result = false;
        }

        return $result;
    }

     private function check_ticket_price($form,$cfg)
    {
        $calculated_price = 0;
        
        /* get default price */
        if($cfg["only_friday"] == true)
        {
            $calculated_price += $this->master_handler["config_handler"]->price_ticket_only_friday;
        }else if($cfg["meal"] == false)
        {
            $calculated_price += $this->master_handler["config_handler"]->price_ticket_no_meal;
        }
        else
        {
            $calculated_price += $this->master_handler["config_handler"]->price_ticket_meal;
        }

        /* check pii */
        if($form["no_pii"] == true)
        {
            $calculated_price += $this->master_handler["config_handler"]->price_ticket_no_pii;
        }

        /* check accommodation */
        if($form["fri_to_sat"] == true)
        {
            $calculated_price += $this->master_handler["config_handler"]->price_one_night;
        }
        
        if($form["sat_to_sun"] == true)
        {
            $calculated_price += $this->master_handler["config_handler"]->price_one_night;
        }

        return $calculated_price;
    }

     private function get_accormodation_info(){
        $config_accormodation = $this->master_handler["config_handler"]->accormodation_info;

        $database = $this->master_handler["config_handler"]->database_name_event;

        /* take male */
        $config_accormodation["male"]["friday_saturday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'male' AND `fri_to_sat`= 1");
        $config_accormodation["male"]["friday_saturday"] = $this->set_status($config_accormodation["male"]["friday_saturday"]);
        
        $config_accormodation["male"]["saturday_sunday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'male' AND `sat_to_sun`= 1");
        $config_accormodation["male"]["saturday_sunday"] = $this->set_status($config_accormodation["male"]["saturday_sunday"]);

        /* take male */
        $config_accormodation["female"]["friday_saturday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'female' AND `fri_to_sat`= 1");
        $config_accormodation["female"]["friday_saturday"] = $this->set_status($config_accormodation["female"]["friday_saturday"]);

        $config_accormodation["female"]["saturday_sunday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'female' AND `sat_to_sun`= 1");
        $config_accormodation["female"]["saturday_sunday"] = $this->set_status($config_accormodation["female"]["saturday_sunday"]);
        return $config_accormodation;
     }

     private function get_workshop_info_admin()
     {
        $config_workshops = $this->master_handler["config_handler"]->workshops;
        $database = $this->master_handler["config_handler"]->database_name_event;

        $workshop_counts = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT `workshop`, COUNT(*) AS count FROM `".$database."` GROUP BY `workshop` ORDER BY count DESC;");
    
        foreach($workshop_counts as $row){
            if($row["workshop"] != null)
            {
                $config_workshops[$row["workshop"]]["ordered"] = $row['count'];
                $rest_place = $config_workshops[$row["workshop"]]['max_count'] - $row['count'];
                if($rest_place <= 0){
                    /* 0 meals */
                    $config_workshops[$row["workshop"]]["disable"] = true;
                    $config_workshops[$row["workshop"]]["warning_show"] = false;
                }
                else if($rest_place <= $config_workshops[$row["workshop"]]['waring_threshold'])
                {
                    /* warning */
                    $config_workshops[$row["workshop"]]["disable"] = false;
                    $config_workshops[$row["workshop"]]["warning_show"] = true;
                }
                else{
                    /* normal state */
                    $config_workshops[$row["workshop"]]["disable"] = false;
                    $config_workshops[$row["workshop"]]["warning_show"] = false;
                }
            }
        }

        return $config_workshops;
    }

    private function get_food_info_admin()
    {
        $config_food = $this->master_handler["config_handler"]->meals;
        $database = $this->master_handler["config_handler"]->database_name_event;

        $workshop_counts = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT `meal`, COUNT(*) AS count FROM `".$database."` GROUP BY `meal` ORDER BY count DESC;");
    
        foreach($workshop_counts as $row){
            $config_food[$row["meal"]]["ordered"] = $row['count'];
            $rest_meal = $config_food[$row["meal"]]['max_count'] - $row['count'];
            if($rest_meal <= 0){
                /* 0 meals */
                $config_food[$row["meal"]]["disable"] = true;
                $config_food[$row["meal"]]["warning_show"] = false;
            }
            else if($rest_meal <= $config_food[$row["meal"]]['waring_threshold'])
            {
                /* warning */
                $config_food[$row["meal"]]["disable"] = false;
                $config_food[$row["meal"]]["warning_show"] = true;
            }
            else{
                /* normal state */
                $config_food[$row["meal"]]["disable"] = false;
                $config_food[$row["meal"]]["warning_show"] = false;
            }
        }

        return $config_food;
    }

    private function get_accormondation_info()
    {
        $config_accormodation = $this->master_handler["config_handler"]->accormodation_info;

        $database = $this->master_handler["config_handler"]->database_name_event;

        /* take male */
        $config_accormodation["male"]["friday_saturday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'male' AND `fri_to_sat`= 1");
        $config_accormodation["male"]["friday_saturday"] = $this->set_status($config_accormodation["male"]["friday_saturday"]);
        
        $config_accormodation["male"]["saturday_sunday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'male' AND `sat_to_sun`= 1");
        $config_accormodation["male"]["saturday_sunday"] = $this->set_status($config_accormodation["male"]["saturday_sunday"]);

        /* take male */
        $config_accormodation["female"]["friday_saturday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'female' AND `fri_to_sat`= 1");
        $config_accormodation["female"]["friday_saturday"] = $this->set_status($config_accormodation["female"]["friday_saturday"]);

        $config_accormodation["female"]["saturday_sunday"]["booked"] = $this->master_handler["database_handler"]->get_count($database, "`gender` = 'female' AND `sat_to_sun`= 1");
        $config_accormodation["female"]["saturday_sunday"] = $this->set_status($config_accormodation["female"]["saturday_sunday"]);
        return $config_accormodation;
    }

     private function set_status($array){
        if($array["booked"] >= $array["max_count"]){
            $array["disable"] = true;
            $array["warning_show"] = false;
        }
        else if($array["booked"] >= ($array["max_count"] - $array["waring_threshold"])){
            $array["disable"] = false;
            $array["warning_show"] = true;
        }
        else{
            $array["disable"] = false;
            $array["warning_show"] = false;
        }
        return $array;
    }

     private function get_meal_info(){

        $config_meals = $this->master_handler["config_handler"]->meals;
             

        $database = $this->master_handler["config_handler"]->database_name_event;
        /* get number of meal  */
        $meal_counts = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT `meal`, COUNT(*) AS count FROM `".$database."` GROUP BY `meal` ORDER BY count DESC;");
    
        foreach($meal_counts as $row){
            if($row["meal"] == null)
            {
                continue; // no meal selected
            }
            if($this->master_handler["config_handler"]->debug == true)
            {
                $config_meals[$row["meal"]]["ordered"] = $row['count'];
            }
            $rest_meal = $config_meals[$row["meal"]]['max_count'] - $row['count'];
            if($rest_meal <= 0){
                /* 0 meals */
                $config_meals[$row["meal"]]["disable"] = true;
                $config_meals[$row["meal"]]["warning_show"] = false;
            }
            else if($rest_meal <= $config_meals[$row["meal"]]['waring_threshold'])
            {
                /* warning */
                $config_meals[$row["meal"]]["disable"] = false;
                $config_meals[$row["meal"]]["warning_show"] = true;
            }
            else{
                /* normal state */
                $config_meals[$row["meal"]]["disable"] = false;
                $config_meals[$row["meal"]]["warning_show"] = false;
            }
        }

        return $config_meals;
    }

    private function get_workshop_info()
    {
        $config_workshops = $this->master_handler["config_handler"]->workshops;



        $database = $this->master_handler["config_handler"]->database_name_event;
        /* get number of meal  */
        $workshop_counts = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT `workshop`, COUNT(*) AS count FROM `".$database."` GROUP BY `workshop` ORDER BY count DESC;");
    
        foreach($workshop_counts as $row){
            if($row["workshop"] != null)
            {
                if($this->master_handler["config_handler"]->debug == true)
                {
                    $config_workshops[$row["workshop"]]["ordered"] = $row['count'];
                }
                $rest_place = $config_workshops[$row["workshop"]]['max_count'] - $row['count'];
                if($rest_place <= 0){
                    /* 0 meals */
                    $config_workshops[$row["workshop"]]["disable"] = true;
                    $config_workshops[$row["workshop"]]["warning_show"] = false;
                }
                else if($rest_place <= $config_workshops[$row["workshop"]]['waring_threshold'])
                {
                    /* warning */
                    $config_workshops[$row["workshop"]]["disable"] = false;
                    $config_workshops[$row["workshop"]]["warning_show"] = true;
                }
                else{
                    /* normal state */
                    $config_workshops[$row["workshop"]]["disable"] = false;
                    $config_workshops[$row["workshop"]]["warning_show"] = false;
                }
            }
        }

   
        return $config_workshops;
    }


    private function check_meal_availability($key_meal, $parameters)
    {
        $result = false;
        $meal = $parameters[$key_meal];
        if($meal == null)
        {
            return true; // no meal selected
        }
        $database = $this->master_handler["config_handler"]->database_name_event;

        $ordered_meals = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT COUNT(*) AS count FROM `".$database."` WHERE `meal` = '".$meal."'")[0]["count"];

        if($ordered_meals <= $this->master_handler["config_handler"]->meals[$meal]["max_count"])
        {
            $result = true;
        }
        else
        {
            $result = false;
        }

        return $result;
    }
    private function check_workshop_availability($key_workshop, $parameters)
    {
        $result = false;
        $workshop = $parameters[$key_workshop];
        if($workshop == null)
        {
            return true; // no workshop selected
        }
        $database = $this->master_handler["config_handler"]->database_name_event;

        $ordered_workshops = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT COUNT(*) AS count FROM `".$database."` WHERE `workshop` = '".$workshop."'")[0]["count"];

        if($ordered_workshops <= $this->master_handler["config_handler"]->workshops[$workshop]["max_count"])
        {
            $result = true;
        }
        else
        {
            $result = false;
        }

        return $result;
    }
    private function check_accormondation_availability($key_accormondation, $parameters)
    {
        $result = false;
        if($key_accormondation == "fri_to_sat")
        {
            $key_accormondationcfg = "friday_saturday";
        }
        else if($key_accormondation == "sat_to_sun")
        {
            $key_accormondationcfg = "saturday_sunday";
        }
        $database = $this->master_handler["config_handler"]->database_name_event;
        $ordered_accormondations = $this->master_handler["database_handler"]->sql_cmd_fetchAll("SELECT COUNT(*) AS count FROM `".$database."` WHERE `".$key_accormondation."` = 1 AND `gender` = '".$parameters["gender"]."'")[0]["count"];
        if($ordered_accormondations <= $this->master_handler["config_handler"]->accormodation_info[$parameters["gender"]][$key_accormondationcfg]["max_count"])
        {
            $result = true;
        }
        else
        {
            $result = false;
        }

        return $result;
    }

    private function scanner_meal($id,$email)
    {
        $result = array(
            "result" => false,
            "response" => array()
        );
        $permissions = new permissions($this->master_handler, $email);
        $participant_data = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("meal", "food_delivered", "email"), "`id`='".$id."'")[0];
        $result["meal"] = $this->master_handler["config_handler"]->meals[$participant_data["meal"]]["title"];
        $result["pay"] = "";
        $result["email"] = $participant_data["email"];
        if($participant_data["food_delivered"] == 1)
        {
            $result["result"] = false;
            $result["response"] = 'meal_already_delivered';
        }
        else
        {
            $scanner_meal_permissions = $permissions->get_meal_scanner_permissions();
            if($scanner_meal_permissions == $participant_data["meal"])
            {
                $this->update_status_field($id, "food_delivered", 1, $email);
                $result["result"] = true;
                $result["response"] = 'meal_delivered';
            }
            else
            {
                $result["result"] = false;
                $result["response"] = 'meal_different_location';
            }
        }
        return $result;
    }
    private function scanner_registration($id,$email)
    {
        $result = array(
            "result" => false,
            "response" => 'participant_not_found'
        );
        $participant_data = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("register", "meal", "pay", "email"), "`id`='".$id."'")[0];
        $result["meal"] = $this->master_handler["config_handler"]->meals[$participant_data["meal"]]["title"];
        $result["pay"] = $participant_data["pay"];
        $result["email"] = $participant_data["email"];
        if($participant_data["register"] == 1)
        {
            $result["result"] = false;
            $result["response"] = 'participant_already_registered';
        }
        else
        {
           $this->update_status_field($id, "register", 1, $email);
           $result["result"] = true;
           $result["response"] = 'participant_registered';
        }

        return $result;
    }
    

}
$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new ticket_controler($master_handler, $toolbox));
exit();
?>