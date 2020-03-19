<?php
class DbConnect {
  protected $debug = null;

  public function __construct() {
  }

  public function setConnectionInfo($connection_info){
    $this->debug = new Logger('DEBUG');
    $this->rdb = $connection_info['rdb'];
    $this->host = $connection_info['host'];
    $this->port = isset($connection_info['port']) ? $connection_info['port'] : 3306; 
    $this->dbname = $connection_info['dbname'];
    $this->charset = $connection_info['charset'];
    $this->username = $connection_info['username'];
    $this->password = $connection_info['password'];
  }

  public function createConnection() {
    try {
      $dsn = $this->rdb . ":host=" . $this->host . ";port=".$this->port.";dbname=" . $this->dbname . ";charset=" . $this->charset;

      $this->debug->log("dsn:".$dsn);
      $dbh = new PDO(
        $dsn,
        $this->username,
        $this->password,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_EMULATE_PREPARES => false, 
          PDO::ATTR_FETCH_TABLE_NAMES => 1
        ]
      );
      $this->debug->log("dbh:".print_r($dbh, true));

      return $dbh;
    } catch (PDOException $e) {
      throw new Exception('データベース接続失敗。'.$e->getMessage(), 1);
    }
  }
}