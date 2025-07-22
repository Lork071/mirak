<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';
require_once '../tools/email_sender.php';
require_once '../tools/permissions.php';

class volunteer_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
    }

    public function send_volunteer($parameters)
    {
        
        $result = array(
            'result' => false,
            'response' => '',
        );
        $database_row = array(
            'first_name' => $parameters['first_name'] ?? '',
            'last_name' => $parameters['last_name'] ?? '',
            'phone_number' => $parameters['phone_number'] ?? '',
            'email' => $parameters['email'] ?? '',
            'note' => $parameters['volunteer_note'] ?? '',
        );

        if(empty($database_row['first_name']) || empty($database_row['last_name']) || empty($database_row['phone_number']) || empty($database_row['email'])) {
            $result['result'] = false;
            $result['response'] = 'volunteer_missing_form_data';
            return $result;
        }
        if($this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_mirak_crew, $database_row)) {
            $result['result'] = true;
            $result['response'] = 'volunteer_form_sent';
            $email_sender = new EmailSender();
            $email_sender->send_email($parameters["email"],
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["volunteer_form_sent_title"],
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["volunteer_form_sent"],
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["best_regards"],
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["mirak_team"]);
        } else {
            $result['result'] = false;
            $result['response'] = 'error_comm_api';
        }
        return $result;
    }

    public function read_all_mirak_crew()
    {
        $result = array(
            'result' => false,
            'response' => '',
        );
        $sql_command = "SELECT * FROM `".$this->master_handler["config_handler"]->database_name_mirak_crew."`";
        $volunteers = $this->master_handler["database_handler"]->sql_cmd_fetchAll($sql_command);
        if($volunteers) {
            $result['result'] = true;
            $result['response'] = $volunteers;
        } else {
            $result['result'] = false;
            $result['response'] = 'error_comm_api';
        }
        return $result;
    }

    public function read_mirak_crew_person($parameters)
    {
        $result = array(
            'result' => false,
            'response' => array(),
        );
        if(!isset($parameters['id']) || empty($parameters['id'])) {
            $result['result'] = false;
            $result['response']['desc'] = 'error_comm_api';
            return $result;
        }
        $volunteer = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_mirak_crew,"all", "`id`='".$parameters['id']."'")[0];
        if($volunteer) {
            $result['result'] = true;
            $result['response'] = $volunteer;
        } else {
            $result['result'] = false;
            $result['response']['desc'] = 'error_comm_api';
        }
        return $result;
    }

    public function mark_as_contacted($parameters)
    {
        $result = array(
            'result' => false,
            'response' => array(),
        );
        if(!isset($parameters['id']) || empty($parameters['id'])) {
            $result['result'] = false;
            $result['response']['desc'] = 'error_comm_api';
            return $result;
        }
        $volunteer = array();
        $volunteer['processed'] = ($parameters['processed'] == true) ? 1 : 0;
        if($this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_mirak_crew, $volunteer, "`id`='".$parameters['id']."'")) {
            $result['result'] = true;
        } else {
            $result['result'] = false;
            $result['response']['desc'] = 'error_comm_api';
        }
        return $result;
    }

    public function delete_mirak_crew_person($parameters)
    {
        $result = array(
            'result' => false,
            'response' => array(),
        );
        if(!isset($parameters['id']) || empty($parameters['id'])) {
            $result['result'] = false;
            $result['response']['desc'] = 'error_comm_api';
            return $result;
        }
        if($this->master_handler["database_handler"]->delete_row($this->master_handler["config_handler"]->database_name_mirak_crew, "`id`='".$parameters['id']."'")) {
            $result['result'] = true;
            $result['response']['desc'] = 'deleted_successfully';
        } else {
            $result['result'] = false;
            $result['response']['desc'] = 'error_comm_api';
        }
        return $result;
    }
    

}
$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new volunteer_controler($master_handler, $toolbox));
exit();
?>