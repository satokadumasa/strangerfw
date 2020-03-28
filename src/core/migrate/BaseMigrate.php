<?php
namespace strangerfw\core\migrate;

class BaseMigrate {
  private $dbh = null;
  public function __construct($default_database) {
    $this->error_log = new \strangerfw\utils\Logger('ERROR');
    $this->info_log = new \strangerfw\utils\Logger('INFO');
    $this->debug = new \strangerfw\utils\Logger('DEBUG');
    $this->debug->log('BaseMigrate::__constructor()');
    $this->dbConnect = new \strangerfw\utils\DbConnect();
    $this->debug->log('BaseMigrate::__constructor() CH-01');
    $this->dbConnect->setConnectionInfo($default_database);
    $this->debug->log('BaseMigrate::__constructor() CH-02');
    $this->dbh = $this->dbConnect->createConnection();
  }

  public function up($sql) {
    $this->debug->log('BaseMigrate::up() CH-01');
    $this->dbh->beginTransaction();
    $this->debug->log('BaseMigrate::up() CH-02');
    $this->dbh->query($sql);
    $this->debug->log('BaseMigrate::up() CH-03');
    $this->dbh->commit();
    $this->debug->log('BaseMigrate::up() CH-04');
  }

  public function down($sql){
    $this->dbh->beginTransaction();
    $this->dbh->query($sql);
    $this->dbh->commit();
  } 
}
