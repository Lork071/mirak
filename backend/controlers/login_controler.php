<?php
require_once '../main_core.php';
require_once '../tools/toolbox.php';
require_once '../tools/permissions.php';
require_once '../tools/email_sender.php';
require_once '../vendor/autoload.php';

class login_controler{

    private $master_handler;
    private $toolbox;

    public function __construct($master_handler, $toolbox)
    {
        $this->master_handler = $master_handler;
        $this->toolbox = $toolbox;
    }


    public function load_page($parameters)
    {
        $session_data = $this->check_session();
        $result = array(
            "result" => false,
            "response" => "/intro"
        );

        if($session_data["result"] == true)
        {  
            if($this->master_handler["config_handler"]->check_access_page($session_data["response"]["user_info"]["credentials"],$parameters["page"]))
            {
                /* user have access to this page */
                $result["result"] = true;
                $result["response"] = array();
                $result["response"]["user_info"] = $session_data["response"]["user_info"];

            }
            else
            {
                $result["result"] = false;
                $result["response"] = "/auth/access";
            }
        }
        else
        {
            $result["result"] = false;
            $result["response"] = "/auth/login";
            $result["aaa"] = $_SESSION;
        }
        return $result;
    }

    public function get_user_info($parameters)
    {
        $result = array(
            "result" => false,
            "response" => "/auth/login"
        );

        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if(isset($_SESSION["email"]))
        {
            $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array("email","FirstName", "LastName","permission"), "`email` = '".$_SESSION["email"]."'");
            if($database_result != null)
            {
                $result["result"]= true;
                $database_result = $database_result[0];
                $_SESSION = $database_result;
                $result["response"] = array();
                $result["response"]["user_info"] = $_SESSION;
                $permissions = new permissions($this->master_handler, $database_result["email"]);
                $result["response"]["user_info"]["premissions_pages"] = $permissions->get_pages(); 
                $result["response"]["user_info"]["premissions_operations"] = $permissions->get_operations(); 
                $result["response"]["pages_with_permissions"] = $this->master_handler["config_handler"]->permissions_pages;

                if (in_array(basename($parameters["page_path"]), $this->master_handler["config_handler"]->permissions_pages))
                {
                    if (!in_array(basename($parameters["page_path"]), $permissions->get_pages()))
                    {
                        $result["result"] = false;
                        $result["response"] = "/auth/access";
                    }
                }
            }
        }

