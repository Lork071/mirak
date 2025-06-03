<?php
require_once 'permissions.php';
require_once 'email_sender.php';

class toolbox {

    private $master_handler;

    public function __construct($master_handler) {
        $this->master_handler = $master_handler;
    }
    // Vytvoření klientského cookie
    public static function createCookie($name, $value, $days = 30) {
        $expire = time() + (86400 * $days); // Nastavení expirace na zadaný počet dní
        setcookie($name, $value, $expire, "/"); // Vytvoření cookie s názvem, hodnotou a expirací
    }

    public static function get_cookie($cookie_name) {
        // Check if the cookie is set
        if (isset($_COOKIE[$cookie_name])) {
            // Return the value of the cookie
            return $_COOKIE[$cookie_name];
        } else {
            // Cookie is not set, return false or any other default value
            return false;
        }
    }

    // Odstranění klientského cookie
    public static function eraseCookie($name) {
        setcookie($name, '', time() - 3600, '/'); // Nastavení cookie s vypršelou expirací pro odstranění
    }

    // Metoda pro získání hodnoty cookie podle jména
    public static function getCookie($name) {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name]; // Pokud cookie existuje, vrátí její hodnotu
        }
        return null; // Pokud cookie není nalezena
    }

    public function is_sha256($str) {
    return (bool)preg_match('/^[a-f0-9]{64}$/i', $str);
    }

    public static function generateUuid() {
        // Use random_bytes to generate random bytes
        $data = random_bytes(16);

        // Set version and variant of UUID as per RFC 4122 specification
        // Version 4 UUID format is "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
        // where y is a random value with specific bit settings (variant)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        // Format the UUID into the correct form
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $uuid;
    }

    public static function addSecondsToTimestamp($seconds) {
        // Get the current timestamp
        $currentTimestamp = time();
        
        // Add the specified number of seconds
        $newTimestamp = $currentTimestamp + $seconds;
        
        return $newTimestamp; // Return the new timestamp
    }

    public function api_receiver($handler)
    {
        $response = array(
            "result" => false,
            "response" => ""
        );

        /* get received data */
        $data = json_decode(file_get_contents('php://input'), true);

        /* if data or methot is not set */

        if (!$data || !isset($data["method"])) {
            //http_response_code(400); // Chybný požadavek
            $response["result"] = false;
            $response["response"] = "error api invalid input";
        }
        else
        {
            $method = $data["method"]; /* get method */
            if (method_exists($handler, $method)) {
                /* check if the method is exist in class */
                /* Check if method needs have permission */
                if(in_array($method,$this->master_handler["config_handler"]->permissions_operations))
                {  
                    if(isset($data["user_info"]))
                    {
                        /* Methots needs permission */
                        $response = $this->check_operation($method,$data["user_info"]);
                    }
                    else
                    {
                        $response["result"] = false;
                        $response["response"] = "error api invalid input";
                    }
                    
                }
                else
                {
                    $response["result"] = true;
                }
                if($response["result"] == true)
                {
                    /* call method */
                    if(isset($data["parameters"]))
                    {
                        if(isset($data["user_info"]))
                        {
                            $data["parameters"]["user_info"] = $data["user_info"];
                        }
                        $parameters = $data["parameters"];
                        $response = $handler->$method($parameters); 
                    }
                    else
                    {
                        $response = $handler->$method(); 
                    }
                }
            }
            else
            {
                $response["result"] = false;
                $response["response"] = "Called method " .$method. " is not exist";
            }
        }

        $json = json_encode($response, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            die(json_encode(["result" => false, "response" => json_last_error_msg()]));
        }
        return $json;
    } 

    public function check_operation($operation, $user_data)
    {
        $result = array(
            "result" => false,
            "response" =>array(
                "error_title" => "error",
                "error_desc" => "error_msg_not_operation_permission",
                "debug" => ""
            )
        );
        $user_operations = array();

        $permissions = new permissions($this->master_handler, $user_data["email"]);
        $user_operations= $permissions->get_operations(); 

        if((in_array($operation, $user_operations)) && ($permissions->get_permissions_id() == $user_data["permission"]))
        {
            $result["result"] = true;
        }
        return $result;

    }

    public function generateOtp($length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $otp;
    }

    public function cmac_sha256($message) {
        return bin2hex(hash_hmac('sha256', $message, $this->master_handler["config_handler"]->ticket_key, true));
    }

    public function verify_hmac(string $message, string $sign): bool {
        $calculated_mac = $this->cmac_sha256( $message);
        return hash_equals($calculated_mac, $sign);
    }

    public function user_name_by_email($email)
    {
        $database_name = $this->master_handler["config_handler"]->database_name_users;

        $result = $this->master_handler["database_handler"]->read_row($this->master_handler["config_handler"]->database_name_users, array("FirstName","LastName"), "`email` = '".$email."'")[0];

        return $result["FirstName"]." ".$result["LastName"];
    }
}
?>
