<?php
  
    //============================
    //Version Beta
    //Last modified 19=3/04/2020
    //This is only for php7
    //============================


class ZeroSQL
{

    #region constructor and destructor

    /**
     * Constructor.
     */
    public function __construct() {
        $get_arguments       = func_get_args();
        $number_of_arguments = func_num_args();

        if($number_of_arguments == 1){
            $this->logger = $get_arguments[0];
        }
    }


    public function __destruct(){
        if(is_resource($this->connection) && get_resource_type($this->connection)==='mysql link'){
            mysqli_close($this->connection);
        }
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

    //Connect to the server and select the database.
    public function connect() {
        $this->debugBacktrace();
        $this->connection = mysqli_connect($this->server, $this->user, $this->password, $this->database); 

        if (!$this->connection) {
            $mysqlError = mysqli_error($this->connection);
            die('ERROR CODE: ARPOASRUWWER412547');
        } 
    }
    
    //Close the connection.
    public function close() {
        $this->debugBacktrace();
        if(is_resource($this->connection) && get_resource_type($this->connection)==='mysql link'){
            mysqli_close($this->connection);
        }
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
            $this->debugBacktrace();

            $meta = new stdClass();
            $meta->type = $name;

            $objStdClass = new stdClass();
            $objStdClass->__meta = $meta;
            return $objStdClass;
        }
    #endregion

    private $queryType= "";
    private $tableName = "";

    #region transaction
    private $useTransaction = false;
    public function startTransaction(){
        $this->debugBacktrace();
        mysqli_autocommit($this->connection,FALSE);
        $this->useTransaction = true;
        return $this;
    }

    public function stopTransaction(){
        $this->debugBacktrace();
        mysqli_autocommit($this->connection,TRUE);
        $this->useTransaction = false;
        return $this;
    }

    public function commit(){
        $this->debugBacktrace();
        mysqli_commit($this->connection,TRUE);
        //now stop transation.
        mysqli_autocommit($this->connection,TRUE);
        $this->useTransaction = false;
        
        return $this;
    }

    public function rollBack(){
        $this->debugBacktrace();
        if($this->useTransaction){
            mysqli_rollback($this->connection);
        }
        
        return $this;
    }

    #endregion 

    #region SELECT ... methods
    private $selectParam;
    public function select($selectParam = NULL){
        $this->debugBacktrace();
        $this->queryType = "select";
        $this->selectParam = $selectParam;
        return $this;
    }


        private $selectModifier = "";
        #region Select modifiers (first, firstOrNull, single, singleOrNull)
        public function first(){
            $this->debugBacktrace();
            $this->selectModifier = "first";
            return $this;
        }
    
        public function firstOrNull(){
            $this->debugBacktrace();
            $this->selectModifier = "firstOrNull";
            return $this;
        }
    
        //Single or SingleOrDefault is used, 
        //when you expect only a single row to exist in the table. 
        //If there is more than one result is found, then the system will throw an exception. 
        
        public function single(){
            $this->debugBacktrace();
            $this->selectModifier = "single";
            return $this;
        }
    
        public function singleOrNull(){
            $this->debugBacktrace();
            $this->selectModifier = "single";
            return $this;
        }

        public function isExist(){
            $sql = $this->_prepare_select_sql("single");
            if(isset($this->_mysql_query_object)){
                $mysql_query = $this->_mysql_query_object;
            }
            else{
                $mysql_query =  $this->_perform_mysql_query($sql);
            }
            $quantity = call_user_func("SwiftDB::". $this->mysql_num_rows_function, $mysql_query);

            if($quantity > 0){
                return true;
            }
            else{
                return false;
            }
        }
         
        /**
         * any()
         * 
         * @return row single row from database query.
         * @return false if no record found.
         */
        // public function any(){
        //     $sql = $this->_prepare_select_sql("single");
        //     if(isset($this->_mysql_query_object)){
        //         $mysql_query = $this->_mysql_query_object;
        //     }
        //     else{
        //         $mysql_query =  $this->_perform_mysql_query($sql);
        //     }

        //     switch ($this->fetch_type){
        //         case "fetch_assoc":
        //             $row =  $this->_fetch_assoc($mysql_query);
        //             break;
        //         case "fetch_array":
        //             $row =  $this->_fetch_array($mysql_query);
        //             break;
        //         case "fetch_row":
        //             $row =  $this->_fetch_row($mysql_query);
        //             break;
        //         case "fetch_field":
        //             $row =  $this->_fetch_field($mysql_query);
        //             break;
        //     }

        //    // $this->_reset_private_variables();
        //    return $row;
        // }
        #endregion

        private $orderByClause = "";
        #region Order By
        private function _orderby($table_name, $column_name, $asc_or_desc){
            $this->debugBacktrace();
            $temp = "";
            if(isset($table_name) && !empty($table_name)){
                $temp = "`$table_name`.`$column_name`";
            }
            else{
                $temp = "`$column_name`";
            }
            if(empty($this->orderByClause)){
                $this->orderByClause = "$temp $asc_or_desc";
            }
            else{
                $this->orderByClause .= ", $temp $asc_or_desc";
            }
            // return $this;
        }

        public function orderBy($column_name, $table_or_alias_name = null){
            $this->debugBacktrace();
            $this->_orderby($table_or_alias_name, $column_name, "ASC");
            return $this;
        }

        public function orderByDesc($column_name, $table_or_alias_name = null){
            $this->debugBacktrace();
            $this->_orderby($table_or_alias_name,  $column_name, "DESC");
            return $this;
        }

        public function ascBy($column_name, $table_or_alias_name = null){
            $this->debugBacktrace();
            $this->_orderby($table_or_alias_name, $column_name, "ASC");
            return $this;
        }

        public function descBy($column_name, $table_or_alias_name = null){
            $this->debugBacktrace();
            $this->_orderby($table_or_alias_name,  $column_name, "DESC");
            return $this;
        }

        public function ascendingBy($column_name, $table_or_alias_name = null){
            $this->debugBacktrace();
            $this->_orderby($table_or_alias_name, $column_name, "ASC");
            return $this;
        }


        //Order By DESC
        public function descendingBy($column_name, $table_or_alias_name = null){
            $this->debugBacktrace();
            $this->_orderby($table_or_alias_name,  $column_name, "DESC");
            return $this;
        }



        #endregion

        #region MISC- distinct, skip, take, columnAs
         private $skipQuantity= NULL;
         public function skip($quantity){
            $this->debugBacktrace();
             $this->skipQuantity = $quantity;
             return $this;
         }
 
         private $takeQuantity= NULL;
         public function take($quantity){
            $this->debugBacktrace();
             $this->takeQuantity = $quantity;
             return $this;
         }

        public function columnAs($column_name, $alias_name, $table_name_or_table_alias = null) {
            $this->debugBacktrace();
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
        #endregion

        #region JOIN
        private $joinClause = "";
        public function innerJoin($table){
            $this->debugBacktrace();
            if(empty($this->joinClause)){
                $this->joinClause = " INNER JOIN `$table`";  
            }
            else{
                $this->joinClause .= " INNER JOIN `$table`";  
            }
            
            return $this;
        }

        public function innerJoinAs($table, $alias){
            $this->debugBacktrace();
            if(empty($this->joinClause)){
                $this->joinClause = " INNER JOIN `$table` AS `$alias`";  
            }
            else{
                $this->joinClause .= " INNER JOIN `$table` AS `$alias`";  
            }
            
            return $this;
        }

        public function on($leftTable, $leftColumn, $rightTable, $rightColumn){
            $this->debugBacktrace();
            $this->joinClause .= " ON `$leftTable`.`$leftColumn` = `$rightTable`.`$rightColumn`";  
            return $this;
        }
        #endregion

    private $findParam;
    //returns an instance of stdClass.
    //throws exception if record not found.
    //Find Method
    //Finding the row with the Primary Key is one of the common tasks that is performed on the table.
    public function find($id){
        $this->debugBacktrace();
        $this->queryType = "find";
        $this->findParam = $id;
        return $this;
    }

    private $distinct="";
    public function distinct($columnName = NULL) {
        //parameter is optional in order to use with min, max, count etc aggregate function.
        //Because, in aggregate function, column name is set in that function.
        $this->debugBacktrace();
        if(empty($this->queryType)){
            $this->queryType = "select";
        }
        
        if(empty($this->selectParam)){
            $this->selectParam = $columnName;
        }

       
        $this->distinct = "DISTINCT";
        return $this;
    }
      
    public function count($columnName) {
        $this->debugBacktrace();
        $this->queryType = "count";
        $this->selectParam = "`" . $columnName . "`";
       
        return $this;
    }

    public function min($columnName) {
        $this->debugBacktrace();
        $this->queryType = "min";
        $this->selectParam = "`" . $columnName . "`";
       
        return $this;
    }

    public function max($columnName) {
        $this->debugBacktrace();
        $this->queryType = "max";
        $this->selectParam = "`" . $columnName . "`";
       
        return $this;
    }

    public function sum($columnName) {
        $this->debugBacktrace();
        $this->queryType = "sum";
        $this->selectParam = "`" . $columnName . "`";
       
        return $this;
    }
    #endregion

    #region Common for Select and Delete
    public function from($tableName){
        $this->debugBacktrace();
        $this->tableName ="`" . $tableName . "`";
        return $this;
    }
    #endregion

    #region INSERT
    private $insertParam;
    public function insert($param){
        $this->debugBacktrace();
        $this->queryType= "insert";
        $this->insertParam = $param;
        return $this;
    }
    #endregion

    #region UPDATE
    protected $updateParam;
    public function update($param){
        $this->debugBacktrace();
        $this->queryType= "update";
        $this->updateParam = $param;
        return $this;
    }
    #endregion

    #region Common for insert and update
    
    public function into($tableName){
        $this->debugBacktrace();
        $this->tableName ="`" . $tableName . "`";
        return $this;
    }
    #endregion

    #region DELETE
    private $deletaParam;

    /**
     * Starts a delete operation.
     *
     * Another line of desription.
     *
     * @param mix     $deletaParam       optional. If provided, then it must be a primakry key value or an stdObject.
     *
     * @return this affected rows;
     */
    public function delete($deleteParam = NULL){
        $this->debugBacktrace();
        $this->queryType= "delete";
        $this->deleteParam = $deleteParam;
        return $this;
    }
    #endregion
    
    #region Common function 
    protected $hasRawSql = false;
    protected function _hasRawSql($bool){
        $this->debugBacktrace();
        $this->hasRawSql = $bool;
        return $this;
    }
    public function withSQL(){
        $this->debugBacktrace();
        return $this->_hasRawSql(true);
    }
    public function fromSQL(){
        $this->debugBacktrace();
        return $this->_hasRawSql(true);
    }
    public function useSQL(){
        $this->debugBacktrace();
        return $this->_hasRawSql(true);
    }
    #endregion

    #region MySQL functions
    protected $logSQL = false; //to view sql on a later time for troubleshooting purpose.
    private function _perform_mysql_query($sql){
        $this->debugBacktrace();
        
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

        $query = mysqli_query($this->connection, $sql);
        /*
        For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset, mysql_query() returns a resource on success, or FALSE on error.
        For other type of SQL statements, INSERT, UPDATE, DELETE, DROP, etc, mysql_query() returns TRUE on success or FALSE on error.
        */
        if ($query === false) {
            //$error_description = "An error has occured while working with database.";
           
            $error = "MySQL Error:". mysqli_error($this->connection);
            $error_description = "Failed to execute the following SQL statement: $sql. " . $error;
            throw new Exception($error_description);
        }

        return $query;
    }

    private function _real_escape_string($value){
        $this->debugBacktrace();
        $value = "'" . mysqli_real_escape_string($this->connection, $value) . "'"; 
        return $value;
    }
     
    private function _array_escape_string($array){
        $this->debugBacktrace();
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

    private $fetchType = "fetch_object";
    //Default fetch type
    //returns instance of stdClass.
    public function fetchObject(){
        $this->debugBacktrace();
        $this->fetchType = "fetch_object";
        return $this;
    }

    //Returns an associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
    public function fetchAssoc(){
        $this->debugBacktrace();
        $this->fetchType = "fetch_assoc";
        return $this;
    }

    //Fetch a result row as an associative array, a numeric array, or both
    public function fetchArray(){
        $this->debugBacktrace();
        $this->fetchType = "fetch_array";
        return $this;
    }

    //Get a result row as an enumerated array
    public function fetchRow(){
        $this->debugBacktrace();
        $this->fetchType = "fetch_row";
        return $this;
    }

    //Get column information from a result and return as an object
    public function fetchField(){
        $this->debugBacktrace();
        $this->fetchType = "fetch_field";
        return $this;
    }
    #endregion

    #region utility/helper functions for select() method

    private function __findPrimaryKeyColumnName($tableName){
        $this->debugBacktrace();
        $tableName= str_replace("`","",$tableName);
        $sql = "SELECT COLUMN_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = '". $tableName ."' 
                AND CONSTRAINT_NAME = 'PRIMARY'";

        $result = $this->_perform_mysql_query($sql);
        $primaryKeyColumn = mysqli_fetch_object($result);
        return  $primaryKeyColumn->COLUMN_NAME;
    }



	/**
	 * Converts a camel cased string to a snake cased string.
	 *
	 * @param string $camel camelCased string to converty to snake case
	 *
	 * @return string
	 */
	private function camelsSnake( $camel )	{
        $this->debugBacktrace();
		return strtolower( preg_replace( '/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $camel ) );
	}

    private function camelCaseToUnderScore( $property )	{
        $this->debugBacktrace();
		static $beautifulColumns = array();

		if ( ctype_lower( $property ) ) return $property;
		
		if ( !isset( $beautifulColumns[$property] ) ) {
			$beautifulColumns[$property] = SELF::camelsSnake( $property );
		}
		return $beautifulColumns[$property];
	}
    /**
	 * Turns a camelcase property name into an underscored property name.
	 *
	 * Examples:
	 *
	 * - oneACLRoute -> one_acl_route
	 * - camelCase -> camel_case
	 *
	 * Also caches the result to improve performance.
	 *
	 * @param string $property property to un-beautify
	 *
	 * @return string
	 */
	protected function beau( $property )
	{
        $this->debugBacktrace();
		static $beautifulColumns = array();

		if ( ctype_lower( $property ) ) return $property;
		if (
			( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )
			|| ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) )
			|| ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) )
		) {

			$property = preg_replace( '/List$/', '', $property );
			return $property;
		}
		if ( !isset( $beautifulColumns[$property] ) ) {
			$beautifulColumns[$property] = SELF::camelsSnake( $property );
		}
		return $beautifulColumns[$property];
	}
    
    //PropertyValueArray
    private function _createPropertyValueArrayFromBean($bean){
        $this->debugBacktrace();
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
        $this->debugBacktrace();
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
        $this->debugBacktrace();
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
            if ( !ctype_lower( $key ) ) {
                $key = $this->beau( $key );
            } 

            $value = trim($value);
            if ( $value === FALSE ) {
                $value = '0';
            } elseif ( $value === TRUE ) {
                $value = '1';
                /* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
            } elseif ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            } elseif($value == NULL ||  strtoupper($value) == "NULL"){
                $value = 'NULL';
            }

            $PropertyValueArray[] = array( $k1 => $key, $k2 => $value );
        }
        
       
        return $PropertyValueArray;
    }

    //("name=saumitra, father=fathers name")
    public function _createPropertyValueArrayFromCommaSeparatedString( $string )
    {
        $this->debugBacktrace();

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
        $this->debugBacktrace();
		$columnsArray = $valuesArray = array();

        foreach ( $PropertyValueArray as $pair ) {
            $column = $pair['property'];
            if ( !ctype_lower( $column ) ) {
                $column = $this->camelCaseToUnderScore( $column );
            } 

            $columnsArray[] = $column; //$pair['property'];

            $value = $pair['value'];
            $value = trim($value);
            if ( $value === FALSE ) {
                $value = '0';
            } elseif ( $value === TRUE ) {
                $value = '1';
                /* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
            } elseif ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            } elseif($value == NULL ||  strtoupper($value) == "NULL"){
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
        $this->debugBacktrace();
		$set= "";
        $whereClause="";
        //If where clause is empty, then updateParam might be an stdClass 
        //with Primary Key column as a property. Lets find the primary key column
        //from table.
        $pk="";
        if(empty($this->whereClause)){
            $pk = $this->__findPrimaryKeyColumnName($table);
        }
        else{
            $whereClause  = $this->whereClause;
            $this->whereClause = "";
        }
        foreach ( $PropertyValueArray as $pair ) {
            $column = $pair['property'];
            if ( !ctype_lower( $column ) ) {
                $column = $this->camelCaseToUnderScore( $column );
            } 
          
            $value = $pair['value'];
           
            
            //$value = trim($value);
            if ( $value === FALSE ) {
                $value = '0';
            } elseif ( $value === TRUE ) {
                $value = '1';
                /* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
            } elseif ( $value instanceof \DateTime ) { 
                $value = $value->format( 'Y-m-d H:i:s' ); 
            } elseif($value == NULL ||  strtoupper($value) == "NULL"){
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

        //Option to provide directly raw where clause sql statement without 'where' keyword.
        public function whereSQL($whereSqlStatement){
            $this->debugBacktrace();
            $this->whereClause = $whereSqlStatement;
            return $this;
        }

        //comes from where(), andWhere(), orWhere()
        //return void
        private function _where($conjunction, $column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->last_call_where_or_having = "where";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->whereClause)){
                $this->whereClause = "$table_name`$column_name`";
            }
            else{
                $this->whereClause .= " $conjunction $table_name`$column_name`";
            }
            return "";
        }

        public function where($column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->_where("AND", $column_name, $table_or_alias_name);
            return $this;
        }

        public function andWhere($column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->_where("AND", $column_name, $table_or_alias_name);

            return $this;
        }

        public function orWhere($column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->_where("OR", $column_name, $table_or_alias_name);

            return $this;
        }
        
        #endregion

        #region Having clause

        private $havingClause = "";

        private function _having($column_name, $table_or_alias_name, $andOr){
            $this->debugBacktrace();
            $this->last_call_where_or_having = "having";
            if(isset($table_or_alias_name)){
                $table_name = "`$table_or_alias_name`.";
            }
            else{
                $table_name = "";
            }

            if(empty($this->havingClause)){
                $this->havingClause = "$table_name`$column_name`";
            }
            else{
                $this->havingClause .= " $andOr $table_name`$column_name`";
            }
            return $this;
        }

        public function having($column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->_having($column_name, $table_or_alias_name, "AND");
            return $this;
        }

        public function andHaving($column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->_having($column_name, $table_or_alias_name, "AND");
            return $this;
        }

        public function orHaving($column_name, $table_or_alias_name=null){
            $this->debugBacktrace();
            $this->_having($column_name, $table_or_alias_name, "OR");
            return $this;
        }

        //Option to provide raw sql statement with having clause.
        public function havingSQL($havingSqlStatement){
            $this->debugBacktrace();
            $this->havingClause = $whereSqlStatement;
        }
        #endregion 

        #region Operators for Where and Having clause (= < > etc)
        public function equalTo($value){
            $this->debugBacktrace();
            /*
                Equals is generally used unless using a verb "is" and the phrase "equal to". 
                While reading 3 ft = 1 yd you would say "three feet equals a yard," or "three feet is equal to a yard". 
                Equals is used as a verb. 
                To use equal in mathematics (generally an adjective) you need an accompanying verb.
            */
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= "=$value";
            }
            else{
                $this->havingClause .= "=$value";
            }
            return $this;
        }

        public function greaterThan($value){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= ">$value";
            }
            else{
                $this->havingClause .= ">$value";
            }
            return $this;
        }

