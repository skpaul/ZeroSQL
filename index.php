<?php
    require_once("ZeroSQL-v2.php");


    $db = new ZeroSQL();
    $db->Server("localhost")->User("root")->Password("")->Database("zero_sql_test");
    $db->connect();

    try {
        // $rows = $db->select("name")->sum("id")->from("test")->groupBy("name") ->toList();
        // echo $db->getSQL();  echo "<br>";
        // var_dump($rows); echo "<br>";

        // $rows = $db->max("id")->from("test")->toList();
        // echo $db->getSQL();  echo "<br>";
        // var_dump($rows); echo "<br>";

        // $rows = $db->select("name")->from("test")->find(1);
        // echo $db->getSQL();  echo "<br>";
        // var_dump($rows); echo "<br>";

        // $rows = $db->select("name")->from("test")->where("id")->equalTo(5)->firstOrNull();
        // $rows = $db->count("name, count(id) as qty")->from("test")->groupBy("name")->toList();
        // $test = $db->new("test");
        // $test->name = "skpaul";
        // $test->title = "Mr.";
        // $test->id = $db->insert("name='sk', title='nr', number=2")->into("test")->execute();

        // $test->name = "skpaul 7";
        // $array = array("name"=>"hi", "number"=>2);
        // $array["title"]= "teacher";
        // $result = $db->insert($array)->into("test")->execute();

        $rows= $db->delete("test")->where("number = 2")->execute();
        // $rows = $db->deleteFrom("test")->where("id")->endWith(2)->execute();
        // echo $db->getSQL();  echo "<br>";
        var_dump($rows); echo "<br>";
      
    } catch (\ZeroException $th) {
        echo "here sql-" . $db->getSQL();  echo "<br>";
        echo $th->getMessage();  echo "<br>";
    }
  
    // $db->select()->find()

?>