        return $result;
    }

    public function check_session()
    {
        $is_log = false;
        $result = array(
            "result" => false,
            "response" => array()
        );
        session_start();
        if(isset($_SESSION["email"]))
        {
            /* user is log in */
            $result["result"] = true;
            $result["response"]["user_info"] = $_SESSION;
        }
        else
        {  
            /* check if cookie token exist */

        }

        return $result;
    }

    public function logout()
    {
        $result = array(
            "result" => true
        );
        
        session_start();
        // Destroy the session
        session_destroy();

        return $result;
    }

    public function sign_up($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );

        $parameters["email"] = strtolower($parameters["email"]);

        /* check if email already exists */
        $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array("email","source"), "`email` = '".$parameters["email"]."'");

        if($database_result != null)
        {
            /* email already exists */
            $result["result"] = false;
            $result["response"] = array(
                "title" => "sig_up_email_exists_title",
                "desc" => "sig_up_email_exists_desc"
            );
        }
        else
        {
            /* email does not exist, so create new user */
            $hash_password = hash('sha256', $parameters["password"]);
            $data = array(
                "email" => $parameters["email"],
                "FirstName" => $parameters["first_name"],
                "LastName" => $parameters["last_name"],
                "hash_password" => $hash_password,
                "permission" => $this->master_handler["config_handler"]->default_permission,
                "source" => "app"
            );

            if($this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_users, $data) == true)
            {
                $result["result"] = true;
                $result["response"] = array(
                    "title" => "sig_up_success_title",
                    "desc" => "sig_up_success_desc"
                );
            }
        }

        return $result;
    }

    public function login($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );
        
        $user_info = array();

        /* perform hash of the password */
        $hash_password = hash('sha256', $parameters["password"]);

        $parameters["email"] = strtolower($parameters["email"]);

        $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, "all", "`email` = '".$parameters["email"]."'");

        $result["result"] = false;
        $result["response"] = array(
            "title" => "sig_in_bad_login_title",
            "desc" => "sig_in_bad_login_desc"
        );
        if($database_result != null)
        {
            $database_result = $database_result[0];
            if($hash_password == $database_result["hash_password"])
            {
                /* password was OK */
                session_start([
                    'use_strict_mode' => true, 
                    'cookie_httponly' => true,
                    'cookie_secure' => true, 
                    'use_only_cookies' => true
                ]);
                $_SESSION["email"] = $database_result["email"];
                $_SESSION["first_name"] = $database_result["FirstName"];
                $_SESSION["last_name"] = $database_result["LastName"];
                $_SESSION["permission"] = $database_result["permission"];
                $_SESSION["gender"] = $database_result["gender"];
                $result["result"] = true;
                $result["response"] = $_SESSION;

                if($parameters["remember"])
                {
                    $data = array();
            
                    $data["token"] = hash('sha256', $this->toolbox->generateUuid() . strtolower($parameters["email"]));
                    $data["expiration"] = $this->toolbox->addSecondsToTimestamp($this->master_handler["config_handler"]->token_expiration_sec);
                    $this->toolbox->createCookie("log_token", $data["token"], 30);

                    $json = json_encode($data);

                    if(!$this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_users, array("token"=> $json), "`email` = '".$parameters["email"]."'" ))
                    {
                        $result["result"] = false;
                        $result["response"] = array(
                            "title" => "error",
                            "desc" => "error_comm_api"
                        );
                    }

                }
    
            }
        }
        return $result;
    }

    public function login_google_check($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );

        $client = $this->google_client();

        if(isset($parameters["code"]))
        {
            $token = $client->fetchAccessTokenWithAuthCode($parameters["code"]);
            if(isset($token["error"]))
            {
                $this->master_handler["error_handler"]->log_error("google_login", "Google login error: ".$token["error"]. ". With description: ".$token["error_description"]);
                $result["result"] = false;
                $result["response"] = 'token_error';
            }
            else
            {
                $client->setAccessToken($token);
                $oauth = new Google_Service_Oauth2($client);
                $userinfo = $oauth->userinfo->get();
                $user_data = array(
                    "email" => $userinfo->email,
                    "FirstName" => $userinfo->givenName,
                    "LastName" => $userinfo->familyName,
                    "picture" => $userinfo->picture,
                    "source" => 'google',
                    "permission" => $this->master_handler["config_handler"]->default_permission
                );
                $result = $this->set_user_third($user_data);
            }
        }

        return $result;
    }

    public function email_verify($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );
        

        $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_emails, array("verify"), "`email` = '".$parameters["email"]."'");

        if($database_result != null)
        {
            $database_result = $database_result[0];
            if($database_result["verify"] == true)
            {
                /* email verified */
                $result["result"] = true;
            }
            else
            {
                $result["result"] = false;
                $otp = $this->toolbox->generateOtp();
                if($this->master_handler["database_handler"]->update_row( $this->master_handler["config_handler"]->database_name_emails,array("otp"=>$otp), "`email` = '".$parameters["email"]."'"))
                {
                    $email_sender = new EmailSender();
                    $email_sender->send_otp($parameters["email"],
                    $otp, 
                    $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_otp_title"], 
                    $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_otp_desc"],
                    $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["best_regards"],
                    $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["mirak_team"]);
                }
            }
        }
        else
        {
            $result["result"] = false;
            $otp = $this->toolbox->generateOtp();
            if($this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_emails, array("email"=>$parameters["email"],"otp"=>$otp)))
            {
                $email_sender = new EmailSender();
                $email_sender->send_otp($parameters["email"],
                $otp, 
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_otp_title"], 
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["email_otp_desc"],
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["best_regards"],
                $this->master_handler["config_handler"]->lang_text[$parameters["lang"]]["mirak_team"]);
            }

        }

        return $result;

    }

    public function verify_otp($parameters)
    {
        $result = array(
            "result" => false,
            "response" => ""
        );
        
        $database_result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_emails, array("otp"), "`email` = '".$parameters["email"]."'");

        if($database_result != null)
        {
            $database_result = $database_result[0];

            if($database_result["otp"] == $parameters["otp"])
            {
                $result["result"] = true;
                if($this->master_handler["database_handler"]->update_row( $this->master_handler["config_handler"]->database_name_emails,array("verify"=>true), "`email` = '".$parameters["email"]."'"))
                {
                    $result["result"] = true;
                }
                else
                {
                    $result["result"] = false;
                    $result["response"] = "error_comm_database";
                }
            }
            else
            {
                    $result["result"] = false;
                    $result["response"] = "message_otp_verification_failed";
            }
        }

        return $result;
    }

    public function google_link()
    {
        $result = array(
            "result" => false,
            "response" => ""
        );

        $client = $this->google_client();

        $google_login_url = $client->createAuthUrl();

        $result["result"] = true;
        $result["google_link"] = $google_login_url;
        return $result;
    }

    public function google_client()
    {
        $client = new Google_Client();
        $client->setClientId($this->master_handler["config_handler"]->google_client_id);
        $client->setClientSecret($this->master_handler["config_handler"]->google_client_secret);
        $client->setRedirectUri($this->master_handler["config_handler"]->google_auth_callback);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope("email");
        $client->addScope("profile");

        return $client;
    }

    /*************************************
     * Set global handlers and exception
     ************************************/
    private function set_user_third($user_data)
    {
        
        $result = array(
            "result" => false,
            "response" => ""
        );
        
        $user_data_database = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array('email','FirstName','LastName', 'source', 'picture'), "email = '" . $user_data["email"] . "'");

        if($user_data_database != null)
        {
            /* Email exists */
            $user_data_database = $user_data_database[0];
            if($user_data_database["source"] == $user_data["source"])
            {
                /* User already exists with the same source */
                $result["result"] = true;
                $result["response"] = "user_exists_logged_in";
            }
            else
            {
                /* User exists but with different source */
                if($user_data_database["source"] == "app")
                {
                    $result["response"] = "user_merge_with_app";
                    $result["result"] = $result["result"] = $this->master_handler["database_handler"]->update_row($this->master_handler["config_handler"]->database_name_users, array('source' => $user_data["source"]), "email = '" . $user_data["email"] . "'");
                }
                else
                {
                    $result["response"] = "user_already_exists_different_source";
                    $result["result"] = false;
                }
            }
        }
        else
        {
            /* Email not exist so create the new user */
            $result["result"] = $this->master_handler["database_handler"]->insert_row($this->master_handler["config_handler"]->database_name_users, $user_data);
            if(!$result["result"])
            {
                $result["response"] = "error_comm_database";
                return $result;
            }
            else
            {
                $result["response"] = "user_account_created"; 
            }

        }

        if($result["result"] == true)
        {
            if(session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION["email"] = $user_data["email"];
            $_SESSION["first_name"] = $user_data["FirstName"];
            $_SESSION["last_name"] = $user_data["LastName"];
            $_SESSION["picture"] = $user_data["picture"];
            $_SESSION["permission"] = $user_data["permission"];
        }


        return $result;

    }

}

$toolbox = new toolbox($master_handler);

echo $toolbox->api_receiver(new login_controler($master_handler,$toolbox));
exit;

?>