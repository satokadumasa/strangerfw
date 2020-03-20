<?php
namespace strangerfw\core\migrate;

class BaseMigrate {
  private $dbh = null;
  public function __construct($default_database) {
    $this->error_log = new strangerfw\utils\Logger('ERROR');
    $this->info_log = new strangerfw\utils\Logger('INFO');
    $this->debug = new strangerfw\utils\Logger('DEBUG');

    $this->dbConnect = new strangerfw\utils\DbConnect();
    $this->dbConnect->setConnectionInfo($default_database);
    $this->dbh = $this->dbConnect->createConnection();
  }

  public function up($sql) {
    $this->dbh->beginTransaction();
    $this->dbh->query($sql);
    $this->dbh->commit();
  }

  public function down($sql){
    $this->dbh->beginTransaction();
    $this->dbh->query($sql);
    $this->dbh->commit();
  } 
}