<?php
  
    //============================
    //Version Beta
    //Last modified 21/07/2021
    //This is only for php7
    //============================

class ZeroException extends Exception
{
}

class ZeroSQL{

    #region constructor and destructor
        public function __construct() {
            $get_arguments       = func_get_args();
            $number_of_arguments = func_num_args();

            if($number_of_arguments == 1){
                $this->logger = $get_arguments[0];
            }
        }

        public function __destruct(){
            $mysqli = $this->connection;
        
            if ($mysqli instanceof MySQLi) {
                if ($thread_id = $mysqli->thread_id) $mysqli->kill($thread_id); 
                $mysqli->close();
                echo "<br>closed";
            }
            $this->connection = null; 

            
        }
    #endregion

    #region Database Connection
    private $server = "";
    public function server($database_server) {
        $this->server = $database_server;
        return $this;
    }

    private $user = "";
    public function user($user_name) {
        $this->user = $user_name;
        return $this;
    }

    private $password = "";
    public function password($password) {
        $this->password = $password;
        return $this;
    }

    private $database = "";
    public function database($database) {
        $this->database = $database;
        return $this;
    }

    
    private $connection;
    public function connect() {
        $this->_debugBacktrace();
        $this->connection = new mysqli($this->server, $this->user, $this->password, $this->database);
        // Check connection
        if ($this->connection->connect_error) {
            // die('ERROR CODE: ARPOASRUWWER412547');
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    public function close() {
        $mysqli = $this->connection;       
        if ($mysqli instanceof MySQLi) {
            echo "im ahere";
          if ($thread_id = $mysqli->thread_id) $mysqli->kill($thread_id); 
          $mysqli->close();
        }
        $this->connection = null; 
    }

    //Returns the active database connection.
    public function getConnection() {
       return $this->connection;
    }

    public function setConnection($connection) {
         $this->connection = $connection;
    }
    

    #endregion

    #region ZeroObject
        public function new($name){
            // $this->_debugBacktrace();

            $meta = new stdClass();
            $meta->type = $name;

            $objStdClass = new stdClass();
            $objStdClass->__meta = $meta;
            return $objStdClass;
        }
    #endregion

    #region raw sql query
  

    public function getSQL(){
        return $this->internalSql;
    }

    private $queryType = "";
    private $tableName = "";
    #endregion

    #region transaction
    private $useTransaction = false;
    public function startTransaction(){
        $this->_debugBacktrace();
        mysqli_autocommit($this->connection,FALSE);
        $this->useTransaction = true;
        return $this;
    }

    public function stopTransaction(){
        $this->_debugBacktrace();
        mysqli_autocommit($this->connection,TRUE);
        $this->useTransaction = false;
        return $this;
    }

    public function commit(){
        $this->_debugBacktrace();
        mysqli_commit($this->connection,TRUE);
        //now stop transation.
        mysqli_autocommit($this->connection,TRUE);
        $this->useTransaction = false;
        
        return $this;
    }

    public function rollBack(){
        $this->_debugBacktrace();
        if($this->useTransaction){
            mysqli_rollback($this->connection);
        }
        
        return $this;
    }

    #endregion 


    #region SELECT
     
        public function selectSql($sql){
            $this->hasRawSql = true;
            $this->sql = trim($sql);
            return $this;
        }
        /**
         * 
         * Prepare a select statement
         *
         * @param string $statement Any valid SELECT clause or a complete SQL statement. 'SELECT' keyword is optional. Can have DISTINCT, COUNT/SUM/, JOIN, FROM, WHERE/HAVING, GROUP BY, ORDER BY AND OTHER CLAUSES
         * 
         * ->select("id, name") 
         * 
         * OR ->select("id, count(distict name) as qty from tableA inner join tableB On ...") 
         * 
         * @return this
         */
        public function select(string $statement){
            $this->queryType = "select";

            //Dont use "*" as default columns. 
            //Using "*" here will create problem 
            //in distinct/count/sum/.
            $this->_debugBacktrace();

            $statement = trim($statement);

            if(empty($statement))
                throw new ZeroException("Invalid statement in select() method");

            if(strtoupper(substr($statement,0,6))=="SELECT") $this->sql = $statement;
            else  $this->sql = "SELECT " . $statement;
           
            return $this;
        }

        /**
         * distinct()
         * Sets column name(s) to a SELECT DISTINCT query. No need to add "SELECT DISTINCT" keyword.
         *
         * @param string $columnNames
         * $columnNames = "id, name"
         * 
         * OR,
         * 
         * $columnNames = "id, fullName as name"
         * 
         * @return this
         */
        public function distinct($columnNames = "") {
            $this->_debugBacktrace();
            if(empty($this->sql)){
                $this->sql ="SELECT DISTINCT $columnNames";
            }
            else{
                $this->sql .=" DISTINCT $columnNames";
            }
           
            $this->queryType = "select";
            return $this;
        }
                      
        #region Aggregate
            /**
             * count()
             * 
             * Usage: ->count() or $db->select()->count();
             * 
             * If multiple count() used, the parameter must be as format- count("column as anyName")
             * 
             * @param string $columnName
             * 
             * Example:
             * 
             * $db->select("*")->count("hi")->
             * $db->count("count(hi) AS count")->
             * $db->count("select count(distinct hi) as quantity from table")->
             * @return this
             */
            public function count(string $columnName = "") {
                $this->_debugBacktrace();
                $this->queryType = "select";
                $columnName = trim($columnName);
                if(empty($columnName)) $columnName = "*";

                $as = "count";
                if (stripos($columnName, " as ") !== false) {
                    //Dont use explode(). It is case-sensitive.
                    //Use preg_split() and pass the flag i for case-insensitivity:
                    $arr = preg_split("/ as /i", $columnName); 
                    $columnName = trim($arr[0]);
                    $as = $arr[1];
                }

                $select = "";
                if(empty($this->sql)){
                     //if the $columnName does not contain 'SELECT', add it.
                    if(strtoupper(substr($columnName,0,6)) !== "SELECT") $select = "SELECT";
                }
                else{
                    $select = $this->sql . ",";
                }

                //check whether 'count' keyword exists
                if (stripos($columnName, "count(") !== false) {
                    $this->sql = "$select $columnName AS $as";
                }
                else{
                    $this->sql ="$select COUNT($columnName) AS $as";
                }
            
                return $this;
            }
            
            /**
             * sum()
             * 
             * Usage: ->sum() or $db->select()->sum();
             * 
             * If multiple sum() used, the parameter must be as format- sum("column as anyName")
             * 
             * @param string $columnName
             * 
             * Example:
             * 
             * $db->select("a.*")->sum("hi")->
             * $db->sum("sum(hi) AS sum")->
             * $db->sum("select sum(distinct hi) as total from table")->
             * @return this
             */
            public function sum($columnName) {
                $this->_debugBacktrace();
                $this->queryType = "select";
                $columnName = trim($columnName);
                if(empty($columnName)) throw new ZeroException("Column name required in sum() method.");

                $as = "sum";
                if (stripos($columnName, " as ") !== false) {
                    //Dont use explode(). It is case-sensitive.
                    //Use preg_split() and pass the flag i for case-insensitivity:
                    $arr = preg_split("/ as /i", $columnName); 
                    $columnName = trim($arr[0]);
                    $as = $arr[1];
                }

                $select = "";
                if(empty($this->sql)){
                    if(strtoupper(substr($columnName,0,6)) !== "SELECT"){
                        //if the $columnName does not contain 'SELECT', add it.
                        $select = "SELECT";
                    }
                }
                else{
                    $select = $this->sql . ",";
                }

                //check whether 'sum' keyword exists
                if (stripos($columnName, "sum(") !== false) {
                    $this->sql = "$select $columnName AS $as";
                }
                else{
                    $this->sql ="$select SUM($columnName) AS $as";
                }
            
                return $this;
            }
                                        
            /**
             * min()
             * 
             * Usage: ->min() or $db->select()->min();
             * 
             * If multiple min() used, the parameter must be as format- min("column as anyName")
             * 
             * @param string $columnName
             * 
             * Example:
             * 
             * $db->select("a.*")->min("hi")->
             * $db->min("min(hi) AS min")->
             * $db->min("select min(distinct hi) as highest from table")->
             * @return this
             */
            public function min($columnName) {
                $this->_debugBacktrace();
                $this->queryType = "select";
                $columnName = trim($columnName);
                if(empty($columnName)) throw new ZeroException("Column name required in min() method.");

                $as = "min";
                if (stripos($columnName, " as ") !== false) {
                    //Dont use explode(). It is case-sensitive.
                    //Use preg_split() and pass the flag i for case-insensitivity:
                    $arr = preg_split("/ as /i", $columnName); 
                    $columnName = trim($arr[0]);
                    $as = $arr[1];
                }

                $select = "";
                if(empty($this->sql)){
                    if(strtoupper(substr($columnName,0,6)) !== "SELECT"){
                        //if the $columnName does not contain 'SELECT', add it.
                        $select = "SELECT";
                    }
                }
                else{
                    $select = $this->sql . ",";
                }

                //check whether 'min' keyword exists
                if (stripos($columnName, "min(") !== false) {
                    $this->sql = "$select $columnName AS $as";
                }
                else{
                    $this->sql ="$select min($columnName) AS $as";
                }
            
                return $this;
            }
                        
            /**
             * max()
             * 
             * Usage: ->max() or $db->select()->max();
             * 
             * If multiple max() used, the parameter must be as format- max("column as anyName")
             * 
             * @param string $columnName
             * 
             * Example:
             * 
             * $db->select("a.*")->max("hi")->
             * $db->max("max(hi) AS max")->
             * $db->max("select MAX(distinct hi) as highest from table")->
             * @return this
             */
            public function max($columnName) {
                $this->_debugBacktrace();
                $this->queryType = "select";
                $columnName = trim($columnName);
                if(empty($columnName)) throw new ZeroException("Column name required in max() method.");

                $as = "max";
                if (stripos($columnName, " as ") !== false) {
                    //Dont use explode(). It is case-sensitive.
                    //Use preg_split() and pass the flag i for case-insensitivity:
                    $arr = preg_split("/ as /i", $columnName); 
                    $columnName = trim($arr[0]);
                    $as = $arr[1];
                }

                $select = "";
                if(empty($this->sql)){
                    if(strtoupper(substr($columnName,0,6)) !== "SELECT"){
                        //if the $columnName does not contain 'SELECT', add it.
                        $select = "SELECT";
                    }
                }
                else{
                    $select = $this->sql . ",";
                }

                //check whether 'MAX' keyword exists
                if (stripos($columnName, "max(") !== false) {
                    $this->sql = "$select $columnName AS $as";
                }
                else{
                    $this->sql ="$select MAX($columnName) AS $as";
                }
            
                return $this;
            }

            public function from($tableName){
                $this->_debugBacktrace();
                $this->sql .=" FROM `" . $tableName . "`";
                $this->tableName = $tableName;
                return $this;
            }

        #endregion Aggregate

        #region JOIN
        private $joinClause = "";

        //$table = "table" or "table a"
        public function innerJoin($table){
            $this->_debugBacktrace();
            if(empty($this->joinClause)){
                $this->joinClause = " INNER JOIN `$table`";  
            }
            else{
                $this->joinClause .= " INNER JOIN `$table`";  
            }
            
            return $this;
        }

        //$left = "colA.leftTable"
        //$right = "colB.rightTable"
        public function on($left, $right){
            $this->_debugBacktrace();
            $this->joinClause .= " ON $left = $right";  
            return $this;
        }
        #endregion JOIN
        
        #region ORDER BY
        private $orderByClause = "";
        private function _orderby($column, $direction){
            $this->_debugBacktrace();
           
            if(empty($this->orderByClause)){
                $this->orderByClause = "$column $direction";
            }
            else{
                $this->orderByClause .= ", $column $direction";
            }
        }

        //$column = "columnName" or "table.columnName"
        public function orderBy($column){
            $this->_debugBacktrace();
            $this->_orderby($column, "ASC");
            return $this;
        }

        public function orderByDesc($column){
            $this->_debugBacktrace();
            $this->_orderby($column, "DESC");
            return $this;
        }

        public function ascBy($column){
            $this->_debugBacktrace();
            $this->_orderby($column, "ASC");
            return $this;
        }

        public function descBy($column){
            $this->_debugBacktrace();
            $this->_orderby($column, "DESC");
            return $this;
        }

        public function ascendingBy($column){
            $this->_debugBacktrace();
            $this->_orderby($column, "ASC");
            return $this;
        }

        public function descendingBy($column){
            $this->_debugBacktrace();
            $this->_orderby($column, "DESC");
            return $this;
        }
        //order by ends
        #endregion

        #region Group By
        //can be used repeatedly.
        private $groupByClause = "";
        public function groupBy($column_name, $table_or_alias_name=null) {
            $this->_debugBacktrace();
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->groupByClause)){
                $this->groupByClause = "$table_name`$column_name`";
            }
            else{
                $this->groupByClause .= ", $table_name`$column_name`";
            }

            return $this;
        }
        //end of Group By
        #endregion

