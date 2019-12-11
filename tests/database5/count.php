<?php 

    include_once("../../../../CONSTANTS.php");

    require_once(ROOT_DIRECTORY . "\php\Logger\Logger.php");
    require_once(ROOT_DIRECTORY . "\php\Database\Database5.php");

    $logger = new Logger(ROOT_DIRECTORY);

    $db = new Database($logger);

    $db->connect(DATABASE_SERVER, DATABASE_USER_NAME, DATABASE_PASSWORD, DATABASE_NAME);

    //http://localhost/SwiftFramework/php/Database/tests/database5/count.php
    
    //Test
    $query = $db->min("age")->from("test");
    $result = $query->execute();
    //Result- Passed.
   
    
    $row = $result;
    var_dump($result);
   
?>