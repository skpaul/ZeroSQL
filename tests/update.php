<?php 

    include_once("../../../../CONSTANTS.php");

    require_once(ROOT_DIRECTORY . "\php\Logger\Logger.php");
    require_once(ROOT_DIRECTORY . "\php\Database\Database5.php");

    $logger = new Logger(ROOT_DIRECTORY);

    $db = new Database($logger);

    $db->connect(DATABASE_SERVER, DATABASE_USER_NAME, DATABASE_PASSWORD, DATABASE_NAME);

   

    //Test
    //$result = $db->update("test")->set("age=999")->set("name='boloram'") ->where("id=2")->execute();
    //Result- Passed.
  
    //Test
    $result = $db->update()->table("test")->set("age=NULL")->set("name=NUrLL")->where("id=11")->execute();
    //Result- Passed.
    
    $array = array('name'=>'gholghat');
    $array["age"] = 112;
    //Test
   // $result = $db->update()->table("test")->set($array)->where("id=6")->execute();
    //Result- Passed.

    //Test
    //$result = $db->update()->sql("update test set name='ABCD' where id=5")->execute();
    //Result- Passed.

    //http://localhost/SwiftFramework/php/Database/tests/database5/select.php
    
    var_dump($result);
?>