        public function greaterThanOrEqualTo($value){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " >= $value";
            }
            else{
                $this->havingClause .= " >= $value";
            }
            return $this;
        }

        public function lessThan($value){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " < $value";
            }
            else{
                $this->havingClause .= " < $value";
            }
            return $this;
        }

        public function lessThanOrEqualTo($value){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($value);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= "<=$value";
            }
            else{
                $this->havingClause .= "<=$value";
            }
            return $this;
        }

        public function between($starting_value, $ending_value){
            $this->debugBacktrace();
            $value_one = $this->_real_escape_string($starting_value);
            $value_two = $this->_real_escape_string($ending_value);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " BETWEEN $value_one AND $value_two";
            }
            else{
                $this->havingClause .= " BETWEEN $value_one AND $value_two";
            }
            return $this;
        }

        public function startWith($string){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($string . "%");

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " LIKE $value";
            }
            else{
                $this->havingClause .= " LIKE $value";
            }
            return $this;
        }

        public function notStartWith($string){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($string. "%");

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " NOT LIKE $value";
            }
            else{
                $this->havingClause .= " NOT LIKE $value";
            }
            return $this;
        }

        public function endWith($string){
            $this->debugBacktrace();
            $value = $this->_real_escape_string("%" . $string);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " LIKE $value";
            }
            else{
                $this->havingClause .= " LIKE $value";
            }
            return $this;
        }

        public function notEndWith($string){
            $this->debugBacktrace();
            $value = $this->_real_escape_string("%" . $string);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " NOT LIKE $value";
            }
            else{
                $this->havingClause .= " NOT LIKE $value";
            }
            return $this;
        }

        public function contain($string){
            $this->debugBacktrace();
            $value = $this->_real_escape_string("%". $string. "%");

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " LIKE $value";
            }
            else{
                $this->havingClause .= " LIKE $value";
            }
            return $this;
        }

        public function notContain($string){
            $this->debugBacktrace();
            $value = $this->_real_escape_string("%". $string. "%");

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " NOT LIKE $value";
            }
            else{
                $this->havingClause .= " NOT LIKE $value";
            }
            return $this;
        }

        //Enable user to write raw string with wildcard characters i.e. 'itunes%'
        public function like($stringWithWildCardCharacter){
            $this->debugBacktrace();

            $value = $this->_real_escape_string($stringWithWildCardCharacter);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " LIKE $value";
            }
            else{
                $this->havingClause .= " LIKE $value";
            }
            return $this;
        }

        //Enable user to write raw string with wildcard characters i.e. 'itunes%'
        public function notLike($stringWithWildCardCharacter){
            $this->debugBacktrace();
            $value = $this->_real_escape_string($stringWithWildCardCharacter);

            if($this->last_call_where_or_having == "where"){
                $this->whereClause .= " NOT LIKE $value";
            }
            else{
                $this->havingClause .= " NOT LIKE $value";
            }
            return $this;
        }
        #endregion
    #endregion

    //can be used repeatedly.
    private $groupByClause = "";
    public function groupBy($column_name, $table_or_alias_name=null) {
        $this->debugBacktrace();
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


    public function execute(){
        $this->debugBacktrace();
        switch ($this->queryType){
            case "insert":
                return $this->_insert();
            break;
            case "update":
                return $this->_update();
            break;
            case "find":
                return $this->_find();
            break;
            case "delete":
                return $this->_delete();
            break;
            case "select":
            case "count":
            case "min":
            case "max":
            case "sum":
                return $this->_select();
            break;
        }
    }

    #region Methods for execute()

    public function _select(){
        $this->debugBacktrace();
        $parameter = $this->selectParam; //transfer to local variable.
        $this->selectParam = ""; //reset updateParam.

        $tableName = $this->tableName;
        $this->tableName = ""; //reset.
       
        $sql = "";
        if($this->hasRawSql){
            $sql = $parameter;
        }
        else{
            $columns = "";
            if(!isset($parameter) || empty($parameter)){
                $columns = "*";
                $sql = $this->_prepare_select_sql($columns,$tableName);
            }
            else{
                //first check whether it has 'select' keyword
                $parameter = trim($parameter);
                $keyWord = substr($parameter,0,6);
                if(strtoupper($keyWord)=="SELECT") {
                    $sql = $parameter;
                }
                else{
                    $columns = $parameter;
                    $sql = $this->_prepare_select_sql($columns,$tableName);
                }
            }
        }

    
        $queryObject = $this->_perform_mysql_query($sql);
        
        if(empty($this->selectModifier)){
            //No select modifier (first, firstOrDefault, single, singleOrDefault) found ---->
            $quantity = 0;
            $rows = array();
            switch ($this->fetchType){
                case "fetch_object":
                    while ($row = mysqli_fetch_object($queryObject)) {
                        $meta = new stdClass();
                        $meta->type = $tableName;
                        $row->__meta = $meta;
                        $rows[] = $row;
                        $quantity++;
                    }
                    break;
                case "fetch_assoc":
                    while ($row = mysqli_fetch_assoc($queryObject)) {
                        $rows[] = $row;
                        $quantity++;
                    }
                    break;
                case "fetch_array":
                    while ($row = mysqli_fetch_array($queryObject)) {
                        $rows[] = $row;
                        $quantity++;
                    }
                    break;
                case "fetch_row":
                    while ($row = mysqli_fetch_row($queryObject)) {
                        $rows[] = $row;
                        $quantity++;
                    }
                    break;
                case "fetch_field":
                    while ($row = mysqli_fetch_field($queryObject)) {
                        $rows[] = $row;
                        $quantity++;
                    }
                    break;
            }

            if($quantity>0){
                mysqli_free_result($queryObject);
            }

            return $rows;
            //<----No select modifier (first, firstOrDefault, single, singleOrDefault) found 
        }
        else{ 
            //select modifier (first, firstOrDefault, single, singleOrDefault) found ---->
            $selectModifier = $this->selectModifier;
            $this->selectModifier = "";
            $row;
            switch($selectModifier){
                case "first":
                    $numRows =  mysqli_num_rows($queryObject);
                    if($numRows == 0){
                        throw new Exception("No data found.");
                    }
                break;
    
                case "firstOrNull":
                    $numRows =  mysqli_num_rows($queryObject);
                    if($numRows == 0){
                        return NULL;
                    }
                break;

                case "single":
                    $numRows =  mysqli_num_rows($queryObject);
                    if($numRows == 0){
                        throw new Exception("No data found.");
                    }
                    if($numRows > 1){
                        throw new Exception("Multiple records found.");
                    }
                break;

                case "singleOrNull":
                    $numRows =  mysqli_num_rows($queryObject);
                    if($numRows == 0){
                        return NULL;
                    }
                    if($numRows > 1){
                        return NULL;
                    }
                break;
            }

            return $this->_prepareSingleRecord($queryObject);
            //<---- select modifier (first, firstOrDefault, single, singleOrDefault) found *212*062#
        }
    }

        #region Helper method for _select() method
        private function _prepare_select_sql($columns,$table){
            $this->debugBacktrace();
            $distinct = $this->distinct; $this->distinct = "";
            $sql = "";
            switch ($this->queryType){
                case "select":
                    $sql = "SELECT ". $distinct . " " . $columns ." FROM " . $table;
                    break;
                case "count":
                case "min":
                case "max":
                case "sum":
                    $aggregateFunctionName = $this->queryType;
                    $sql = "SELECT ".  $aggregateFunctionName ."(". $distinct  . " ". $columns .") as `".  $aggregateFunctionName ."` FROM " . $table;
                    break;
            }
            
            if(!empty($this->joinClause)){
                $sql .= " " . $this->joinClause;
                $this->joinClause = "";
            }
    
            if(!empty($this->whereClause)){
                $sql .= " WHERE " . $this->whereClause;
                $this->whereClause ="";
            }
    
            
            if(!empty($this->groupByClause)){
                $sql .= " GROUP BY " . $this->groupByClause;
                $this->groupByClause ="";
            }
    
            if(!empty($this->havingClause)){
                $sql .= " HAVING " . $this->havingClause;
                $this->havingClause = "";
            }
    
            if(!empty($this->orderByClause)){
                $sql .= ' ORDER BY '. $this->orderByClause;
                $this->orderByClause = "";
            }
            //LIMIT 10 OFFSET 10
            if($this->selectModifier == "first"){
                $sql .= ' LIMIT 1';
            }
            else{
                if($this->takeQuantity > 0){
                    $sql .= " LIMIT " . $this->takeQuantity;
                }
            }
           
            if($this->skipQuantity>0){
                $sql .= " OFFSET " . $this->skipQuantity;
            }
    
            return $sql;
        }

        //used in _select() method.
        //returns single record.
        private function _prepareSingleRecord($queryObject){

            $this->debugBacktrace();

            $fetchType = $this->fetchType;
            
            /*
            NOTE- 
            Don't reset the fetchType. Because default fetch type intentionaly has been set by user.
            So, keep it until ther user changes it. 
            */

            //$this->fetchType = ""; 

            switch ($this->fetchType){
                case "fetch_object":
                    $record = mysqli_fetch_object($queryObject);
                    break;
                case "fetch_assoc":
                    $record = mysqli_fetch_assoc($queryObject);
                    break;
                case "fetch_array":
                    $record =  mysqli_fetch_array($queryObject);
                    break;
                case "fetch_row":
                    $record =  mysqli_fetch_row($queryObject);
                    break;
                case "fetch_field":
                    $record =  mysqli_fetch_field($queryObject);
                    break;
            }

            return $record;
        }
        #endregion

    private function _find(){
        $this->debugBacktrace();

        $tableName = $this->tableName;
        $this->tableName = "";


        $primaryKeyColumn =  $this->__findPrimaryKeyColumnName($tableName);
        $sql = "select * from " . $tableName . " where " . $primaryKeyColumn . " = " . $this->findParam;
        $queryObject = $this->_perform_mysql_query($sql);

        $matchQuantity =  mysqli_num_rows($queryObject);

        if($matchQuantity <> 1){
            throw new Exception("No data found.");
        }

        $record = $this->_prepareSingleRecord($queryObject);

        $meta = new stdClass();
        $meta->type = $tableName;
        $meta->primaryKey = $primaryKeyColumn;
        $record->__meta = $meta;
        return $record;
    }
   
    private function _insert(){
        $this->debugBacktrace();
        $parameter = $this->insertParam;
        unset($this->insertParam);

        $tableName = $this->tableName;
        unset($this->tableName);

        // if($this->hasRawSql){
        if($this->hasRawSql ){ 
            $sql = $parameter;
        }
        elseif($parameter instanceof stdClass){
            if(isset($parameter->__meta->type)){
                $tableName = $parameter->__meta->type;
            }

            $PropertyValueArray = $this->_createPropertyValueArrayFromStdClass($parameter);
            
            $sql = $this->_prepareInsertSQL($tableName, $PropertyValueArray);
        }

        elseif(is_array($parameter)){
            $keyValueArray = $parameter ;
            $PropertyValueArray = $this->_createPropertyValueArrayFromKeyValuePair($keyValueArray);
            $sql = $this->_prepareInsertSQL($tableName, $PropertyValueArray);
        }
        else{
            //first check whether it has insert keyword
            $parameter = trim($parameter);
            $keyword = substr($parameter,0,6);
            if(strtoupper($keyword)=="INSERT") {
                $sql = $parameter;
            }
            else{
                $commaSeparatedString = $parameter ;
                $PropertyValueArray = $this->_createPropertyValueArrayFromCommaSeparatedString($commaSeparatedString);
                $sql = $this->_prepareInsertSQL($tableName, $PropertyValueArray);
            }
        }

        $isSuccess = $this->_perform_mysql_query($sql);
        $lastId = mysqli_insert_id($this->connection); 
        return $lastId;
    }

    //comes from execute()
    private function _update(){
        $this->debugBacktrace();
        $parameter = $this->updateParam; //transfer to local variable.
        unset($this->updateParam); //reset updateParam.

        $tableName = $this->tableName;
        unset($this->tableName);

       
        $sql = "";
        if($this->hasRawSql){
            $this->debugBacktrace();
            $sql = $parameter;
        }
    
        elseif($parameter instanceof stdClass ){
            $stdClass = $parameter ;
            if(isset($stdClass->__meta->type)){
                $tableName = $stdClass->__meta->type;
            }
            $PropertyValueArray = $this->_createPropertyValueArrayFromStdClass($stdClass);
            
            $sql = $this->_prepareUpdateSQL($tableName, $PropertyValueArray);
        }
        elseif(is_array($parameter)){
            $PropertyValueArray = $this->_createPropertyValueArrayFromKeyValuePair($parameter);
            $sql = $this->_prepareUpdateSQL($tableName, $PropertyValueArray);
        }
        else{
            //first check whether it has update keyword
            $parameter = trim($parameter);
            $update = substr($parameter,0,6);
            if(strtoupper($update)=="UPDATE") {
                $sql = $parameter;
            }
            else{
                $commaSeparatedString = $parameter ;
                $PropertyValueArray = $this->_createPropertyValueArrayFromCommaSeparatedString($commaSeparatedString);
                $sql = $this->_prepareUpdateSQL($tableName, $PropertyValueArray);
            }
        }

        $isSuccess = $this->_perform_mysql_query($sql);
        return mysqli_affected_rows($this->connection);
    }

    //comes from execute()
    private function _delete(){
        $this->debugBacktrace();
        $parameter = $this->deleteParam; //transfer to local variable.
        unset($this->deleteParam); //reset updateParam.

        $tableName = $this->tableName;
        unset($this->tableName); //reset.
       
        $sql = "";
        if($this->hasRawSql){
            $sql = $parameter;
        }
        else{
            if(isset($parameter) && !empty($parameter)){
                if($parameter instanceof stdClass ){
                    $stdClass = $parameter ;
                    if(isset($stdClass->__meta->type)){
                        $tableName = $stdClass->__meta->type;
                    }
                    if(isset($stdClass->__meta->primaryKey)){
                        $pkColumn = $stdClass->__meta->primaryKey;
                    }
                    else{
                        $pkColumn = $this->__findPrimaryKeyColumnName($tableName);
                    }
                    $keyValueArray = (array) $stdClass;
                    
                    $id = $keyValueArray[$pkColumn];
                    $sql = "DELETE FROM $tableName WHERE $pkColumn = " . $this->_real_escape_string($id);
                }
                else{
                    //first check whether it has 'delete' keyword
                    $parameter = trim($parameter);
                    $update = substr($parameter,0,6);
                    if(strtoupper($update)=="DELETE") {
                        $sql = $parameter;
                    }
                    else{
                        $pkColumn = $this->__findPrimaryKeyColumnName($tableName);
                        $id = $parameter;
                        $sql = "DELETE FROM $tableName WHERE $pkColumn = " . $this->_real_escape_string($id);
                    }
                }
            }
            else{
                //it is assumed that there is where clause.
                $sql = "DELETE FROM $tableName WHERE " . $this->whereClause;
                unset($this->whereClause);
            }
        }

        $isSuccess = $this->_perform_mysql_query($sql);
        return mysqli_affected_rows($this->connection);
    }
    #endregion

    #region Troubleshoot and Logging
    private $isEnabledSqlLogging = false;
    public function enableSqlLogging(){
        $this->isEnabledSqlLogging = true;
        return $this;
    }

    public function disableSqlLogging(){
        $this->isEnabledSqlLogging = false;
        return $this;
    }
    private $isEnabledSqlPrinting = false;
    public function enableSqlPrinting(){
        $this->isEnabledSqlPrinting = true;
        return $this;
    }
    public function disableSqlPrinting(){
        $this->isEnabledSqlPrinting = false;
        return $this;
    }

    private $isEnableDebugBacktrace = false;
    public function enableDebugBacktrace(){
        $this->isEnableDebugBacktrace = true;
        return $this;
    }

    public function disableDebugBacktrace(){
        $this->isEnableDebugBacktrace = false;
        return $this;
    }

    private $callCounter = 1;
    private function debugBacktrace(){
        if($this->isEnableDebugBacktrace){

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
        $this->debugBacktrace();
        $sql = "TRUNCATE TABLE `$tableName`";
        $this->_perform_mysql_query($sql);
        return $this;
    }

    //Field, Type, Null, Key, Default, Extra
    public function showColumns($tableName){
        $this->debugBacktrace();
        $sql = "SHOW COLUMNS FROM `$tableName`";
        $queryObject = $this->_perform_mysql_query($sql);

        $rows = array();
        switch ($this->fetchType){
            case "fetch_object":
                while ($row = mysqli_fetch_object($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_assoc":
                while ($row = mysqli_fetch_assoc($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_array":
                while ($row = mysqli_fetch_array($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_row":
                while ($row = mysqli_fetch_row($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_field":
                while ($row = mysqli_fetch_field($queryObject)) {
                    $rows[] = $row;
                }
                break;
        }

        mysqli_free_result($queryObject);
        return $rows;
    }

    public function showTables(){
        $this->debugBacktrace();
        $sql = "SHOW TABLES FROM " . $this->database;
        $queryObject = $this->_perform_mysql_query($sql);
        $rows = array();
        switch ($this->fetchType){
            case "fetch_object":
                while ($row = mysqli_fetch_object($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_assoc":
                while ($row = mysqli_fetch_assoc($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_array":
                while ($row = mysqli_fetch_array($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_row":
                while ($row = mysqli_fetch_row($queryObject)) {
                    $rows[] = $row;
                }
                break;
            case "fetch_field":
                while ($row = mysqli_fetch_field($queryObject)) {
                    $rows[] = $row;
                }
                break;
        }

        mysqli_free_result($queryObject);
        return $rows;
    }
    
    public function getPrimaryKey($tableName){
        $this->debugBacktrace();
        return $this->__findPrimaryKeyColumnName($tableName);
    }

    #endregion

    public function getCSV($sql = ""){

        if(!empty($sql)){
            $this->sql = $sql;
        }

        $select = $this->_prepare_select_sql("many");
     
        $export =  $this->_perform_mysql_query($select);
       
        
        //$fields = mysql_num_fields ( $export );
        $fields =  $this->_mysql_num_fields($export);

        
        for ( $i = 0; $i < $fields; $i++ )
        {
            if($this->php_version == 5){
                $header .= mysql_field_name( $export , $i ) . "\t";
                while( $row = mysql_fetch_row( $export ) )
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
            else{
                $header .= $this->_mysqli_field_name( $export , $i ) . "\t";
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
}

?>