        #region find/toList/single/first 

            /**
             * find()
             * 
             * Find a record from database by primary key.
             * It must be the last call of the query. 
             * select() must be used in first call.
             * 
             * @param mixed $id
             * 
             * @return object stdClass
            */
            public function find($id){
                $this->_debugBacktrace();

                $tableName = $this->tableName;
                $this->tableName = "";

                $primaryKeyColumn =  $this->_findPrimaryKeyColumnName($tableName);
                $id = $this->_real_escape_string($id);
                $this->internalSql = $this->sql. " WHERE $primaryKeyColumn = $id";

                $this->sql = "";

                $result = $this->_query($this->internalSql);

                $matchQuantity = $result->num_rows;

                if($matchQuantity <> 1){
                    throw new ZeroException("No data found.");
                }

                $record = $this->_prepareSingleRecord($result);

                $meta = new stdClass();
                $meta->type = $tableName;
                $meta->primaryKey = $primaryKeyColumn;
                $record->__meta = $meta;
                return $record;
            }

            public function toList(){
                if($this->hasRawSql){
                    $this->hasRawSql = false;
                }
               
                $tableName = $this->tableName;
                $this->tableName = "";
               
                $this->internalSql = $this->_addOtherClauses($this->sql);
                $this->sql = "";
                $result = $this->_query($this->internalSql);
                
                $quantity = 0;
                $rows = []; //array();
                switch ($this->fetchType){
                    case "object":
                        while ($row = $result->fetch_object()) {
                            if(isset($tableName) && !empty($tableName)){
                                $meta = new stdClass();
                                $meta->type = $tableName;
                                $row->__meta = $meta;
                            }
                            $rows[] = $row;
                            $quantity++;
                        }
                        break;
                    
                    case "assoc": //fetch_assoc is the fastest method.   
                        while ($row = $result->fetch_assoc()) {
                            $rows[] = $row;
                            $quantity++;
                        }
                        break;
                    case "array": //fetch_array is the second fastest method.
                        while ($row = $result->fetch_array()) {
                            $rows[] = $row;
                            $quantity++;
                        }
                        break;
                    case "row":
                        while ($row = $result->fetch_row()) {
                            $rows[] = $row;
                            $quantity++;
                        }
                        break;
                    case "field":
                        while ($row = $result->fetch_field()) {
                            $rows[] = $row;
                            $quantity++;
                        }
                        break;
                }

                if($quantity>0){
                    $result->free_result();
                }

                return $rows;
            }

