# ZeroSQL v 1.0
A zero-learning-curve and zero-configuration PHP7/MySQL library. Best for small to midsize applications. It looks like SQL, usages human-friendly keyword. Nothing to remember, nothing to memorize.

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



###### Select only one row- `first()`, `firstOrNull()` or `single()`

```php
$db->select("name, age")->from("customer"); 
$db->first(); //select one row with first()
$customer = $db->execute();

echo $customer->name;
echo $customer->age;
```

```php
$db->select("name, age")->from("customer"); 
$db->firstOrNull(); //select one row with firstOrNull()
$customer = $db->execute();
```

```php
$db->select("name, age")->from("customer"); 
$db->single(); //select one row with single()
$customer = $db->execute();
```

**first()** *vs* **firstOrNull** *vs* **single()**

`first()` is used when there are more than one records in the database, but you expect only the first matching row from the database. If no records found in the database, it will throw an **exception**.

`firstOrNull()` is similar to ***first()***. But it will return ***null*** , instead of throwing exception.

`single()` is used when you expect only **exactly** a single row to exist in the table (or search query). If there is no row or more than one row is found, then it will throw an exception. 

###### Limiting data: the take() & skip() methods

`skip()` will ignore the specified number of records and return the rest of the records. The skip() method skips/bypasses a specified number of  rows from a table (or search query) from top and returns the remaining rows.

`take()` will return the specified number of records from the starting point of the table (or search query) and ignore the rest of the records. The take() specifies how many rows we want from the start position of the table (or search query).

For example, you have 10 customers in table. 

```php
$db->select()->from("customer");
$db->take(3); //select only top 3 rows from table
$customers = $db->execute();
```

```php
$db->select()->from("customer");
$db->skip(4); //skips top 4 records, and select next 6 records. 
$customers = $db->execute();
```

```php
$db->select()->from("customer");
$db->skip(8)->take(1); //skips top 8 rows, and select next 2 rows.
$customers = $db->execute();
```

**skip()** and **take()** is a must-have feature for pagination. It shows paginated rows from a large table.



###### ORDERING THE DATA

Methods  for ascending order:

1. `orderBy()`

2. `ascBy()`

3. `ascendingBy()`

   All of the above methods sort the rows based on a specified field in ascending order. You can use any of them as your choice.

Methods  for descending order:

1. `orderByDesc()`

2. `descBy()`

3. `descendingBy()`

   All of the above methods sort the rows based on a specified field in descending order. You can use any of them as your choice.

```php
$db->select()->from("customer");
$db->orderBy("name");		//ascending by name column
$db->orderBy("address");	//you can use multiple ascending methods
$db->orderByDesc("age");    //descening by age column.
$db->orderByDesc("amount"); //you can use multiple descending methods.
$customers = $db->execute();
```



###### SEARCHING/FILTERING

**find()** 

It finds a record with the Primary Key. It is one of the common tasks that is performed on the table.

```php
$customer = $db->find(109)->from('customer')->execute();
```

**where()**, **andWhere()** and **orWhere()** 

You can have more control on your search. You can use multiple **andWhere()** and **orWhere()**. 

```php
$db->select()->from("customer");
$db->where("name");  	//search on "name" column
$db->equalTo('John');
$db->andWhere("age");  	//search on "age" column
$db->greaterThan(30);
$db->execute();

//or, in a single line
$customer = $db->select()->from("customer")->where("name")->equalTo('John') 
                ->andWhere("age")->greaterThan(30)->execute();
```

You can use the following operators. All are self-explanatory.

- equalTo()
- greaterThan()
- greaterThanOrEqualTo()
- lessThan()
- lessThanOrEqualTo()
- between()
- startWith()
- notStartWith()
- endWith()
- notEndWith()
- contain()
- notContain()
- like()            - You can use raw wildcard characters here. 
- notLike()     - You can use raw wildcard characters here. 



###### AGGREGATE FUNCTIONS

**count()**

```php
$quantity = $db->count("name")->from('customer')->execute();
```

**sum()**

```php
$total = $db->sum("order")->from('customer')->execute();
```

**min()**

```php
$minimum = $db->min("order")->from('customer')->execute();
```

**max()**

```php
$maximum = $db->max("order")->from('customer')->execute();
```

**groupBy()** 

```php
$orderTotal = $db->sum("order_amount")->from('customer')->groupBy("type")-> execute();
```

**having()**, **andHaving()** and **orHaving()**

Very similar to where(), andWhere() and orWhere().

```php
$db->sum("order_amount")->from('customer');
$db->groupBy("type")->groupBy("age");
$db->having("type")->equalTo("regular");
$db->orHaving("age")->greaterThan(5);
$orderTotal = $db-> execute(); 

//OR, in a single line-
$orderTotal = 	$db->sum("order_amount")->from('customer')->groupBy("type")
    			->groupBy("age")->having("type")->equalTo("regular")->orHaving("age")
    			->greaterThan(5)-> execute();
```

You can use the following operators. All are self-explanatory.

- equalTo()
- greaterThan()
- greaterThanOrEqualTo()
- lessThan()
- lessThanOrEqualTo()
- between()
- startWith()
- notStartWith()
- endWith()
- notEndWith()
- contain()
- notContain()
- like()            - You can use raw wildcard characters here. 
- notLike()     - You can use raw wildcard characters here. 

###### ELIMINATE DUPLICATE VALUES

`distinct()` can be used to return unique rows from a result set. Please note that, it's usage is ***different*** in ***select*** and ***aggregate*** queries.

```php
//distinct in select queries
$types = $db->distinct("type")->from('customer')-> execute();

//distinct in aggregate queries-
$customers= $db->count("type")->distinct()->from('customer')-> execute();
```



## INSERTING NEW RECORD IN DATABASE

The following syntax initiates an insert operation and returns auto-increment primary key value-

```
$db->insert($insertParam);
```

`$insertParam` can be one of the following-

- ZeroObject
- KeyValue array
- Comma separated string

###### INSERT AS ZERO OBJECT

First, create a new instance of ZeroObject. Please note that **createObject()** parameter must be the same as table name.

```php
$newCustomer = $db->createObject("customer");
```

Now, add properties to the `$customer`. Here property name acts as table column name. If you use **"camelCase"** in the property name, it will be converted into **"camel_case"**. 

```php
$newCustomer->name = "John Doe";
$newCustomer->age = 39;
$newCustomer->dob = "1980-01-19";
$newCustomer->orderAmount = 500.00; //orderAmount will be converted to order_amount
$newCustomer->type = "regular";
$newCustomer->countryId = 1;        //countryId will be converted to country_id
```

Finally, insert the `$newCustomer`

```php
$customer->customerId = $db->insert($newCustomer)->execute();
```



$db->select();
$db->select();

​    $db->from("customer");

​    $result  = $db->execute();

$query = $db->select()->from("customer")->execute();



```
$result = $db->select()->from("table1")->execute();
```

