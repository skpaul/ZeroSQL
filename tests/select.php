<?php 

    require_once("CONSTANTS.php");

    require_once(ROOT_DIRECTORY . "\Logger.php");
    require_once(ROOT_DIRECTORY . "\Database.php");

    $logger = new Logger(ROOT_DIRECTORY);
    $db = new Database($logger);

    $db->connect(DATABASE_SERVER, DATABASE_USER_NAME, DATABASE_PASSWORD, DATABASE_NAME);

    //Test
    $query = $db->SelectMany()->Columns("id,str_col")->From("table_1")->OrderBy("str_col")->Where("date_col")->GreaterThanOrEqualTo("2019-01-01")-> Execute();
    //Result- Passed.

    echo "<br> ============================== <br>";
    
    foreach($query->rows as $row){
        echo "id:" . $row["id"] . ", str_col: ". $row["str_col"] ." <br>";
    }
?>