            /**
             * first() 
             * 
             * When you expect one or more items to be returned by a query but you only want to access the first item in your code (ordering could be important in the query here). This will throw an exception if the query does not return at least one item.
            */
            public function first(){
                return $this->_first(false);
            }
        
            public function firstOrNull(){
                return $this->_first(true);
            }

            private function _first(bool $allowNull){
                if($this->hasRawSql) $this->hasRawSql = false;
               
                $this->tableName = "";
               
                $this->takeQuantity = 1;
                $this->internalSql = $this->_addOtherClauses($this->sql);
                $this->sql = "";
                $result = $this->_query($this->internalSql);
              
                $numRows =  $result->num_rows;
                if($numRows == 0){
                    if($allowNull) return NULL;
                    throw new ZeroException("The resultset must have at least 1 record. SQL - '$this->internalSql'");
                }
               
                return $this->_prepareSingleRecord($result);
            }

            public function single(){
                return $this->_single(false);
            }

            public function singleOrNull(){
                return $this->_single(true);
            }

            private function _single(bool $allowNull){
                $sql = $this->sql;
                $this->sql = "";
                $this->takeQuantity = 1;
                $sql = $this->_addOtherClauses($sql);
               

                $result = $this->_query($sql);
                $numRows =  $result->num_rows;

                if($numRows == 0){
                    if($allowNull) return NULL;
                    throw new ZeroException("The resultset must have exactly 1 record. But found none. SQL- '$sql'");
                }
               
                if($numRows > 1) throw new ZeroException("The resultset must have exactly 1 record. But multiple records found. SQL- '$sql'");
                return $this->_prepareSingleRecord($result);
            }
           

            //Used in find(), toList(), first(), firstOrNull(), single(), singleOrNull()
            private function _addOtherClauses($sql){
                $this->_debugBacktrace();
                
                if(!empty($this->joinClause)){
                    $sql .= " " . $this->joinClause;
                    $this->joinClause = "";
                }

                if(!empty($this->whereClause)){
                    $sql .= " WHERE " . $this->whereClause;
                    $this->whereClause ="";
                }
                
                //Group by must be before than Having
                if(!empty($this->groupByClause)){
                    $sql .= " GROUP BY " . $this->groupByClause;
                    $this->groupByClause ="";
                }
                
                //"HAVING" must followed by "GROUP BY"
                if(!empty($this->havingClause)){
                    $sql .= " HAVING " . $this->havingClause;
                    $this->havingClause = "";
                }

                if(!empty($this->orderByClause)){
                    $sql .= ' ORDER BY '. $this->orderByClause;
                    $this->orderByClause = "";
                }
                //LIMIT 10 OFFSET 10
                if($this->takeQuantity > 0){
                    $sql .= " LIMIT " . $this->takeQuantity;
                    $this->takeQuantity = 0; //Reset takeQuantity
                }
                
                if($this->skipQuantity>0){
                    $sql .= " OFFSET " . $this->skipQuantity;
                    $this->skipQuantity = 0; //Reset skipQuantity
                }

                return $sql;
            }

            //used in find(), first(), firstOrNull(), single(), singleOrNull()
            private function _prepareSingleRecord($queryObject){

                $this->_debugBacktrace();

                $fetchType = $this->fetchType;
                $this->fetchType = "object"; //reset fetchType

                switch ($fetchType){
                    case "object":
                        $record = mysqli_fetch_object($queryObject);
                        break;
                    case "assoc":
                        $record = mysqli_fetch_assoc($queryObject);
                        break;
                    case "array":
                        $record =  mysqli_fetch_array($queryObject);
                        break;
                    case "row":
                        $record =  mysqli_fetch_row($queryObject);
                        break;
                    case "field":
                        $record =  mysqli_fetch_field($queryObject);
                        break;
                }

                return $record;
            }
        #endregion
   
        private $skipQuantity= 0;
        public function skip($quantity){
            $this->_debugBacktrace();
            $this->skipQuantity = $quantity;
            return $this;
        }

