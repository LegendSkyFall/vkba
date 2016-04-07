<?php
# connection information
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'db_user');
define('MYSQL_PASS', 'db_pass');
define('MYSQL_DB', 'db_name');

/*
debugging variable
only for testing purposes,
can contain sensible data
*/
define('DEBUG', true);

# db class
class DB extends PDO{
  public function __construct(){
    try{
      $options = [
          PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
    }
  }
}
?>
