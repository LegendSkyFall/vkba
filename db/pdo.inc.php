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
        parent::_construct('mysql_host=' . MYSQL_HOST . ';dbname=' . MYSQL_DB . ';charset=utf8', MYSQL_USER, MYSQL_PASS, $options);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }catch(Exception $e){
      if(DEBUG){
        echo $e->getMessage() . "<br>";
      }
      exit("Internal server error. Could not connect to specified database.");
    }
  }
}

# create instance
$db = new DB();
?>
