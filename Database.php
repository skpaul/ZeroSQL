
<?php 

    class query_result {
        public $success;
        public $count;
        public $row;
        public $rows;
        public $affected_rows;
        public $auto_id;
    }

    class Database
    {
        private $php_version = 5;
        private $sql = "";
        private $column_name = "";
        private $column_names = ""; //column names
        private $mysql_function = "";

        private $table_name = ""; //table name
        private $alias_name = "";
        private $where = ""; //where clause 
        private $order_by = "";
        private $query_type = ""; //SELECT, INSERT, UPDATE, DELETE
        private $data_array = [];
        private $connection;
        private $logger;
        private $fetch_type = "fetch_assoc";
        private $skip_quantity = 0; //used with select()->many();
        private $take_quantity = 0; //used with select()->many();
        private $projection_name = "";
        private $values = "";
        private $return_last_inserted_auto_id = false;
        private $set;
        private $group_by = "";
        private $having= "";
        private $use_transaction = false;
        private $distinct = "";
        private $last_call_where_or_having = ""; //Condition is stored on the same EqualsTo() method. So we need to know what was the last call.

        private function _reset_private_variables(){
            $this->sql = "";
            $this->column_name = "";
            $this->column_names = "";
            $this->mysql_function = "";
            $this->table_name = "";
            $this->alias_name = "";
            $this->where = "";
            $this->order_by = "";
            $this->query_type = "";
            $this->data_array = [];
            $this->fetch_type = "fetch_assoc";
            $this->skip_quantity = 0;
            $this->take_quantity = 0;
            $this->projection_name = "";
            $this->values = "";
            $this->return_last_inserted_auto_id = false;
            unset($this->set);
            $this->group_by = "";
            $this->having = "";
            $this->use_transaction = false;
            $this->distinct = "";
            $this->last_call_where_or_having = "";
        }

        //used in _mysql_query()
        private function _create_mysql_query_error_log($sql){
            if($this->php_version == 5){
                $error = "mysql_error:". mysql_error($this->connection);
                mysql_close($this->connection);
            }
            else{
                $error = "mysqli_error:". mysqli_error($this->connection);
                mysqli_close($this->connection);
            }

            $error_description = "Failed to execute the following SQL statement: $sql. " . $error;
            $this->logger->create_log($error_description);
        }

        private function _mysql_query($sql){
            //Returns FALSE on failure. Otherwise will return a mysql_result object.
            //$this->logger->create_log($sql);
            if($this->php_version == 5){
                $query = mysql_query($sql, $this->connection);
            }
           else{
                $query = mysqli_query($this->connection, $sql);
           }

            if ($query){
                return $query;
            }
            else{
                $this->_create_mysql_query_error_log($sql);
                mysqli_rollback($this->connection);
                mysqli_close($this->connection);
            }
        }


        /*
        mysql_fetch_array() — Fetch a result row as an associative array, a numeric array, or both
        mysql_fetch_row() - Get a result row as an enumerated array
        mysql_fetch_assoc() - Fetch a result row as an associative array
        mysql_fetch_field — Get column information from a result and return as an object
        */
        public function fetch_assoc(){
            $this->fetch_type = "fetch_assoc";
            return $this;
        }

        public function fetch_array(){
            $this->fetch_type = "fetch_array";
            return $this;
        }

        public function fetch_row(){
            $this->fetch_type = "fetch_row";
            return $this;
        }

        public function fetch_field(){
            $this->fetch_type = "fetch_field";
            return $this;
        }

        //Constructor this class.
        //If user provides values in this, it will call connect() method.
        //Otherwise, user have to call connect() method by himself.
        public function __construct() {
            $get_arguments       = func_get_args();
            $number_of_arguments = func_num_args();

            if($number_of_arguments == 4){
                call_user_func_array(array($this, "connect"), $get_arguments);
            }

            if($number_of_arguments == 1){
                $this->logger = $get_arguments[0];
            }
        }

        public function __destruct(){
            if($this->php_version == 5){
                mysql_close($this->connection);
            }
            else{
                mysqli_close($this->connection);
            }
        }


        private $server = "";
        public function Server($database_server) {
            $this->server = $database_server;
            return $this;
        }

        private $user = "";
        public function User($user_name) {
            $this->user = $user_name;
            return $this;
        }

        private $password = "";
        public function Password($password) {
            $this->password = $password;
            return $this;
        }

        private $database = "";
        public function Database($database) {
            $this->database = $database;
            return $this;
        }

        public function Connect() {
            if($this->php_version == 5){
                $this->connection = mysql_connect($this->server, $this->user, $this->password, $this->database); 
                if (!$this->connection) {
                    $this->logger->create_log('Failed to connect database server. mysql_error:' . mysql_error());
                    die('ERROR CODE: ARPOASRUWWER412547');
                } 
            }
            else{
                $this->connection = mysqli_connect($this->server, $this->user, $this->password, $this->database);  
                if (!$this->connection) {
                    $error = "mysqli_error:". mysqli_error($this->connection) .". mysqli_connect_errno:". mysqli_connect_errno() ." mysqli_connect_error:" .  mysqli_connect_error() ;  

                    $this->logger->create_log('Failed to connect database server. ' . $error);
                    die('ERROR CODE: ARPOASRUWWER412547');
                }
            }
            
            if($this->php_version == 5){
                if(!mysql_select_db($this->database, $this->connection)){
                    $this->logger->create_log('Could not select database. mysql_error: ' . mysql_error($this->connection));
                    die('ERROR CODE: PEA974AFE4614');  
               }
            }
            else{
                if (!mysqli_select_db($this->connection, $this->database)) {
                    $error = 'Could not select database. mysqli_error: ' . mysqli_error($this->connection) . ' mysqli_connect_errno: ' . mysqli_connect_errno();
                    die('Error Code: PEA974AFE4614'); 
                }
            }
        }
        
        public function GetConnection() {
           return $this->connection;
        }

        public function Close() {
            if($this->php_version == 5){
                mysql_close($this->connection);
            }
            else{
                mysqli_close($this->connection);
            }
        }

        public function SetLogger($logger){
            $this->logger = $logger;
            return $this;
        }

        //Only for PHP7
        public function StartTransaction(){
            if($this->php_version == 7){
                $this->use_transaction = true;
                mysqli_autocommit($this->connection,FALSE);
            }
            return $this;
        }

        //Only for PHP7
        public function StopTransaction(){
            if($this->php_version == 7){
                $this->use_transaction = false;
                mysqli_autocommit($this->connection,TRUE);
            }
            
            return $this;
        }

        public function Commit(){
            if($this->php_version == 7){
                mysqli_commit($this->connection,TRUE);
                $this->use_transaction = false;
            }
           
            return $this;
        }

        

        //==========INSERT starts==========

        //Returns a query_result object.
        public function Insert($array_of_key_value_pair = null){
            $this->query_type = "INSERT";
            if($array_of_key_value_pair != null){
                if(is_array($array_of_key_value_pair)){
                    $this->data_array = $array_of_key_value_pair;  
                    
                }
            }
           // $this->_prepare_insert_param($params);
            return $this;
        }

        
        public function Into($table_name){
            $this->table_name = $table_name;  
            return $this; 
        }

        private function _real_escape_string($value){
            switch($this->php_version){
                case 5:
                    $value = "'" . mysql_real_escape_string($value, $this->connection) . "'"; 
                    break;
                case 7:
                    $value = "'" . mysqli_real_escape_string($this->connection, $value) . "'"; 
                    break;
            }

            return $value;
        }
     
        private function _array_escape_string($array){
            $escaped = array_map(function($val) {
                $val = trim($val);
                if($val == NULL ||  strtoupper($val) == "NULL"){
                    return "NULL";
                }
                else{
                    return $this->_real_escape_string($val); 
                 
                }
             }, $array);
            return $escaped;
        }
    
        //$values must be within a double quote i.e. values("'sindhu',101")
        public function Values($values) {
            if(is_array($values)){
                $arr = $values;
            }
            else{
                $arr = explode(",", $values);  
            }

            $escaped = $this->_array_escape_string($arr);

            if(empty($this->values)){
                $this->values = implode (", ", $escaped);   // Join array elements with a string
            }
            else{
                $this->values .= ", " .  explode (", ", $escaped);  //$values;  
            }
            return $this;
        }
       
        public function KeyValueArray($array_of_key_value_pair) {
            $this->data_array = $array_of_key_value_pair;  
            return $this;
        }
        public function ReturnkAutoId(){
            $this->return_last_inserted_auto_id = true;  
            return $this; 
        }
        
        //==========INSERT ends============
        
       
        //========== COUNT starts ==========

        
        //SELECT count(name) as `count` FROM `test` group by name having name='BOND'
        //SELECT count(name) as `count` FROM `test` where name='BOND'

        //Returns a single row with a single column if found.
        //Otherwise, returns zero/false.
        public function Count($column_name = "") {
            $this->query_type = "COUNT";
            if(!empty($column_name)){
                $this->CountAs($column_name, "COUNT");
            }
            return $this;
        }

        //Returns a single row with a single column if found.
        //Otherwise, returns zero/false.
        public function CountAs($column_name, $alias_name, $table_name_or_table_alias = null) {
            $this->query_type = "COUNT";
            $temp = "COUNT(`$column_name`) AS `$alias_name`";  

            if(empty($this->mysql_function)){
                $this->mysql_function =  $temp;
            }
           else{
                $this->mysql_function .= ", $temp";  
           }
           echo $this->mysql_function;
            return $this;
        }

       

        //groupby() is already defined in common functions section.
        
        //from() is already defined in common functions section.

        //where() is already defined in common functions section.

        //having() is already defined in common functions section.
        //========== COUNT ends ==========
        

        //Select starts ----------------------------
        //Initiate a SELECT query.
        public function Select($column_names = null) {
            $this->query_type = "SELECT";
            if(isset($column_names)){
                $this->_prepapre_column_names($column_names, null);
            }
            return $this;
        }

        //Initiate a SELECT query.
        public function SelectSingle($column_names = null) {
            $this->query_type = "SELECT";
            $this->projection_name = "single";
            if(isset($column_names)){
                $this->_prepapre_column_names($column_names, null);
            }
            return $this;
        }

        //Initiate a SELECT query.
        public function SelectMany($column_names = null) {
            $this->query_type = "SELECT";
            $this->projection_name = "many";
            if(isset($column_names)){
                $this->_prepapre_column_names($column_names, null);
            }
            return $this;
        }

        public function Column($column_name, $table_name_or_table_alias = null) {
            //$column_name = "`$column_name`";
            switch($this->query_type){
                case "SELECT":
                    $this->_prepapre_column_names($column_name, $table_name_or_table_alias);
                    break;
                case "COUNT":
                    $this->CountAs($column_name, "COUNT");
                    break;
                default:
                break;
            }
           
            return $this;
        }

        public function ColumnAs($column_name, $alias_name, $table_name_or_table_alias = null) {
            //$column_name = "`$column_name`";
            if(isset($table_name_or_table_alias)){
                $table_name = "`$table_name_or_table_alias`.";
            }
            else{
                $table_name = "";
            }
            switch($this->query_type){
                case "SELECT":
                    $temp = "$table_name`$column_name` AS `$alias_name`";
                    if(empty($this->column_names)){
                        $this->column_names = $temp;
                    }
                    else{
                        $this->column_names .=", $temp";
                    }
                    break;
                case "COUNT":
                    $this->CountAs($column_name, $alias_name, $table_name_or_table_alias = null);
                    break;
                default:
                break;
            }
           
            return $this;
        }

         //$column_names can be in string or array
         public function Columns($column_names, $table_name_or_table_alias = null) {
            $this->_prepapre_column_names($column_names, $table_name_or_table_alias);
            return $this;
        }

        //Initiates a Select Distinct Query
        
        public function Distinct() {
            $this->distinct = "DISTINCT";
            return $this;
        }
       
        private function _prepapre_column_names($column_names, $table_name = null){
            $temp_string = "";
            if(isset($table_name)){
                $table_name = "`$table_name`.";
            }
            else{
                $table_name = "";
            }

            if(!isset($column_names) || empty($column_names) || $column_names == "*"){
                $temp_string = "$table_name" . "*";
            }
            else{
                if(is_array($column_names)){
                    $arr = array_map(function($val) {
                        $val = trim($val);
                        $escaped = " $table_name`$val`"; //add back-quote before & after of each column.
                        return $escaped; 
                     }, $column_names);
    
                     $temp_string = implode (", ", $arr);   // Join array elements with a string        
                }
                else{
                    $arr_temp = explode(",", $column_names);
                    
                    $arr = array_map(function($val) {
                        $val = trim($val);

                        $escaped = " $table_name`$val`"; //add back-quote before & after of each column.
                        return $escaped; 
                     }, $arr_temp);
    
                     $temp_string = implode (", ", $arr);   // Join array elements with a string   
                }
            }
            

            if(empty($this->column_names)){
                $this->column_names = $temp_string; 
            }
           else{
                $this->column_names .= ", $temp_string"; 
           }
          
        }
       

        //from() is already defined in common functions section.

        
        //There is exactly 1 result, an exception is thrown if no result is returned or more than one result. 
        //Returns a single record if found.
        //Returns false if no record found.
        public function Single(){
            $this->projection_name = "single";
            return $this;
        }

        //Only used with select().
        //Returns query_result object.
        //if records not found, query_result->rows is an empty array and can be treat as a boolean(false) value.
        public function Many(){
            $this->projection_name = "many";
            return $this;
        }

        //Only used with select()->many();
        public function Skip($skip_quantity){
            $this->skip_quantity = $skip_quantity;
            return $this;
        }

        //Only used with select()->many();
        public function Take($take_quantity){
            $this->take_quantity = $take_quantity;
            return $this;
        }

        private function _orderby($table_name, $column_name, $asc_or_desc){
            $temp = "";
            if(isset($table_name) && !empty($table_name)){
                $temp = "`$table_name`.`$column_name`";
            }
            else{
                $temp = "`$column_name`";
            }
            if(empty($this->order_by)){
                $this->order_by = "$temp $asc_or_desc";
            }
            else{
                $this->order_by .= ", $temp $asc_or_desc";
            }
            // return $this;
        }

        //Order By ASC
        public function OrderBy($column_name, $table_or_alias_name = null){

            $this->_orderby($table_or_alias_name, $column_name, "ASC");
            return $this;
        }

        public function ThenBy($column_name, $table_or_alias_name = null){
            $this->_orderby($table_or_alias_name, $column_name, "ASC");
            return $this;
        }

        //Only used with select -> many
        //Order By ASC
        public function AscendingBy($column_name, $table_or_alias_name = null){
            $this->_orderby($table_or_alias_name, $column_name, "ASC");
            return $this;
        }

        //Only used with select -> many
        //Order By DESC
        public function OrderByDesc($column_name, $table_or_alias_name = null){
            $this->_orderby($table_or_alias_name,  $column_name, "DESC");
            return $this;
        }

        //used with select -> many
        //Order By DESC
        public function ThenByDesc($column_name, $table_or_alias_name = null){
            $this->_orderby($table_or_alias_name, $column_name, "DESC");
            return $this;
        }

        public function DescendingBy($column_name,  $table_or_alias_name = null){
            $this->_orderby($table_or_alias_name, $column_name, "DESC");
            return $this;
        }

        //No need where() function here. It is already defined in common functions section
        //-------------------Select ends
        
        
        
        //==========UPDATE starts ==========
        //update() initiates an update query.
        ///$table_name can be empty. Table can be set in table() or into() function.
        public function Update($table_name = ""){
            $this->query_type = "UPDATE";
            $this->table_name = "`$table_name`";  
            return $this;
        }
      
        //Used with update() function.
        //Optional. Table name can be set here, if $table_name parameter in update() is empty.
        public function table($table_name){
            $this->table_name = "`$table_name`";  
            return $this;
        }

        //Used with update() function.
        //set() is equivalent to SET clause in SQL statement.
        //$param can be array or string.
        public function Set($param){
            if(is_array($param)){
                $array = $param;
            }
            else{
                parse_str($param, $array); //
            }

            foreach($array as $column=>$value) {
                $value = trim($value);
                if($value == NULL ||  strtoupper($value) == "NULL"){
                    $value = "NULL";
                }
                else{
                    switch($this->php_version){
                        case 5:
                            $value = "'" . mysql_real_escape_string($value, $this->connection) . "'"; 
                            break;
                        case 7:
                            $value = "'" . mysqli_real_escape_string($this->connection, $value) . "'"; 
                            break;
                    }
                }

                if(empty($this->set)){
                    $this->set = "$column=$value";
                }
                else{
                    $this->set .= ", " . "$column=$value";
                }
            }

            return $this;
        }
        //========== UPDATE ends ==========


        public function Delete(){
            $this->query_type = "DELETE";
            return $this;
        }

        private function _affected_rows(){
            if($this->php_version == 5){
                return mysql_affected_rows($this->connection);
            }
            else{
                return mysqli_affected_rows($this->connection);
            }
        }

        private function _insert_id(){
            if($this->php_version == 5){
                return mysql_insert_id();
             }
             else{
                return mysqli_insert_id($this->connection); 
             }
        }

        private function _free_result($mysql_query){
            if($this->php_version == 5){
                mysql_free_result($mysql_query);
            }
            else{
                mysqli_free_result($mysql_query);
            }
        }

        private function _fetch_assoc($mysql_query){
            if($this->php_version == 5){
                $row =  mysql_fetch_assoc($mysql_query);
            }
            else{
                $row =  mysqli_fetch_assoc($mysql_query);
            }

            return $row;
        }

        private function _fetch_array($mysql_query){
            if($this->php_version == 5){
                $row =  mysql_fetch_array($mysql_query);
            }
            else{
                $row =  mysqli_fetch_array($mysql_query);
            }

            return $row;
        }

        private function _fetch_row($mysql_query){
            if($this->php_version == 5){
                $row =  mysql_fetch_row($mysql_query);
            }
            else{
                $row =  mysqli_fetch_row($mysql_query);
            }

            return $row;
        }

        private function _fetch_field($mysql_query){
            if($this->php_version == 5){
                $row =  mysql_fetch_field($mysql_query);
            }
            else{
                $row =  mysqli_fetch_field($mysql_query);
            }

            return $row;
        }

        //must call this method at the end of the statement.
        public function Execute(){
            switch ($this->query_type){
                case "SELECT":
                    $sql = $this->sql;
                    if(empty($sql)){
                        $sql = 'SELECT '. $this->distinct . ' ' . $this->column_names .' FROM ' . $this->table_name;
                        
                        if(!empty($this->join_clause)){
                            $sql .= " " . $this->join_clause;
                        }

                        if(!empty($this->where)){
                            $sql .= " WHERE " . $this->where;
                        }

                        if(!empty($this->order_by)){
                            $sql .= ' ORDER BY '. $this->order_by;
                        }
                    }

                    // $this->logger->create_log($sql);
                   

                    switch ($this->projection_name){
                        case "single":
                            $sql .= ' LIMIT 1';
                            // $this->logger->create_log($sql);
                            echo $sql;
                            $mysql_query =  $this->_mysql_query($sql);
                           
                            switch ($this->fetch_type){
                                case "fetch_assoc":
                                    $row =  $this->_fetch_assoc($mysql_query);
                                    break;
                                case "fetch_array":
                                    $row =  $this->_fetch_array($mysql_query);
                                    break;
                                case "fetch_row":
                                    $row =  $this->_fetch_row($mysql_query);
                                    break;
                                case "fetch_field":
                                    $row =  $this->_fetch_field($mysql_query);
                                    break;
                            }

                            $this->_reset_private_variables();
                            return $row;
                            break;

                        case "many":
                            //LIMIT 10 OFFSET 10
                            if($this->take_quantity > 0){
                                $sql .= " LIMIT " . $this->take_quantity;
                            }

                            if($this->skip_quantity>0){
                                $sql .= " OFFSET " . $this->skip_quantity;
                            }

                            echo $sql;

                            $mysql_query =  $this->_mysql_query($sql);
                            $result = new query_result();
                            $result->success = true; 
                            
                            $quantity = 0; //mysql_num_rows($mysql_query);
                            $rows = array();
                            switch ($this->fetch_type){
                                case "fetch_assoc":
                                    while ($row = $this->_fetch_assoc($mysql_query)) {
                                        $rows[] = $row;
                                        $quantity++;
                                    }
                                    break;
                                case "fetch_array":
                                    while ($row = $this->_fetch_array($mysql_query)) {
                                        $rows[] = $row;
                                        $quantity++;
                                    }
                                    break;
                                case "fetch_row":
                                    while ($row = $this->_fetch_row($mysql_query)) {
                                        $rows[] = $row;
                                        $quantity++;
                                    }
                                    break;
                                case "fetch_field":
                                    while ($row = $this->_fetch_field($mysql_query)) {
                                        $rows[] = $row;
                                        $quantity++;
                                    }
                                    break;
                            }

                            if($quantity>0){
                               $this->_free_result($mysql_query);
                            }
                           
                            $result->count = $quantity;
                            $result->rows = $rows;
                            $this->_reset_private_variables();
                            return $result;
                        break;

                        default:
                        break;
                    }
                break;

                case "INSERT":
                    $sql = "";
                    if(!empty($this->sql)){
                        $sql = $this->sql;
                    }
                    else{
                        if(!empty($this->data_array)){
                            $key = array_keys($this->data_array);
                            $val = array_values($this->data_array);
                            $val = $this->_array_escape_string($val);
                            $sql = "INSERT INTO `$this->table_name` (" . implode(', ', $key ) . ") VALUES('" . implode("', '", $val) . "')";
                        }
                        else{
                            $sql = "INSERT INTO `$this->table_name` (" . $this->column_names . ") VALUES(" . $this->values . ")";
                        }
                    }

                    echo $sql; 
                    $this->_mysql_query($sql);
                    $result = new query_result();
                    $result->success = true;
                   
                    $result->affected_rows = $this->_affected_rows();

                    if($this->return_last_inserted_auto_id){
                        $result->auto_id = $this->_insert_id();
                    }

                    //reset the variables
                    $this->_reset_private_variables();

                    return $result;
                break;

                case "UPDATE":
                    $sql = "";
                    if(!empty($this->sql)){
                        $sql = $this->sql;
                    }
                    else{
                        $sql = "UPDATE " . $this->table_name . " SET " . $this->set;
                        // if(is_array($this->set)){
                        //     // loop and build the column /
                        //     $sets = array();
                        //     foreach($this->set as $column => $value)
                        //     {
                        //         $sets[] = "`".$column."` = '".$value."'";
                        //     }
        
                        //     $sql .= implode(', ', $sets);
                        // }
                        // else{
                        //     $sql .= $this->set;
                        // }
                    }

                    if(!empty($this->where)){
                        // $sql .= $this->_check_where_keyword($this->where);
                        $sql .= " WHERE " . $this->where;

                    }

                   // $this->logger->create_log($sql);

                    echo $sql;

                    $this->_mysql_query($sql);
                    $result = new query_result();
                    $result->success = true;
                    $result->affected_rows = $this->_affected_rows();
                    
                    //reset the variables
                    $this->_reset_private_variables();
                    return $result;
                break;
                
                case "DELETE":
                    $sql = "";
                    if(!empty($this->sql)){
                        $sql = $this->sql;
                    }
                    else{
                        $sql = "DELETE FROM " . $this->table_name;
                    }

                    if(!empty($this->where)){
                        $sql .= " WHERE " . $this->where; //$this->_check_where_keyword($this->where);
                    }

                    $this->_mysql_query($sql);
                    $result = new query_result();
                    $result->success = true;
                    $result->affected_rows = $this->_affected_rows();

                    //reset the variables
                    $this->_reset_private_variables();
                    return $result;
                break;

                case "COUNT":
                case "MAX":
                case "MIN":
                    $aggregate_type = $this->query_type;
                    $sql = "SELECT ".  $aggregate_type ."(" . $this->column_name .") as `".  $aggregate_type ."` FROM " . $this->table_name;

                    if(!empty($this->where)){
                        $sql .= " WHERE " . $this->where;
                    }

                    if(!empty($this->group_by)){
                        $sql .= " GROUP BY " . $this->group_by;
                    }

                    if(!empty($this->having)){
                        $sql .= " HAVING " . $this->having;
                    }

                    if($this->projection_name == "single"){
                        $sql .= ' LIMIT 1';
                    }
                   

                    //$this->logger->create_log($sql);
                    echo $sql;

                    $mysql_query =  $this->_mysql_query($sql);

                    switch ($this->fetch_type){
                        case "fetch_assoc":
                            $row =  $this->_fetch_assoc($mysql_query);
                            break;
                        case "fetch_array":
                            $row =  $this->_fetch_array($mysql_query);
                            break;
                        case "fetch_row":
                            $row =  $this->_fetch_row($mysql_query);
                            break;
                        case "fetch_field":
                            $row =  $this->_fetch_field($mysql_query);
                            break;
                    }
                   
                    $this->_reset_private_variables();
                    return $row;
                   
                break;

                default:
                break;
            }
        }


        //======== common functions starts =================
        public function From($table_name){
            $table_name = trim($table_name); 
            $this->table_name = "`$table_name`";
            return $this;
        }

        public function FromTable($table_name){
            $table_name = trim($table_name); 
            $this->table_name = "`$table_name`";
            return $this;
        }
        
        public function FromTableAs($table_name, $alias_name){
            $table_name = trim($table_name); 
            $alias_name = trim($alias_name);
            $this->table_name = "`$table_name` `$alias_name`";
            return $this;
        }

        // Deleting first array item
        // $removed = array_shift($hobbies);

        private $join_clause = "";
        public function InnerJoin($table_name){
            if(empty($this->join_clause)){
                $this->join_clause = " INNER JOIN `$table_name`";  
            }
            else{
                $this->join_clause .= " INNER JOIN `$table_name`";  
            }
            
            return $this;
        }

        public function InnerJoinAs($table_name, $alias_name){
            if(empty($this->join_clause)){
                $this->join_clause = " INNER JOIN `$table_name` AS `$alias_name`";  
            }
            else{
                $this->join_clause .= " INNER JOIN `$table_name` AS `$alias_name`";  
            }
            
            return $this;
        }

        public function On($left_table_name, $left_column_name, $right_table_name, $right_column_name){
            $this->join_clause .= " ON `$left_table_name`.`$left_column_name` = `$right_table_name`.`$right_column_name`";  
            return $this;
        }

        //Where clause starts----------------
        public function Where($column_name, $table_or_alias_name=null){
            $this->last_call_where_or_having = "where";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->where)){
                $this->where = "$table_name`$column_name`";
            }
            else{
                $this->where .= " AND $table_name`$column_name`";
            }
            return $this;
        }

        public function AndWhere($column_name, $table_or_alias_name=null){
            $this->last_call_where_or_having = "where";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->where)){
                $this->where = "$table_name`$column_name`";
            }
            else{
                $this->where .= " AND $table_name`$column_name`";
            }
            return $this;
        }

        public function OrWhere($column_name, $table_or_alias_name=null){
            $this->last_call_where_or_having = "where";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->where)){
                $this->where = "$table_name`$column_name`";
            }
            else{
                $this->where .= " OR $table_name`$column_name`";
            }
            return $this;
        }
        //Where clause ends----------------

        //Operator starts -----------------
        public function EqualTo($value){
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->where .= "=$value";
            }
            else{
                $this->having .= "=$value";
            }
            return $this;
        }

        public function GreaterThan($value){
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->where .= ">$value";
            }
            else{
                $this->having .= ">$value";
            }
            return $this;
        }

        public function GreaterThanOrEqualTo($value){
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->where .= ">=$value";
            }
            else{
                $this->having .= ">=$value";
            }
            return $this;
        }

        public function LessThan($value){
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->where .= "<$value";
            }
            else{
                $this->having .= "<$value";
            }
            return $this;
        }

        public function LessThanOrEqualTo($value){
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->where .= "<=$value";
            }
            else{
                $this->having .= "<=$value";
            }
            return $this;
        }

        public function Between($starting_value, $ending_value){
            $value_one = $this->_real_escape_string($starting_value);
            $value_two = $this->_real_escape_string($ending_value);

            if($this->last_call_where_or_having == "where"){
                $this->where .= " BETWEEN $value_one AND $value_two";
            }
            else{
                $this->having .= " BETWEEN $value_one AND $value_two";
            }
            return $this;
        }
        //Operator ends -----------------

        public function Having($column_name, $table_or_alias_name=null){
            $this->last_call_where_or_having = "having";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->having)){
                $this->having = "$table_name`$column_name`";
            }
            else{
                $this->having .= " AND $table_name`$column_name`";
            }
            return $this;
        }

        public function AndHaving($column_name, $table_or_alias_name=null){
            $this->last_call_where_or_having = "having";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->having)){
                $this->having = "$table_name`$column_name`";
            }
            else{
                $this->having .= " AND $table_name`$column_name`";
            }
            return $this;
        }

        public function OrHaving($column_name, $table_or_alias_name=null){
            $this->last_call_where_or_having = "having";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->having)){
                $this->having = "$table_name`$column_name`";
            }
            else{
                $this->having .= " OR $table_name`$column_name`";
            }
            return $this;
        }
       
        public function GroupBy($column_name, $table_or_alias_name=null) {
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->group_by)){
                $this->group_by = "$table_name`$column_name`";
            }
            else{
                $this->group_by .= ", $table_name`$column_name`";
            }

            return $this;
        }

        //sql() is used to set raw SQL statment.
        //$sql_statement can be any kind of valid MySQL complaint SQL statement, 
        //and can be used with select, insert, update & delete.
        public function SQL($sql_statement){
            $this->sql = $sql_statement;
            return $this;
        }

        //======== common functions ends =================
    } //<--class

?>