        private $takeQuantity= 0;
        public function take($quantity){
            $this->_debugBacktrace();
            $this->takeQuantity = $quantity;
            return $this;
        }

    #endregion SELECT

    #region INSERT


        private $insertParam;
        /**
         * insert()
         * 
         * Insert new data into table.
         * 
         * @param mixed $param stdObject/array/string
         * 
         * //Insert from stdObject-
         * $id = $db->insert($object)->execute(); //table name not required.
         * 
         * //Insert from array-
         * $array = array("name"=>"hi", "number"=>2);
         * $array["title"]= "teacher";
         * $db->insert($array)->into("test")->execute();
         * 
         * //Insert from a raw insert statement
         * $db->insert("insert into test(name, title, number) values('hi', 'hello',1)")->execute();
         * 
         * //Insert from a comma-separated column=value string
         * $db->insert("name='sk', title='nr', number=2")->into("test")->execute();
         * 
         * @return this and then last auto increment id when executed.
         */
        public function insert(mixed $param){
            $this->_debugBacktrace();
            $this->queryType= "insert";
            $this->insertParam = $param;
            return $this;
        }

        //comes from execute()
        private function _insert(){
            $this->_debugBacktrace();
            $parameter = $this->insertParam;
            unset($this->insertParam);

            $tableName = $this->tableName;
            $this->tableName = "";
           
            //First preferable parameter is stdClass
            if($parameter instanceof stdClass){
                if(isset($parameter->__meta->type) && !empty($parameter->__meta->type)) $tableName = $parameter->__meta->type;
                $PropertyValueArray = $this->_createPropertyValueArrayFromStdClass($parameter);
                $this->internalSql = $this->_prepareInsertSQL($tableName, $PropertyValueArray);
            }
            else{
                //Second preferable parameter is array
                //Must ends with '->into("tableName")->execute();'
                if(is_array($parameter)){
                    $keyValueArray = $parameter ;
                    $PropertyValueArray = $this->_createPropertyValueArrayFromKeyValuePair($keyValueArray);
                    $this->internalSql = $this->_prepareInsertSQL($tableName, $PropertyValueArray);
                }
                else{
                    //Third preferable parameter is a complete & valid insert sql statement
                    $parameter = trim($parameter);
                    //check for 'insert', 'into' & 'values' keyword. Not case sensitive.
                    //Must ends with '->execute();'

                    if (stripos($parameter,"insert") !== false && stripos($parameter,"into") !== false && stripos($parameter,"values") !== false) {
                        $this->internalSql = $parameter;
                    }
                    else{
                        //Comma separated string - ->insert("name='abc', age=23")->into("tableName")->execute();
                        $commaSeparatedString = $parameter ;
                        $PropertyValueArray = $this->_createPropertyValueArrayFromCommaSeparatedString($commaSeparatedString);
                        $this->internalSql = $this->_prepareInsertSQL($tableName, $PropertyValueArray);
                    }
                }
            }
           

            $this->_query($this->internalSql);
            return $this->connection->insert_id;
            
        }
    #endregion INSERT

    #region UPDATE

        /**
         * update()
         * 
         * @param mixed $param stdObject/array/string
         * 
         * //Update from stdObject-
         * $id = $db->update($object)->execute(); //table name optional.
         * 
         * //Update from array-
         * $array = array("name"=>"hi", "number"=>2);
         * $array["title"]= "teacher";
         * $db->update($array)->into("test")->execute();
         * 
         * //Update from a raw update SQL statement
         * $db->insert("update test set name='abc', age=23")->execute();
         * 
         * //Insert from a comma-separated column=value string
         * $db->update("name='sk', title='nr', number=2")->into("test")->execute();
         * 
         * @return this and then number of affected rows when executed.
         */
        public function update(mixed $param){
            $this->_debugBacktrace();
            $this->queryType= "update";
            $this->updateParam = $param;
            
            return $this;
        }

        //comes from execute()
        private function _update(){
            $this->_debugBacktrace();
            $parameter = $this->updateParam; //transfer to local variable.
            unset($this->updateParam); //reset updateParam.
            
            $tableName = $this->tableName; $this->tableName = "";
        
             //First preferable parameter is stdClass
            if($parameter instanceof stdClass ){
                $stdClass = $parameter ;
                if(isset($stdClass->__meta->type) && !empty($stdClass->__meta->type)) $tableName = $stdClass->__meta->type;
                $PropertyValueArray = $this->_createPropertyValueArrayFromStdClass($stdClass);
                $this->internalSql = $this->_prepareUpdateSQL($tableName, $PropertyValueArray);
            }
            else{
                //Second preferable parameter is array
                //Must ends with '->into("tableName")->execute();'
                if(is_array($parameter)){
                    $PropertyValueArray = $this->_createPropertyValueArrayFromKeyValuePair($parameter);
                    $this->internalSql = $this->_prepareUpdateSQL($tableName, $PropertyValueArray);
                }
                else{
                    $parameter = trim($parameter);

                    //Third preferable parameter is a complete & valid UPDATE sql statement
                    //Must have 'UPDATE' and 'SET' keyword. Not case sensitive.
                    if (stripos($parameter,"update") !== false && stripos($parameter," set ") !== false) {
                        $this->internalSql = $parameter;
                    }
                    else{
                        //Update from a comma-separated column=value string
                        //Example $db->update("name='abc', age=23")->into("tableName")->execute();
                        $PropertyValueArray = $this->_createPropertyValueArrayFromCommaSeparatedString($parameter);
                        $this->internalSql = $this->_prepareUpdateSQL($tableName, $PropertyValueArray);
                    }
                }
            }
           

            $this->_query($this->internalSql);
            return $this->connection->affected_rows;
        }
    #endregion UPDATE

    #region DELETE
        private $deleteParam = "";
        /**
         * Starts a delete operation.
         *
         * Another line of desription.
         *
         * @param string $table table name
         *
         * @return this
         */
        public function delete(string $param){
            $this->_debugBacktrace();
            $this->queryType= "delete";
            $this->deleteParam = $param;
            return $this;
        }

        //comes from execute()
        private function _delete(){
            $this->_debugBacktrace();

            $parameter = $this->deleteParam;
            $this->deleteParam = "";
           
            if($this->hasRawSql){
                $this->hasRawSql = false;
                $this->internalSql = $this->sql;
            }
            else{
                if(empty($this->whereClause))
                throw new ZeroException("Delete query must have a where clause. SQL- $this->internalSql");
                $this->internalSql = "DELETE FROM $tableName WHERE " . $this->whereClause;
            }

            $this->whereClause = "";
            $this->sql = "";

            $this->_query($this->internalSql);
            
            return $this->connection->affected_rows;
        }
    #endregion DELETE
    
