<?php

class database_controler
{
    private $config;
    
    public function __construct($config_handler) 
    {
        $this->config = $config_handler;
    }

    /**********************************************
     * Single-Connection Pattern is used
     * ********************************************/
    private function open_connection()
    {
        $host = $this->config->database_server_name;
        $db_name = $this->config->database_name;
        try
        {
            $connection = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $this->config->database_user_name, $this->config->database_password,);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $connection;
        }
        catch (PDOException $e)
        {
            /*  Fixme: how can be handle it */
            return null;
        }
    }

    public function read_row($database_name, $columns_to_select, $where)
    {
        /* prepare which columns will be selected */
        $select_string = "";
        $return_value = -1;
        if(is_array($columns_to_select))
        {
            foreach($columns_to_select as $column)
            {
                $select_string .= "`" . $column ."`, ";
            }
            $select_string = substr($select_string, 0, -2);
        }
        else
        {
            if(strtolower($columns_to_select) == "all")
            {
                $select_string = "*";
            }
            else
            {
                /* bad input */
            }
        }

        if($where != "")
        {
            $sql_command = 'SELECT '.$select_string.' FROM `'.$database_name.'` WHERE ' . $where;
        }
        else
        {
            $sql_command = 'SELECT '.$select_string.' FROM `'.$database_name.'`';
        }
        $connection = $this->open_connection();

        $stmt = $connection->query($sql_command);

        if ($stmt->rowCount() > 0) {
            $return_value = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            $return_value = null;
        }
        return $return_value;

    }

    public function delete_row($database_name, $condition)
    {
        $result = false;
        
        $sql_command = 'DELETE FROM `'.$database_name.'` WHERE '.$condition;

        $connection = $this->open_connection();

        if($connection)
        {
            $stmt = $connection->query($sql_command);
            $stmt->execute();
            $result = true;
        }
        
        return $result;
    }

    public function update_row($database_name, $structure_data, $condition)
    {
        /* Prepare SQL command */
        $set_string = "";
        $lastKey = array_key_last($structure_data);
        
        foreach ($structure_data as $col => $data) {
            if ($col === $lastKey) {
                /* last column */
                $set_string .= "`".$col."` = '".$data."'";
            } else {
                /* not last column */
                $set_string .= "`".$col."` = '".$data."', ";
            }
        }

        // Prepare the full SQL query
        $sql_command = "UPDATE `".$database_name."` SET ".$set_string." WHERE ".$condition;

        // Open the database connection and execute the query
        $connection = $this->open_connection();
        if ($connection) {
            $stmt = $connection->prepare($sql_command);
            $stmt->execute();
        } else {
            /* Handle connection failure */
            return false;
        }
        return true;
    }


    /**********************************************
     * Insert row
     * ********************************************/
    /*
     * Data for insert needs to be prepare in the array:
     * $structure_data = array(
     *     "column_name_1" => "data_1",
     *     "column_name_2" => "data_2",
     *                 .
     *                 .
     *                 .
     *      "column_name_n" => "data_n",
     * );
     */

    public function insert_row($database_name, $structure_data)
    {
        /* Prepare SQL command */
        $columns_string = "(";
        $data_string = "(";
        $lastKey = array_key_last($structure_data);
        foreach ($structure_data as $col => $data)
        {
            if ($col === $lastKey) 
            {
                /* last */
                $columns_string .= "`".$col."`)";
                $data_string .= "'".$data."')";
            }
            else
            {
                /* not last */
                $columns_string .= "`".$col."`, ";
                $data_string .= "'".$data."', ";
            }
        }
 
        $sql_command = "INSERT INTO `".$database_name."` ".$columns_string." VALUES ".$data_string;
        $connection = $this->open_connection();
        if($connection)
        {
            $stmt = $connection->prepare($sql_command);
            $stmt->execute();
        }
        else
        {
            /* fixme: hot to handle */
            return false;
        }
        return true;

    }
    

    public function sql_cmd_execute($sql_command)
    {
        $connection = $this->open_connection();
        if($connection)
        {
            $stmt = $connection->prepare($sql_command);
            $stmt->execute();
        }
        else
        {
            /* fixme: hot to handle */
            return false;
        }
        return true;
    }
    
    public function sql_cmd_fetchAll($sql_command)
    {
        $return_value = -1;
        
        $connection = $this->open_connection();

        $stmt = $connection->query($sql_command);

        if ($stmt->rowCount() > 0) {
            $return_value = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $return_value;
        
    }

    public function get_count($database, $condition)
    {
        $return_value = -1;
        
        $connection = $this->open_connection();
        $sql_command = 'SELECT COUNT(*)  AS total_count FROM `'.$database.'` WHERE '.$condition;

        $stmt = $connection->query($sql_command);

        if ($stmt->rowCount() > 0) {
            $return_value = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $return_value[0]['total_count']; 
    }

}

?>