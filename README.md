# ZeroSQL (Beta)
A zero-learning-curve and zero-configuration PHP7/MySQL library. Best for small to midsize applications. It looks like SQL, usages human-friendly keyword. Nothing to remember, nothing to memorize.

## First look

```php
$db = new ZeroSQL();

$newCustomer = $db->new("customer");
$newCustomer->name = "Saumitra Kumar Paul";
$newCustomer->age = 40;
$newCustomer->country = "Bangladesh";

$db->insert($newCustomer)->execute();

$existingCustomer = $db->find(20)->from("customer")->execute();
$existingCustomer->age = 45;
$db->update($existingCustomer )->execute();

$db->delete(20)->from("customer")->execute();
```

### Sounds interesting ??

Let's discover more ....



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
//OR
$db->insert($insertParam)->into($tableName);
```

`$insertParam` can be one of the following-

- ZeroObject
- KeyValue array
- Comma separated string

###### INSERT AS ZERO OBJECT

First, create a new instance of ZeroObject. Please note that **createObject()** parameter must be the same as table name. In the following example, *customer* is the name of table.

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
//Saves data and returns auto-increament primary key "customer_id"
$customer->customerId = $db->insert($newCustomer)->execute();
```

Please note that, when you are inserting as zero object, you don't need to provide table name. 

###### INSERT AS KEY-VALUE ARRAY

```
$newCustomer = array();
$newCustomer["name"] = "John Doe";
$newCustomer["age"] = 39;
$newCustomer["dob"] = "1980-01-19";
$newCustomer["orderAmount"] = 500.00;
$newCustomer["type"] = "regular";
$newCustomer["countryId"] = 1;
```

```
$customerId = $db->insert($newCustomer)->into("customer")->execute();
```

###### INSERT AS COMMA-SEPARATED STRING

```
$customerId = $db->insert("name=John Doe, age=12")->into("customer")-> execute();
```

## UPDATING A RECORD

The following syntax initiates an update operation and returns the numbers of affected rows -

```
$db->update($updateParam)->into($tableName);
```

`$updateParam` can be one of the following-

- stdClass object
- KeyValue array
- Comma separated string

###### Update from stdClass object

No need to provide any where clause, given that the object has a primary key value.

```php
//read a record from table to update-
$oldCustomer = $db->find(2)->from("customer")->execute();

//change some value
$oldCustomer->order_amount = 120.00 //change value.

//update in table
$affectedRows = $db->update($oldCustomer)->into("customer")->execute();
```

###### Update as Key-Value array

Does not require a where clause, if you have primary key value in the data-

```php
//Prepare the key-value array
$data = array();
$data["customer_id"] = 3; //primary key and value
$data["orderAmount"] = 500.00;
$data["type"] = "regular";

//Update using the 
$affectedRows = $db->update($data)->into("customer")
                   ->execute();
```

Require a where clause, if you don't have primary key value in the data-

```php
//Prepare the key-value array
$data = array();
$data["orderAmount"] = 500.00;
$data["type"] = "regular";

//Update using the 
$affectedRows = $db->update($data)->into("customer")
                   ->where("name")->equalTo("John Doe")
                   ->execute();
```

###### Update as comma-separated string

Does not require a where clause, if you have primary key value in the data-

```php
$data = "orderAmount=99.00, type=Foreign, customer_id=4";
$affectedRows = $db->update($data)->into("customer")->execute();
```

Require a where clause, if you don't have primary key value in the data- 

```
$data = "orderAmount=111.00, type=Foreign";
$affectedRows = $db->update($data)->into("customer")
                   ->where("name")->equalTo("John Doe")
                   ->execute();
```

## DELETE RECORD FROM DATABASE

The following syntax initiates an **delete** operation and returns the numbers of affected rows -

```php
//Using a traditional where clause
$db->delete()->from($tableName)->where() ......;

//OR, using an instance of stdClass object.
$db->delete($deleteParam)->from($tableName);
```

Exaample: Delete using traditional where clause-

```php
$affectedRows = $db->delete()->from("customer")
                   ->where("type")->equalTo("regular")
                   ->execute();
```

Exaample: Delete using  an instance of stdClass object -

```php
//First, read the data from database.
//It will return an instance of stdClass object.
$oldCustomer = $db->find(2)->from("customer")->execute();

//Now delete using that object.
$affectedRows = $db->delete($oldCustomer)->from("customer")->execute();
```



## FORMAT OF RETURNED DATA FROM SELECT METHOD

The default format of dataset is an object or an array of object.

You can always change the default behavior -

###### fetchObject()

will return the current row result set as an object from *mysqli_fetch_object()* 

```
$db->fetchObject();
```

###### fetchAssoc()

return an associative array from *mysqli_fetch_assoc()*

```
$db->fetchAssoc();
```

###### fetchArray()

function fetches a result row as an associative array, a numeric array, or both from *mysqli_fetch_array()*

```
$db->fetchArray();
```

###### fetchRow()

returns a row from a recordset as a numeric array. mysqli_fetch_row()

```
$db->fetchRow();
```

###### fetchField()

returns the definition of one column of a result set as an object.  mysqli_fetch_field()

```
$db->fetchField();
```



## Still passionate for raw SQL?

No problem!! 

ZeroSQL has that much of flexibility-

You can write any sort of SQL statement in select(), insert(), update() and delete() method.

```php
$result = $db->select("select.from.where.having.orderby.groupby.anything")->execute();

$result = $db->insert("insert...into...values...anything")->execute();

$result = $db->update("update...table...set...where...anything")->execute();

$result = $db->delete("delete...from...where...anything")->execute();
```



## Debugging and Troubleshooting

**enableSqlLogging()** and **disableSqlLogging()**

Enable/Disable storing SQL in a text file. 

**enableSqlPrinting()** and **disableSqlPrinting()**

Enable/Disable printing SQL with echo command. 

**enableDebugBacktrace()** and **disableDebugBacktrace()**

Shows the function calling flow-

01 → update()
02 → into()
03 → execute()
04 → execute() → _update()

