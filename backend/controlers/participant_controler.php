<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';

class participant_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
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

    public function read_one_participant($parameters)
    {
        $result = array(
            "result" => false,
            "response" => array()
        );

        $result["response"]["participant"] = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_event, array("id","email","birthday","gender","address","zip","meal","state","first_name","last_name","role", "part_fri", "part_sat1", "part_sat2","part_sat3","register","register_time","register_person","want_accommodation","fri_to_sat", "sat_to_sun","food_delivered","food_delivered_time","food_delivered_person","email_notify","pay","paid","workshop","no_pii"),"`id`='".$parameters["id"]."'")[0];
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

        $database = $this->master_handler["config_handler"]->database_name_event;
        $id = $parameters["id"];
        unset($parameters["id"]);
        
        if($this->master_handler["database_handler"]->update_row($database, $parameters["update_data"], "`id`='".$id."'"))
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

    /**************************************
     * 
     * Private functions
     * 
     **************************************/

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
    
}
$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new participant_controler($master_handler, $toolbox));
exit();
?>