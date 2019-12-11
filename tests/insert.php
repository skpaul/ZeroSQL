<?php 

    require_once("../../../../CONSTANTS.php");
   
    require_once(ROOT_DIRECTORY . "\PHP\Logger\Logger.php");

    require_once(ROOT_DIRECTORY . "\PHP\Database\Database5.php");
    
    $logger = new Logger(ROOT_DIRECTORY);
    $db = new Database($logger);
    $db->connect(DATABASE_SERVER, DATABASE_USER_NAME, DATABASE_PASSWORD, DATABASE_NAME);

    $array = array('name'=>"Bon'd");
    $array["age"] = null;

    
    //Test
    //$result = $db->insert($array)->into("test")->return_auto_id()->execute();
    //Result- Passed.

    //Test
    // $result = $db->insert()->sql("insert into test(name) values('ghonta')")->return_auto_id()->execute();
    //Result- Passed.

    //Test
    //$result = $db->Insert($array)->Into("test")->execute();
    //Result- Passed.

   // $result = $db->Insert()->Into("table_1")->Columns("int_col,str_col,date_col, datetime_col")->Values("100, KK, NULL, 2017-12-12 12:12:12")->execute();
    
    $colums = array("int_col","str_col", "date_col", "datetime_col");
    $result = $db->Insert()->Into("table_1")->Columns($colums)->Values($colums)->execute();


    //http://localhost/SwiftFramework/php/Database/tests/database5/insert.php
    
  
?>