    //Must call for insert(), update() and delete()
    public function execute(){
        $this->_debugBacktrace();
        switch ($this->queryType){
            case "insert":
                return $this->_insert();
            break;
            case "update":
                return $this->_update();
            break;
            case "delete":
                return $this->_delete();
            break;
        }
    }

    #region Common function 
        
        //Used in insert and update
        public function into($tableName){
            $this->_debugBacktrace();
            $this->tableName ="`" . $tableName . "`";
            return $this;
        }

        protected $hasRawSql = false;
        protected function _hasRawSql($bool){
            $this->_debugBacktrace();
            $this->hasRawSql = $bool;
            return $this;
        }
        public function withSQL(){
            $this->_debugBacktrace();
            return $this->_hasRawSql(true);
        }
        public function fromSQL(){
            $this->_debugBacktrace();
            return $this->_hasRawSql(true);
        }
        public function useSQL(){
            $this->_debugBacktrace();
            return $this->_hasRawSql(true);
        }
    #endregion

    #region MySQL functions
        protected $logSQL = false; //to view sql on a later time for troubleshooting purpose.
        private function _query($sql){
            $this->_debugBacktrace();
            
            if($this->isEnabledSqlLogging){
                
                $currentdatetime = new DateTime("now", new DateTimeZone('Asia/Dhaka'));
                $FormattedDateTime = $currentdatetime->format('d-m-Y h:i:s A');  //date('Y-m-d H:i:s');
                $logContent = "\n\n";
                $logContent .= "-------------------" . $FormattedDateTime . "----------------------------";
                $logContent .= "\n";
                $logContent .= $sql;

                file_put_contents("ZeroSQL_LOG.txt", $logContent, FILE_APPEND | LOCK_EX );
            }

            if($this->isEnabledSqlPrinting){
                echo "<br>" . $sql . "<br>";
            }

            $result = $this->connection->query($sql);

            /*
            For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset, mysql_query() returns a resource on success, or FALSE on error.
            For other type of SQL statements, INSERT, UPDATE, DELETE, DROP, etc, mysql_query() returns TRUE on success or FALSE on error.
            */
            if ($result === false) {
                //$error_description = "An error has occured while working with database.";
            
                $error = $this->connection->error; //mysqli_error($this->connection);
                $sqlState = $this->connection->sqlstate;
                $error_description = "Failed to execute the following SQL statement: $sql. MySQL Error: $error. SqlState: $sqlState";
                throw new ZeroException($error_description);
            }

            return $result;
        }

        public function escapeString($value){
            return $this->_real_escape_string($value);
        }

        private function _real_escape_string($value){
            $this->_debugBacktrace();
            //also valid - $this->connection->real_escape_string($value)
            $value = "'" . $this->connection->escape_string($value) . "'"; 
            return $value;
        }
     
