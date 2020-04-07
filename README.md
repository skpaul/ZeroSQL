# ZeroSQL v 1.0
A zero-learning-curve and zero-configuration PHP7/MySQL library. Best for small to midsize applications.

## Supported PHP version
PHP7.

## Installation
Download the repository. `require_once ("ZeroSQL.php");` in your php script.
That's all.

## Get started
Create a new instance of ZeroSQl `$db = new ZeroSQl();`

## Connect to database
```
$db->server("localhost")->user("root")->password("123")->database("sample_db");
```
You can also write the above in multiple lines-
```
$db->server("localhost");
$db->user("root");
$db->password("123");
$db->Database("sample_db"); 
$db->Connect();
```

## Reading from database
Start selecting data from database with `select()` method.

```php
$db->select();                //initiates a select query
$db->from("customer");        //selects data from customer table
$customers  = $db->execute(); //returns all customers as an object array
```

Now loop $customers-

```php
foreach($customers as $customer){
    echo $customer->id;
    echo $customer->name;
    echo $customer->age;
}
```

You can rewrite the above select query in a number of ways-

```php
$db->select()->from("customer");    //initiates a select query from customer table
$customers  = $db->execute();       //returns all customers as an object array
```

OR,

```php
$customers = $db->select()->from("customer")->execute();
```

But, always remember that `execute()` must be the last method call.

###### Select all columns

You already have selected all columns in the above example. Let's see it again - 

```php
$customers = $db->select()->from("customer")->execute();
```

Keep the `select()` method empty to select all columns from customer table.

###### Select specific columns 

```php
//select only name & age columns from customer table
$customers = $db->select("name, age")->from("customer")->execute(); 
```

###### Select all rows

You already have selected all rows in one of the above example. Let's see it again - 

```php
$customers = $db->select()->from("customer")->execute();
```

###### Select only one row

```php
$db->select("name, age")->from("customer"); 
$db->first(); //select one row with first()
$customers = $db->execute();
```

```php
$db->select("name, age")->from("customer"); 
$db->single(); //select one row with single()
$customers = $db->execute();
```

###### `first()` vs `single()`

`first()` is used when there are more than one records in the database, but you expect only the first matching row from the database. If no records found in the database, it will throw an **exception**.




```
 $db->select();
    $db->from("customer");
    $result  = $db->execute();
$query = $db->select()->from("customer")->execute();

```
```
$result = $db->select()->from("table1")->execute();
```

