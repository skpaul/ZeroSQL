# SwiftSQL v 0.0.1
A dead-simple PHP library for MySQL CRUD 

# Supported PHP version
PHP5 and PHP7.

# Create a new connection
$db = new Database();

$db->Server("server");

$db->User("user");

$db->Password("password"); <br>
$db->Database("swift_sql");

$db->Connect();

//OR, in a single line\
$db->Server("server")->User("user")->Password("password")->Database("swift_sql")->Connect();