        private function _array_escape_string($array){
            $this->_debugBacktrace();
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
    #endregion

    #region fetch type

        private $fetchType = "object";
        //Default fetch type
        //returns instance of stdClass.
        public function fetchObject(){
            $this->_debugBacktrace();
            $this->fetchType = "object";
            return $this;
        }

        //Returns an associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
        public function fetchAssoc(){
            $this->_debugBacktrace();
            $this->fetchType = "assoc";
            return $this;
        }

        //Fetch a result row as an associative array, a numeric array, or both
        public function fetchArray(){
            $this->_debugBacktrace();
            $this->fetchType = "array";
            return $this;
        }

        //Get a result row as an enumerated array
        public function fetchRow(){
            $this->_debugBacktrace();
            $this->fetchType = "row";
            return $this;
        }

        //Get column information from a result and return as an object
        public function fetchField(){
            $this->_debugBacktrace();
            $this->fetchType = "field";
            return $this;
        }
    #endregion fetch type

    #region utility/helper functions for select() method

    //used in find() and _updateSql()
    private function _findPrimaryKeyColumnName($tableName){
        $this->_debugBacktrace();
        $tableName= str_replace("`","",$tableName);
        // $sql = "SELECT COLUMN_NAME 
        //         FROM information_schema.KEY_COLUMN_USAGE 
        //         WHERE TABLE_NAME = '". $tableName ."' 
        //         AND CONSTRAINT_NAME = 'PRIMARY'";

        $sql = "SHOW KEYS FROM ". $tableName ." WHERE Key_name = 'PRIMARY'";

        $result = $this->_query($sql);
        $primaryKeyColumn = mysqli_fetch_object($result);
        // return  $primaryKeyColumn->COLUMN_NAME;
        return  $primaryKeyColumn->Column_name;
    }


    //PropertyValueArray
    private function _createPropertyValueArrayFromBean($bean){
        $this->_debugBacktrace();
        list( $properties, $table ) = $bean->getPropertiesAndType();
        $PropertyValueArray = array();
        $k1 = 'property';
        $k2 = 'value';

        foreach( $properties as $key => $value ) {
            $PropertyValueArray[] = array( $k1 => $key, $k2 => $value );
        }
        
        return $PropertyValueArray;
    }

    private function _createPropertyValueArrayFromStdClass($stdClass){
        $this->_debugBacktrace();
        $properties = (array) $stdClass;
        $PropertyValueArray = array();
        $k1 = 'property';
        $k2 = 'value';

        foreach( $properties as $key => $value ) {
            if($key == "__meta") continue;
            $PropertyValueArray[] = array( $k1 => $key, $k2 => $value );
        }
        
        return $PropertyValueArray;
    }

    private function _createPropertyValueArrayFromKeyValuePair($arrayOfKkeyValuePair) {
        $this->_debugBacktrace();
        /*
            $keyvalues = array();
            $keyvalues['foo'] = "bar";
            $keyvalues['pyramid'] = "power";
        */

        $properties = $arrayOfKkeyValuePair;  

        $PropertyValueArray = array();
        $k1 = 'property';
        $k2 = 'value';

       
        foreach( $properties as $key => $value ) {

            $key = trim($key);
            // if ( !ctype_lower( $key ) ) {
            //     $key = $this->beau( $key );
            // } 

            $value = trim($value);
            if ( $value === FALSE ) {
                $value = '0';
            } elseif ( $value === TRUE ) {
                $value = '1';
                /* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
            } elseif ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            } elseif($value === NULL ||  strtoupper($value) === "NULL"){
                $value = 'NULL';
            }

            $PropertyValueArray[] = array( $k1 => $key, $k2 => $value );
        }
        
       
        return $PropertyValueArray;
    }

    //("name=saumitra, father=fathers name")
    public function _createPropertyValueArrayFromCommaSeparatedString( $string )  {
        $this->_debugBacktrace();

        $properties = array();
    
        $arr = explode(",", $string);
        
        foreach( $arr as $item) {
            $keyValue = explode("=",$item);
            $key = $keyValue[0];
            $key = trim($key);
        
            $exists = isset( $properties[$key] );

            if($exists) continue;

            $value = $keyValue[1];
            $properties[$key] = $value;
        }
        
        $PropertyValueArray = array();
        $k1 = 'property';
        $k2 = 'value';

        
        foreach( $properties as $key => $value ) {
            $PropertyValueArray[] = array( $k1 => $key, $k2 => $value );
        }
        
        return $PropertyValueArray;
        
    }

    private function _prepareInsertSQL( $table, $PropertyValueArray ) {
        $this->_debugBacktrace();
		$columnsArray = $valuesArray = array();

        foreach ( $PropertyValueArray as $pair ) {
            $column = $pair['property'];
            $columnsArray[] = $column; //$pair['property'];

            $value = $pair['value'];
            if ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            }
            $value = trim($value);
            if ( $value === FALSE ) {
                $value = '0';
            } elseif ( $value === TRUE ) {
                $value = '1';
                /* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
            } elseif ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            } elseif($value === NULL ||  strtoupper($value) === "NULL"){
                $value = 'NULL';
            }

            $valuesArray[]  = $value; //$pair['value'];
        }
       
        $column_names ="`" . implode('`,`', $columnsArray) . "`";
        $val = $this->_array_escape_string($valuesArray);
        $values = implode(", ", $val);

        return "INSERT INTO $table (" . $column_names . ") VALUES(" . $values . ")";
    }
    
    private function _prepareUpdateSQL( $table, $PropertyValueArray ) {
        $this->_debugBacktrace();
		$set= "";
        $whereClause="";
        //If where clause is empty, then updateParam might be an stdClass 
        //with Primary Key column as a property. Lets find the primary key column
        //from table.
        $pk="";
        if(empty($this->whereClause)){
            $pk = $this->_findPrimaryKeyColumnName($table);
        }
        else{
            $whereClause  = $this->whereClause;
            $this->whereClause = "";
        }
        foreach ( $PropertyValueArray as $pair ) {
            $column = $pair['property'];
            $value = $pair['value'];
           
            
            //$value = trim($value);
            if ( $value === FALSE ) {
                $value = '0';
            } elseif ( $value === TRUE ) {
                $value = '1';
                /* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
            } elseif ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            } elseif($value === NULL ||  strtoupper($value) === "NULL"){
                $value = 'NULL';
            }
            
            $value = "'" . mysqli_real_escape_string($this->connection, $value) . "'"; 

            if($column == $pk){
                $whereClause = " $pk=$value"; continue;
            }

            if(empty($set)){
                $set = "$column=$value";
            }
            else{
                $set .= ", " . "$column=$value";
            }
        }
      
       

        return "UPDATE " . $table . " SET " . $set . " WHERE " . $whereClause;
    }

    #endregion

    #region Conditional (Where and Having)
        
        #region Where clause

        private $whereClause = "";
        private $conditionalClauseName = "";

        //Option to provide directly raw where clause sql statement without 'where' keyword.
        public function whereSQL($whereSqlStatement){
            $this->_debugBacktrace();
            $this->whereClause = $whereSqlStatement;
            return $this;
        }

        //->where("column")->equalTo(1)
        //OR
        //->where("column = 1")
        public function where($columnName){
            $this->_debugBacktrace();
            $this->_where("AND", $columnName);
            return $this;
        }

        //->where("column")->equalTo(1)
        //OR
        //->where("column = 1")
        public function andWhere($columnName){
            $this->_debugBacktrace();
            $this->_where("AND", $columnName);

            return $this;
        }

        //->where("column")->equalTo(1)
        //OR
        //->where("column = 1")
        public function orWhere($columnName){
            $this->_debugBacktrace();
            $this->_where("OR", $columnName);

            return $this;
        }
        
          //comes from where(), andWhere(), orWhere()
        //return void
        private function _where($conjunction, $columnName){
            $this->_debugBacktrace();
            $operators = array(' = ', '<>' ,'<=', '>=', ' < ', ' > ', ' is ', ' like ');

            $this->conditionalClauseName = "where";
            
            $isInline = false;
            foreach($operators as $operator) {
                if (stripos($columnName,$operator) !== false) {
                    //Dont use explode(). It is case-sensitive.
                    //Use preg_split() and pass the flag i for case-insensitivity:
                    
                    $arr = preg_split("/$operator/i", $columnName); 
                    // $arr = explode($operator, $columnName);
                    $x = $arr[0];
                    $value = trim($arr[1]);
                    if($value == NULL || strtolower($value) == 'null'){
                        $value = $value;
                    }
                    else{
                        $value = $this->_real_escape_string($value);
                    }
                   
                    $isInline = true;
                    $columnName = "$x $operator $value";
                }
            }
            if(empty($this->whereClause)){
                if($isInline) $this->whereClause = "$columnName";
                else $this->whereClause = "`$columnName`";
            }
            else{
                if($isInline) $this->whereClause .= "  $conjunction $columnName";
                else $this->whereClause .= " $conjunction `$columnName`";
            }
        }

        #endregion

        #region Having clause

        private $havingClause = "";

        //Option to provide raw sql statement with having clause.
        public function havingSQL($havingSqlStatement){
            $this->_debugBacktrace();
            $this->havingClause = $havingSqlStatement;
        }

        public function having($columnName){
            $this->_debugBacktrace();
            $this->_having("AND", $columnName);
            return $this;
        }

        public function andHaving($columnName){
            $this->_debugBacktrace();
            $this->_having("AND", $columnName);
            return $this;
        }

        public function orHaving($columnName){
            $this->_debugBacktrace();
            $this->_having("OR", $columnName);
            return $this;
        }

        private function _having($conjunction, $columnName){
            $this->_debugBacktrace();
            $operators = array(' = ', '<>' ,'<=', '>=', ' < ', ' > ', ' is ', ' like ');

            $this->conditionalClauseName = "having";
            
            $isInline = false;
            foreach($operators as $operator) {
                if (stripos($columnName,$operator) !== false) {
                    //Dont use explode(). It is case-sensitive.
                    //Use preg_split() and pass the flag i for case-insensitivity:
                    
                    $arr = preg_split("/$operator/i", $columnName); 
                    // $arr = explode($operator, $columnName);
                    $x = $arr[0];
                    $value = trim($arr[1]);
                    if($value == NULL || strtolower($value) == 'null'){
                        $value = $value;
                    }
                    else{
                        $value = $this->_real_escape_string($value);
                    }
                   
                    $isInline = true;
                    $columnName = "$x $operator $value";
                }
            }
            if(empty($this->havingClause)){
                if($isInline) $this->havingClause = "$columnName";
                else $this->havingClause = "`$columnName`";
            }
            else{
                if($isInline) $this->havingClause .= "  $conjunction $columnName";
                else $this->havingClause .= " $conjunction `$columnName`";
            }
        }
        #endregion 

        #region Operators for Where and Having clause (= < > etc)
        public function equalTo($value){
            $this->_debugBacktrace();
            /*
                Equals is generally used unless using a verb "is" and the phrase "equal to". 
                While reading 3 ft = 1 yd you would say "three feet equals a yard," or "three feet is equal to a yard". 
                Equals is used as a verb. 
                To use equal in mathematics (generally an adjective) you need an accompanying verb.
            */
            $value = trim($value);
            if($value == NULL || strtolower($value) == 'null'){
                $value = $value;
            }
            else{
                $value = $this->_real_escape_string($value);
            }

            $this->_setCondition($value, "=");

            return $this;
        }

        public function notEqualTo($value){
            $this->_debugBacktrace();
            /*
                Equals is generally used unless using a verb "is" and the phrase "equal to". 
                While reading 3 ft = 1 yd you would say "three feet equals a yard," or "three feet is equal to a yard". 
                Equals is used as a verb. 
                To use equal in mathematics (generally an adjective) you need an accompanying verb.
            */
            $value = trim($value);
            if($value == NULL || strtolower($value) == 'null'){
                $value = $value;
            }
            else{
                $value = $this->_real_escape_string($value);
            }

            $this->_setCondition($value, "<>");

            return $this;
        }

        public function greaterThan($value){
            $this->_debugBacktrace();
            $value = trim($value);
            if($value == NULL || strtolower($value) == 'null'){
                $value = $value;
            }
            else{
                $value = $this->_real_escape_string($value);
            }

            $this->_setCondition($value, ">");
            
            return $this;
        }

        public function greaterThanOrEqualTo($value){
            $this->_debugBacktrace();
            $value = trim($value);
            if($value == NULL || strtolower($value) == 'null'){
                $value = $value;
            }
            else{
                $value = $this->_real_escape_string($value);
            }
            
            $this->_setCondition($value, ">=");

            return $this;
        }

        public function lessThan($value){
            $this->_debugBacktrace();
            $value = trim($value);
            if($value == NULL || strtolower($value) == 'null'){
                $value = $value;
            }
            else{
                $value = $this->_real_escape_string($value);
            }

            $this->_setCondition($value, "<");

            return $this;
        }

        public function lessThanOrEqualTo($value){
            $this->_debugBacktrace();
            $value = trim($value);
            if($value == NULL || strtolower($value) == 'null'){
                $value = $value;
            }
            else{
                $value = $this->_real_escape_string($value);
            }

            $this->_setCondition($value, "<=");

            return $this;
        }

        public function between($first, $last){
            $this->_debugBacktrace();
            $value_one = $this->_real_escape_string(trim($first));
            $value_two = $this->_real_escape_string(trim($last));

            if($this->conditionalClauseName == "where"){
                $this->whereClause .= " BETWEEN $value_one AND $value_two";
            }
            else{
                $this->havingClause .= " BETWEEN $value_one AND $value_two";
            }
            return $this;
        }

        public function notBetween($first, $last){
            $this->_debugBacktrace();
            $value_one = $this->_real_escape_string(trim($first));
            $value_two = $this->_real_escape_string(trim($last));

            if($this->conditionalClauseName == "where"){
                $this->whereClause .= " NOT BETWEEN $value_one AND $value_two";
            }
            else{
                $this->havingClause .= " NOT BETWEEN $value_one AND $value_two";
            }
            return $this;
        }

        public function startWith($value){
            $this->_debugBacktrace();
            //NEVER use trim() here.
            $value = $this->_real_escape_string($value . "%");

            $this->_setCondition($value, "LIKE");

            return $this;
        }

        public function notStartWith($value){
            $this->_debugBacktrace();
            $value = $this->_real_escape_string($value. "%");

            $this->_setCondition($value, "NOT LIKE");

            return $this;
        }

        public function endWith($string){
            $this->_debugBacktrace();
            $value = $this->_real_escape_string("%" . $string);

            $this->_setCondition($value, "LIKE");

            return $this;
        }

        public function notEndWith($string){
            $this->_debugBacktrace();
            $value = $this->_real_escape_string("%" . $string);

            $this->_setCondition($value, "NOT LIKE");

            return $this;
        }

        public function contain($string){
            $this->_debugBacktrace();
            $value = $this->_real_escape_string("%". $string. "%");

            $this->_setCondition($value, "LIKE");
           
            return $this;
        }

        public function notContain($string){
            $this->_debugBacktrace();
            $value = $this->_real_escape_string("%". $string. "%");

            $this->_setCondition($value, "NOT LIKE");

            return $this;
        }

        //Enable user to write raw string with wildcard characters i.e. 'itunes%'
        public function like($stringWithWildCardCharacter){
            $this->_debugBacktrace();

            $value = $this->_real_escape_string($stringWithWildCardCharacter);

            $this->_setCondition($value, "LIKE");

            return $this;
        }

        //Enable user to write raw string with any wildcard characters i.e. 'itunes%'
        public function notLike($stringWithWildCardCharacter){
            $this->_debugBacktrace();
            $value = $this->_real_escape_string($stringWithWildCardCharacter);

            $this->_setCondition($value, "NOT LIKE");

            return $this;
        }

        private function _setCondition($value, $operator){
            $this->_debugBacktrace();

            if($this->conditionalClauseName == "where"){
                $this->whereClause .= " $operator $value";
            }
            else{
                $this->havingClause .= " $operator $value";
            }
        }

        #endregion
    #endregion

    #region Debug and Troubleshoot
    private $isEnabledSqlLogging = false;
    public function logSQL($bool){
        $this->isEnabledSqlLogging = $bool;
        return $this;
    }

    private $isEnabledSqlPrinting = false;
    public function printSql($bool){
        $this->isEnabledSqlPrinting = $bool;
        return $this;
    }

    private $performDebugBacktrace = false;
    public function debugBacktrace($bool){
        $this->performDebugBacktrace = $bool;
        return $this;
    }

    private $callCounter = 1;
    private function _debugBacktrace(){
        if($this->performDebugBacktrace){

            $callers = debug_backtrace();
           
            $count = count($callers);
            $caller = sprintf('%02d', $this->callCounter); 
            if($count > 1){
                for($i=$count-1; $i>0; $i=$i-1){
                    //&#8594;
                    $caller .= " " . html_entity_decode("&#8594;") . " " . $callers[$i]['function'] . "()";
                }
              //  $caller = "<br>$caller";
            }
    
            // echo "<br>$caller";

             $currentdatetime = new DateTime("now", new DateTimeZone('Asia/Dhaka'));
             $FormattedDateTime = $currentdatetime->format('d-m-Y h:i:s A');  //date('Y-m-d H:i:s');
             $logContent = "\n\n";
             $logContent .= $caller . "   " . $FormattedDateTime;
 
             file_put_contents("ZeroSQL_Debug_Backtrace.txt", $logContent, FILE_APPEND | LOCK_EX );

             $this->callCounter++;
        }
    }

    #endregion

    #region Database and Table Schema
    public function truncate($tableName){
        $this->_debugBacktrace();
        $sql = "TRUNCATE TABLE `$tableName`";
        $this->_query($sql);
        return $this;
    }

    //Field, Type, Null, Key, Default, Extra
    public function showColumns($tableName){
        $this->_debugBacktrace();
        $sql = "SHOW COLUMNS FROM `$tableName`";
        $result = $this->_query($sql);

        $fetchType = $this->fetchType;
        $this->fetchType = "object";

        $rows = []; //array();
        switch ($fetchType){
            case "object":
                while ($row = $result->fetch_object()) {
                    $rows[] = $row;
                }
                break;
            case "assoc":
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                break;
            case "array":
                while ($row = $result->fetch_array()) {
                    $rows[] = $row;
                }
                break;
            case "row":
                while ($row = $result->fetch_row()) {
                    $rows[] = $row;
                }
                break;
            case "field":
                while ($row = $result->fetch_field()) {
                    $rows[] = $row;
                }
                break;
        }

        $result->free;
       
        return $rows;
    }

    public function showTables(){
        $this->_debugBacktrace();
        $sql = "SHOW TABLES FROM " . $this->database;
        $result = $this->_query($sql);

        $fetchType = $this->fetchType;
        $this->fetchType = "object";

        $rows = []; //array();
        switch ($fetchType){
            case "object":
                while ($row = $result->fetch_object()) {
                    $rows[] = $row;
                }
                break;
            case "assoc":
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                break;
            case "array":
                while ($row = $result->fetch_array()) {
                    $rows[] = $row;
                }
                break;
            case "row":
                while ($row = $result->fetch_row()) {
                    $rows[] = $row;
                }
                break;
            case "field":
                while ($row = $result->fetch_field()) {
                    $rows[] = $row;
                }
                break;
        }

        $result->free;
        return $rows;
    }
    
    public function findPrimaryKeyColumn($tableName){
        $this->_debugBacktrace();
        return $this->_findPrimaryKeyColumnName($tableName);
    }

    #endregion

    #region CSV
    public function getCSV($sql = ""){
        $query = mysqli_query($this->connection, $sql);
        $export =  $query;
        
        //$fields = mysql_num_fields ( $export );
        $fields = mysqli_num_fields($export);

        $header ='';
        $data = '';
        for ( $i = 0; $i < $fields; $i++ )
        {
            $colObj = mysqli_fetch_field_direct($export,$i);                            
            $col = $colObj->name;

            $header .= $col . "\t";
            while( $row = mysqli_fetch_row( $export ) )
            {
                $line = '';
                foreach( $row as $value )
                {                                            
                    if ( ( !isset( $value ) ) || ( $value == "" ) )
                    {
                        $value = "\t";
                    }
                    else
                    {
                        $value = str_replace( '"' , '""' , $value );
                        $value = '"' . $value . '"' . "\t";
                    }
                    $line .= $value;
                }
                $data .= trim( $line ) . "\n";
            }
        }
       
        $data = str_replace( "\r" , "" , $data );
        
        if ( $data == "" )
        {
            $data = "\n(0) Records Found!\n";                        
        }
        
        return "$header\n$data";
        
        //USAGE--------------------
        // header("Content-type: application/octet-stream");
        // header("Content-Disposition: attachment; filename=your_desired_name.xls");
        // header("Pragma: no-cache");
        // header("Expires: 0");
        // print "$header\n$data";
    }

    public function getCSVNew($sql = ""){
     
        $query = mysqli_query($this->connection, $sql);
        $export =  $query;
       
        
        //$fields = mysql_num_fields ( $export );
        $fields = mysqli_num_fields($export);

        $header ='';
        $data = '';
        for ( $i = 0; $i < $fields; $i++ )
        {
            $colObj = mysqli_fetch_field_direct($export,$i);                            
            $col = $colObj->name;

            $header .= $col . "\t";
            while( $row = mysqli_fetch_row( $export ) )
            {
                $line = '';
                foreach( $row as $value )
                {                                            
                    if ( ( !isset( $value ) ) || ( $value == "" ) )
                    {
                        $value = "\t";
                    }
                    else
                    {
                        $value = str_replace( '"' , '""' , $value );
                        $value = '"' . $value . '"' . "\t";
                    }
                    $line .= $value;
                }
                $data .= trim( $line ) . "\n";
            }
        }
       
        $data = str_replace( "\r" , "" , $data );
        
        if ( $data == "" )
        {
            $data = "\n(0) Records Found!\n";                        
        }
        
        return "$header\n$data";
        
        //USAGE--------------------
        // header("Content-type: application/octet-stream");
        // header("Content-Disposition: attachment; filename=your_desired_name.xls");
        // header("Pragma: no-cache");
        // header("Expires: 0");
        // print "$header\n$data";
    }

    public function query_to_csv($db_conn, $query, $filename, $attachment = false, $headers = true) {
       
        if($attachment) {
            // send response headers to the browser
            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment;filename='.$filename);

            $fp = fopen('php://output', 'w');
        } else {
            $fp = fopen($filename, 'w');
        }
       
        $result = mysqli_query($db_conn, $query) or die( mysqli_error( $db_conn ) );
       
        if($headers) {
            // output header row (if at least one row exists)
            $row = mysqli_fetch_assoc($result);
            if($row) {
                fputcsv($fp, array_keys($row));
                // reset pointer back to beginning
                mysqli_data_seek($result, 0);
            }
        }
       
        while($row = mysqli_fetch_assoc($result)) {
            fputcsv($fp, $row);
        }
       
        fclose($fp);
    }
    #endregion
}

?>