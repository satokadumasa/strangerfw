<?php
class <!----class_name----> extends BaseModel {
  public $table_name  = '<!----table_name---->';
  public $model_name  = '<!----class_name---->';
  public $model_class_name  = '<!----class_name---->';

  //  Relation
  public $belongthTo = null;
  public $has = null;
  public $has_many_and_belongs_to = null;
/*
  public $columns = [
    'id' => array('type' => 'int', 'length' => 8, 'null' => false, 'key' => 'PRI', 'default' => null, ), 
    <!----model_columns---->
    'created_at' => array('type' => 'datetime', 'length' => 19, 'null' => false, 'key' => 'PRI', 'default' => null, ), 
    'modified_at' => array('type' => 'datetime', 'length' => 19, 'null' => false, 'key' => 'PRI', 'default' => null, ), 
  ];
*/

  public function __construct(&$dbh) {
    parent::__construct($dbh);
  }
}
