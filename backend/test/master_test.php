<?php

class master_test
{
    public function error_trigger()
    {
        trigger_error("This is a test error.", E_USER_NOTICE);
        throw new Exception("This is a test exception.");
    }
}

?>