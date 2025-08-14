<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';
require_once '../tools/email_sender.php';
require_once '../tools/permissions.php';

class static_config_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
    }

    public function get_intro_cfg($parameters)
    {
        $result = array(
            'result' => false,
            'response' => '',
        );

        $result["response"] = $this->master_handler["database_handler"]->read_static_config($this->master_handler["config_handler"]->database_name_static, 
        array('OnlyIntroPage', 'UseIntroVideo', 'UseIntroFeatures', 'UseIntroVision','UseVenue','UseTickets', 
        'UseContentVolunteer','UseContact', 'UseFaq', 'UseMirakAccount','IntroBar','isLive', 'LiveLink'));

        foreach ($result["response"] as $key => $value) {
            if ($value === "true") {
                $result["response"][$key] = true;
            } elseif ($value === "false") {
                $result["response"][$key] = false;
            }
            else
            {
                $result["response"][$key] = $value;
            }
        }

        if($result["response"] != null)
        {
            $result["result"] = true;
        }

        return $result;
    }

    public function update_cfg($parameters)
    {
        $result = array(
            'result' => false,
            'response' => '',
        );

        if($this->master_handler["database_handler"]->write_static_config($this->master_handler["config_handler"]->database_name_static, $parameters["static_config"]))
        {
            $result["result"] = true;
        }

        return $result;
    }


}
$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new static_config_controler($master_handler, $toolbox));
exit();
?>