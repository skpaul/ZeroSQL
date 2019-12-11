# SwiftSql
A dead-simple PHP library for MySQL CRUD 

# Create a new connection
$db = new Database();

$db->Server("server")->User("user")->Password("password")->Database("swift_sql")->Connect();
