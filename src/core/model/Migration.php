<?php
namespace strangerfw\core\model;

class Migration extends BaseModel {
  public $table_name  = 'migrations';
  public $model_name  = 'Migration';
  public $model_class_name  = 'MigrationModel';
  public $primary_key = 'version';

  public $belongthTo  = null;
  public $has = null;
  public $has_many_and_belongs_to = null;

  public function __construct(&$dbh = null) {
    echo "  Migration::__construct() \n";
    parent::__construct($dbh);
  }

  public function insert($data) {
    $sql = <<<EOM
INSERT INTO $this->table_name (
  version,
  name,
  created_at,
  modified_at
) VALUES (
  :version,
  :name,
  now(),
  now()
);
EOM;
    $stmt = $this->dbh->prepare($sql);
    $stmt->bindValue('version', $data[$this->model_name]['version'], \PDO::PARAM_INT);
    $stmt->bindValue('name', $data[$this->model_name]['name'], \PDO::PARAM_STR);
    $stmt->execute();
  }

  public function getMaxVersion(){
    $sql = <<<EOM
SELECT MAX(version) as version FROM $this->table_name;
EOM;
    $stmt = $this->dbh->prepare($sql);
    if(!$stmt) echo "      stmt is null\n";
    $data = null;
    foreach ($this->dbh->query($sql) as $row) {
      echo "        row[]\n";
      $version = $row[0];
    }
    return $version;
  }
}
