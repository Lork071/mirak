<?php

class error_controler{

    private $database;
    private $config;

    public function __construct($database_handler, $config_handler)
    {
        $this->database = $database_handler;
        $this->config = $config_handler;
    }

    /*************************************
     * Set global handlers and exception
     ************************************/
    public function register() {
        set_error_handler([$this, 'handle_error']);
        set_exception_handler([$this, 'handle_exception']);
        register_shutdown_function([$this, 'handle_shutdown']);
    }

    /*************************************
     * Methots of handeling of errors
     ************************************/
    public function handle_error($errno, $errstr, $errfile, $errline) 
    {
        $this->log_error('Error', $errno, $errstr, basename($errfile), $errline);
        return true; /* Suppress of clasical handle of PHP exceptions */
    }

    /*************************************
     * Methots of handeling of exeption
     ************************************/
    public function handle_exception($exception) {
        $this->log_error('Exception', $exception->getCode(), $exception->getMessage(), basename($exception->getFile()), $exception->getLine());
    }

    /*************************************
     * Methots of handeling fatals errors
     ************************************/
    public function handle_shutdown() {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
            $this->log_error('Shutdown', $error['type'], $error['message'], basename($error['file']), $error['line']);
        }
    }

    public function log_user_error($user_id, $short_text, $long_text)
    {
        $array_data = array(
            "user_id"    => $user_id,
            "short_text" => $short_text,
            "long_text"  => $long_text
        );

        if(!$this->database->insert_row($this->config->database_name_user_error,$array_data))
        {
            /* how to handle it? */
        }

    }

    /*************************************
     * Methots of saving errors
     ************************************/
    private function log_error($type, $code, $message, $file, $line) 
    {
        if(isset($_SESSION["email"]))
        {
            $user = $_SESSION["email"];
        }
        else
        {
            $user = "NAN";
        }
        
        $array_data = array(
            "type"    => $type,
            "code"    => $code,
            "message" => $message,
            "file"    => $file,
            "line"    => $line,
            "user"    => $user
        );

        if(!$this->database->insert_row($this->config->database_name_error,$array_data))
        {
            /* how to handle it? */
        }

    }
    
}

?>