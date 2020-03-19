<?php
namespace strangerphp\core\migrate;
class BaseMigrate {
  private $dbh = null;
  public function __construct($default_database) {
    $this->error_log = new Logger('ERROR');
    $this->info_log = new Logger('INFO');
    $this->debug = new Logger('DEBUG');

    $this->dbConnect = new DbConnect();
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