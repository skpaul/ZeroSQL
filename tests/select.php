<?php 

    include_once("../../../../CONSTANTS.php");

    require_once(ROOT_DIRECTORY . "\php\Logger\Logger.php");
    require_once(ROOT_DIRECTORY . "\php\Database\Database5.php");

    $logger = new Logger(ROOT_DIRECTORY);

    $db = new Database($logger);

    $db->connect(DATABASE_SERVER, DATABASE_USER_NAME, DATABASE_PASSWORD, DATABASE_NAME);

    //http://localhost/SwiftFramework/PHP/Database/tests/database5/select.php


    //Test
    $query = $db->SelectMany()->Columns("id,str_col")->From("table_1")->OrderBy("str_col")->Where("date_col")->GreaterThanOrEqualTo("2019-01-01")-> Execute();
    //Result- Passed.

    echo "<br> ============================== <br>";
    
    foreach($query->rows as $row){
        echo "id:" . $row["id"] . ", str_col: ". $row["str_col"] ." <br>";
    }

   //var_dump($query);
   
    
    //select()->single() starts -----------------
?>