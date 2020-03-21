<?php
class <!----class_name----> extends \strangerfw\core\model\BaseModel {
  public $table_name  = '<!----table_name---->';
  public $model_name  = '<!----class_name---->';
  public $model_class_name  = '<!----class_name---->';

  //  Relation
  public $belongthTo = null;
  public $has = null;
  public $has_many_and_belongs_to = null;

  public function __construct(&$dbh) {
    parent::__construct($dbh);
  }
}
