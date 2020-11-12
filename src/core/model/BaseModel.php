<?php
namespace strangerfw\core\model;

class BaseModel {
  //  DBハンドル
  protected $dbh   = null;
  //  検索条件指定
  protected $conditions = [];
  //  並び順指定
  public $ascs = [];
  protected $keys = null;
  protected $max_rows = 0;
  protected $limit_num = 0;
  protected $offset_num = 0;

  public $error_log;
  public $info_log;
  public $debug;
  public $column_conf;
  public $select;

  protected $form;

  public $primary_key = 'id';

  public $primary_key_value = null;
  protected $joins = [];
  /**
   *  コンストラクタ
   *
   *  @param PDOObject &$dbh データベース接続ハンドラ
   */
  public function __construct(&$dbh) {
    try{
      if($dbh) $this->setDbh($dbh);

      $this->error_log = new \strangerfw\utils\Logger('ERROR');
      $this->info_log = new \strangerfw\utils\Logger('INFO');
      $this->debug = new \strangerfw\utils\Logger('DEBUG');
      $this->debug->log('BaseModel::__construct()');
      $columns = \Spyc::YAMLLoad(SCHEMA_PATH.$this->table_name.".yaml");
      $this->column_conf = $columns[$this->table_name];
    } catch(\Exception $e) {
      $this->debug->log("BaseModel::__construct() error:" . $e->getMessage());
    }
  }

  /**
   *  テーブル名設定
   *
   *  @param string $table_name テーブル名
   *  @return BaseModel $this
   */
  public function setTableName($table_name) {
    $this->table_name = $table_name;
    return $this;
  }

  /**
   *  テーブル名設定
   *
   *  @param PDOObject &$dbh データベース接続ハンドラ
   *  @return BaseModel $this
   */
  public function setDbh (&$dbh) {
    if ($dbh == null || $dbh == '') throw new \Exception("DataBase handle is null.", 1);
    $this->dbh = $dbh;
    return $this;
  }

  // 検索関連

  /**
   *  モデルの検索
   *
   *  @param string $type 'all':全件 'first':先頭一件
   *  @return array $datas 検索結果データ格納配列
   */
  public function find($type = 'all') {
    $this->debug->log("------------------------------------------------------");
    $datas = [];
    $primary_keys = [];

    $sql = $this->createFindSql();

    $column_names = null;

    $this->debug->log("BaseModel::find() sql(1):".$sql);
    $stmt = $this->dbh->prepare($sql);
    foreach ($this->conditions as $v) {
      $arr = explode('.', $v['column_name']);
      $value = $v['value'];
      $col_name = $arr[count($arr) - 1];

      $column_name = str_replace('.', '_', $v['column_name']);
      $column_name = \strangerfw\utils\StringUtil::convertTableNameToClassName($column_name);

      switch ($col_name) {
        case 'created_at':
        case 'modified_at':
          if ($v['operator'] != 'IS NULL') {
            $value = $value ? $value : 'NOW()';
            $stmt->bindParam($column_name, $value, \PDO::PARAM_STR);
          }
          break;
        default:
          if ($v['operator'] != 'IS NULL' && $v['operator'] != 'IN') {
            $param_type = is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($column_name, $value, $param_type);
          }
          break;
      }
    }
    $this->debug->log("BaseModel::find() sql(2):".$sql);
    $stmt->execute();

    foreach ($stmt->fetchAll() as $row) {
      $data = [];
      if(!$column_names) $column_names = array_keys($row);
      foreach ($column_names as  $column_name) {
        if(is_int($column_name)) continue;
        list($model_name, $col_name) = explode(".", $column_name);
        $data[$model_name][$col_name]= $row[$column_name];
      }

      if (array_search($data[$this->model_name][$this->primary_key], $primary_keys)) continue;

      $primary_keys[] = $data[$this->model_name][$this->primary_key];
      $datas[$data[$this->model_name][$this->primary_key]] = $data;
    }


    if ($type === 'first') {
      if (!isset($primary_keys[0])) return false;
      $id = $primary_keys[0];
      $datas = $datas[$id];
    }

    return $datas;
  }

  /**
   * findBySql()
   *
   * @params $sql
   *
   * @return $data
   */
  public function find_by_sql($sql) {
    $data = [];
    $stmt = null;
    $stmt = $this->dbh->prepare($sql);
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
      $column_names = array_keys($row);
      foreach ($column_names as  $column_name) {
        if(is_int($column_name)) continue;
        list($model_name, $col_name) = explode(".", $column_name);
        $model_name = $model_name ? $model_name : $this->table_name;
        $data[$model_name][$col_name]= $row[$column_name];
      }
    }
    return $data;
  }

  /**
   *  HasOne/HasManyなモデルの検索
   *
   *  @param array &$data 検索結果データ格納配列
   *  @param array $has 子モデル
   *  @param array $primary_keys 検索親IDs
   *  @retrun none
   */
   public function findHasModelesData(&$datas, $has = null, $primary_keys = null) {
     foreach ($has as $model_name => $options) {
       $model_class_name = $model_name;
       $obj = new $model_class_name($this->dbh);
       $setDatas = $obj->where($options['foreign_key'], 'IN', $primary_keys)->find();
       $this->setHasModelDatas($model_name, $options['foreign_key'],$datas, $setDatas, $primary_keys);
     }
   }

  /**
   *  HasManyAndBelongsToなモデルの検索
   *
   *  @param array &datas 検索結果格データ納配列
   *  @param array $primary_keys 検索親IDs
   *  @retrun none
   */
   public function findHasManyAndBelongsTo(&$datas, $primary_keys = null)
   {
     foreach ($this->has_many_and_belongs_to as $hasModeName => $options)
     {
       $belongth_to_model_name = $options['through'];
       $belongth_to_model_class_name = $belongth_to_model_name;
       $belongth_to_model_class_instance = new $belongth_to_model_class_name($this->dbh);
       $setDatas = $belongth_to_model_class_instance->where($options['foreign_key'], 'IN', $primary_keys)->find();

       foreach ($belongth_to_model_class_instance->belongthTo as $model_name => $value)
       {
         if ($hasModeName === $model_name)
         {
           foreach ($primary_keys as $primary_key) {
             foreach ($setDatas as $setData) {
               if ($setData[$this->model_name][$this->primary_key] == $primary_key) {
                 $datas[$setData[$this->model_name][$this->primary_key]][$this->model_name][$model_name][][$model_name] = $setData[$model_name];
               }
             }
           }
         }
       }
     }
   }

  /**
   *  検索SQL生成処理
   *
   *  $user = new User();
   *  $user->join('UserInfo' => ['Pref','City'], 'Job', 'Role');
   *
   *  @retrun string $sql
   */
  private function createFindSql(){
    $this->debug->log("BaseModel::createFindSql() model_name:" . $this->model_name);
    $sql = null;
    $relationship_conds = [];
    $relationship_columuns = [];
    $relationship_sql = [];
    $relationship_columuns[] = " " . $this->model_name . ".*";

    $tmp_sql = '';
    // Generate join cond
    $this->processJoins($tmp_sql, $this->joins);
    $this->debug->log("BaseModel::createFindSql() tmp_sql[${tmp_sql}]");

    $sql = "SELECT ";

    if($this->columns)
      $sql .= $this->extendSelects();
    else
      foreach ($relationship_columuns as $value)
        $sql .= $value;

    $sql .= " FROM " . $this->table_name . " as " . $this->model_name . " ";

    if (is_array($relationship_sql)) foreach ($relationship_sql as $value) $sql .= $value;

    $sql .= " ";

    foreach ($relationship_conds as $value) {
      $sql .= $value;
    }

    $sql .= $tmp_sql ? " ${tmp_sql} " : '';
    $this->debug->log("BaseModel::createFindSql() sql(1):[${sql}]");

    $sql .= $this->createCondition();

    if (count($this->ascs) > 0 ) {
      $sql .= ' ORDER BY ';
    }

    if (count($this->ascs) > 0) {
      foreach ($this->ascs as $asc) {
        $sql .= $asc;
      }
    }

    if($this->limit_num > 0) $sql .= " LIMIT " . $this->limit_num ." ";
    if($this->offset_num > 0) $sql .= " OFFSET " . $this->offset_num . " ";

    $this->debug->log("BaseModel::createFindSql() sql:" . $sql);

    return $sql;
  }

  /**
   * extendSelects()
   *
   * @return string column_str
   */
  private function extendSelects() {
      $column_str = '';
      if($this->columns) {
          foreach($this->columns as $model_name => $columns) {
              foreach ($columns as $column) {
                  $column_str .= $column_str ? ',' : ' ';
                  $column_str .= $model_name . '.' . $column. ' ';
              }
          }
      }
      return $column_str;
  }

  /**
   * processJoins()
   *
   * JOIN句生成処理
   *
   * @params string $tmo_sql
   * @params array $joins
   */
  public function processJoins(&$tmp_sql, $joins) {
    if(is_array($joins) && count($joins) > 0) {
      foreach($joins as $model_name => $join) {
        $model_name = !is_numeric($model_name) ? $model_name : $join;

        if(is_array($this->belongthTo)  && array_key_exists($model_name, $this->belongthTo)){
          $belongth = new $model_name($this->dbh);
          $this->joinBelongthTo($belongth, $this->belongthTo[$model_name], $join, $tmp_sql);
          continue;
        }
        if(is_array($this->has) && array_key_exists($model_name, $this->has)) {
          $has = new $model_name($this->dbh);
          $this->joinHas($has, $this->has[$model_name], $join, $tmp_sql);
          continue;
        }
      }
    }
  }

  /**
   * join elongthTo()
   *
   * 従属モデルをjoinする
   *
   * @params Model $obj
   * @params array $cond
   * @params array $join
   * @params string $tmp_sql
   *
   * @return
   */
  public function joinBelongthTo($obj, $cond, $joins, &$tmp_sql) {
    $tmp_sql .= $cond['JOIN_COND'] . ' JOIN ' . $obj->table_name . " AS  " . $obj->model_name . " ON ";
    $cond_str = '';
    foreach($cond['CONDITIONS'] as $left_cond => $right_cond) {
      $cond_str .= $cond_str ? ' AND ' : '';
      $cond_str .= " ${left_cond} = ${right_cond} ";
    }
    $tmp_sql .= $cond_str;
    if($joins) $obj->processJoins($tmp_sql, $joins);
  }

  /**
   * join elongthTo()
   *
   * 従属モデルをjoinする
   *
   * @params Model $obj
   * @params array $cond
   * @params array $join
   * @params string $tmp_sql
   *
   * @return
   */
  public function joinHas($has, $cond, $joins, &$tmp_sql) {
    $tmp_sql .= ' ' . $cond['JOIN_COND'] . ' JOIN ' . $has->table_name . ' AS ' . $has->model_name .' ';
    $tmp_sql .= ' ON ' . $has->model_name . '.'
             . $cond['FOREIGN_KEY'] . '=' .$this->model_name . '.' . $this->primary_key . ' ';
    if($joins) $has->processJoins($tmp_sql, $joins);
  }

  /**
   * select()
   *
   * 抽出対象カラムの指定
   *
   * @params array $cilumns
   *
   * @return
   */
  public function select($columns = [])
  {
    $this->columns = $columns;
    return $this;
  }

  /**
   * addRelationshipSql()
   *
   * @params string relationship_sql
   * @params Model $model_name
   * @params array $relationship_conditions
   * @return
   */
  protected function addRelationshipSql($relationship_sql, $model_name, $relationship_conditions) {
    $model_class_name = $model_name;
    $obj = new $model_class_name($this->dbh);
    $table_name = $obj->table_name;
    $join_cond = isset($relationship_conditions['JOIN_COND']) ? $relationship_conditions['JOIN_COND'] : "INNER";

    $relationship_sql = "";
    foreach ($relationship_conditions['conditions'] as $key => $value) {
      $sql_tmp = " " . $key . " = " . $value;
      $relationship_sql .= $relationship_sql ? " AND " .$sql_tmp . " " : " " . $sql_tmp . " ";
    }
    $relationship_sql = " " . $join_cond . " JOIN " . $table_name . " as " . $model_name . " on " . $relationship_sql;

    $relationship_conds[] = $relationship_sql;
    $relationship_columuns[] = ", " . $model_name . ".*";
  }

  /**
   *  検索条件生成処理
   *
   *  @retrun BaseModel $this
   */
  private function createCondition(){
    $cond = null;
    for($i = 0; $i < count($this->conditions); $i++) {
      $cond_tmp = null;
      $condition = $this->conditions[$i];
      $column_name = str_replace('.', '_', $condition['column_name']);
      $column_name = \strangerfw\utils\StringUtil::convertTableNameToClassName($column_name);

      if (is_array($condition['value'])) {
        // $arr = implode(",", $condition['value']);
        $value = "";

        $col_arr = explode('.', $condition['column_name']);
        foreach ($condition['value'] as $v) {
          $val = $this->setValue($col_arr[count($col_arr) - 1], $v);
          $value .= $value ? "," . $val : $val;
        }
        $condition['value'] = null;
        $condition['value'] = $value;
      }
      $cond_tmp =  " " . $condition['column_name'];
      if ($condition['operator'] == 'IS NULL') {
        $cond_tmp .= " " . $condition['operator'] . " ";
      }
      else if ($condition['operator'] == 'IN') {
        $cond_tmp .= " " . " IN (".$condition['value'].") ";
      }
      else {
        $cond_tmp .= " " . $condition['operator'];
        $cond_tmp .= " :" . $column_name . " ";
      }
      $cond .= $cond ? " and " . $cond_tmp : $cond_tmp;
    }

    if($cond) $cond = " WHERE " . $cond;
    return $cond;
  }

  /**
   *  検索条件設定処理
   *
   *  @retrun BaseModel $this
   */
  public function where($column_name, $operator, $value) {
    $this->conditions[] = [
      'column_name' => $column_name,
      'operator' => $operator,
      'value' => $value,
    ];
    return $this;
  }

  /**
   *  検索件数設定処理
   *
   *  @retrun BaseModel $this
   */
  public function limit($limit_num) {
    if (!is_int($limit_num)) throw new \Exception("Error Processing Request", 1);
    $this->limit_num = $limit_num;
    return $this;
  }

  /**
   *  検索件数上限設定処理
   *
   *  @retrun BaseModel $this
   */
  public function setMaxRows($max_rows) {
    if (!is_int($max_rows)) throw new \Exception("Error Processing Request", 1);
    if ($max_rows > 0) $this->max_rows = $max_rows;
    return $this;
  }

  /**
   *  検索開始位置設定処理
   *
   *  @retrun BaseModel $this
   */
  public function offset($offset_num) {
    if (!is_int($offset_num)) throw new \Exception("Error Processing Request", 1);
    $this->offset_num = $offset_num;
    return $this;
  }

  /**
   *  検索対象頁設定処理
   *
   *  @retrun BaseModel $this
   */
  public function pagenate($page){
    if (!is_int($page)) throw new \Exception("Error Processing Request", 1);
    if ($page > 0 && $this->max_rows > 0) {
      $this->limit_num = $this->max_rows * $page;
      $this->offset_num = $this->max_rows * ($page - 1);
    }
    return $this;
  }

  /**
   *  検索並び順（昇順）設定処理
   *
   *  @param string $asc 対象カラム名
   *  @retrun BaseModel $this
   */
  public function asc($asc){
    $this->ascs[] = $this->ascs ? "," . $this->model_name . "." . $asc . " ASC ":  " " . $this->model_name . "." . $asc . " ASC ";
    return $this;
  }

  /**
   *  検索並び順（降順）設定処理
   *
   *  @param string $asc 対象カラム名
   *  @retrun BaseModel $this
   */
  public function desc($asc){
    $this->ascs[] = $this->ascs ? "," . $this->model_name . "." . $asc . " DESC ":  " " . $this->model_name . "." . $asc . " DESC ";
    return $this;
  }

  public function setHasModelDatas($model_name, $foreign_key_name,&$datas, $setDatas, $primary_keys) {
    foreach ($primary_keys as $primary_key) {
      foreach ($setDatas as $setData) {
        if ($setData[$model_name][$foreign_key_name] == $primary_key) {
          $datas[$setData[$model_name][$foreign_key_name]][$this->model_name][$model_name][][$model_name] = $setData[$model_name];
        }
      }
    }
  }

  //  新規登録・更新処理
  /**
   *  新規登録・更新処理
   *
   *  @param array $form  フォーム入力値
   */
  public function save($form) {
    try {
      $hssModels = [];
      $hasManyAndBelongsToModels = [];
      $now_date = date('Y-m-d H:i:s');

      $this->debug->log("BaseModel::save() form:" . print_r($form, true));
      $this->debug->log("BaseModel::save() " . $this->model_name . ":" . print_r($form[$this->model_name], true));
      $this->validation($form);
      if (
        isset($form[$this->model_name][$this->primary_key]) &&
        (
          $form[$this->model_name][$this->primary_key] != '' ||
          $form[$this->model_name][$this->primary_key] != null
        )
      ) {
          $this->debug->log("BaseModel::save() createModifySql CH-01");
          $sql = $this->createModifySql($form[$this->model_name]);  // CASE MODIFY
      }
      else {
        $this->debug->log("BaseModel::save() createModifySql CH-02");
        unset($form[$this->model_name][$this->primary_key]);
        $sql = $this->createInsertSql();  // CASE INSERT
      }
      $this->debug->log("BaseModel::save() SQL(1):". $sql);

      if($this->has){
        $hssModels = array_keys($this->has);
      }
      if ($this->has_many_and_belongs_to) {
        $hasManyAndBelongsToModels = array_keys($this->has_many_and_belongs_to);
      }
      $stmt = $this->dbh->prepare($sql);
      foreach ($form[$this->model_name] as $col_name => $value) {
        if ($hssModels && in_array($col_name, $hssModels)) {
          continue;
        }
        if ($hasManyAndBelongsToModels && in_array($col_name, $hasManyAndBelongsToModels)) {
          continue;
        }
        $colum_name = ":".$col_name;
        switch ($col_name) {
          case 'created_at':
          case 'modified_at':
            $now_date = $value ? $value : $now_date;
            $stmt->bindValue($col_name, $now_date, $this->getColumnType($col_name));
            break;
          default:
            $stmt->bindValue($col_name, $value, $this->getColumnType($col_name));
            break;
        }
      }

      $stmt->execute();
      $this->debug->log("BaseModel::save() SQL:". $stmt->getSQL);

      //  従属モデルへのセーブ処理
      $this->primary_key_value = $this->dbh->lastInsertId($this->primary_key);
      if (isset($form[$this->model_name])) {
        foreach ($form[$this->model_name] as $model_name => $value) {
          if ($hssModels && in_array($model_name, $hssModels)) {
            $array_keys = array_keys($value);
            if ($this->is_hash(array_keys($value))) {
              foreach ($value as $num => $val) {
                if ($hssModels && in_array($model_name, $hssModels)) {
                  $this->saveHasModel($model_name, $id, $val);
                }
                else if ($hasManyAndBelongsToModels && in_array($model_name, $hasManyAndBelongsToModels)) {
                  $this->saveHasModel($model_name, $id, $val);
                }
              }
            } else {
              $this->saveHasModel($model_name, $id, $value);
            }
          }
          else
            continue;
        }
      }
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage(), 1);
    }
  }

  public function createInsertSql() {
    $col_names = array_keys($this->column_conf);
    $this->debug->log("BaseModel::createInsertSql() col_names:" . print_r($col_names, true));
    $colums_str = null;
    $values_str = null;
    foreach ($col_names as $col_name) {
      $this->debug->log("BaseModel::createInsertSql() col_name:${col_name}");
      if ($col_name === $this->primary_key) continue;

      $colums_str .= $colums_str ? ", " . $col_name : $col_name;
      if ($col_name === 'created_at' || $col_name === 'modified_at')
        $values_str .= $values_str ? ", NOW()" : "NOW()";
      else
        $values_str .= $values_str ? ", :".$col_name : ":".$col_name;
    }

    $sql = "INSERT INTO " . $this->table_name . " (" . $colums_str .") VALUES (" . $values_str . ")";
    return $sql;
  }

  public function createModifySql($form) {
    // $col_names = array_keys($this->columns);
    $this->debug->log("BaseModel::createModifySql() form:" . print_r($form, true));
    $col_names = array_keys($form);
    $colums_str = null;
    $values_str = null;
    foreach ($col_names as $col_name) {
      if ($col_name == $this->primary_key) continue;
      $this->debug->log("BaseModel::createModifySql() colums_str_tmp:[${colums_str_tmp}] col_name[${col_name}]");
      $colums_str_tmp = $col_name . " = :" . $col_name;
      $colums_str .= $colums_str ? ", " . $colums_str_tmp : $colums_str_tmp;
    }
    return  "UPDATE " . $this->table_name . " SET " . $colums_str ." WHERE " . $this->primary_key . " = :" . $this->primary_key ."";
  }

  public function saveHasModel($model_name, $id, $form) {
    $model_class_name = $model_name."Model";
    $obj = new $model_class_name($this->dbh);
    $form[$this->has[$model_name]['foreign_key']] = $id;
    $f = [];
    $f[$model_name] = $form;
    $obj->save($f);
  }

  //  削除
  public function delete($id){
    //  隷属するモデルを先に検索・削除する。
    if (isset($this->has)) {
      foreach ($this->has as $model_name => $value) {
        $model_class_name = $model_name;
        $obj = new $model_class_name($this->dbh);
        $datas = $obj->where($value['foreign_key'] , '=', $id)->find();
        foreach ($datas as $key => $data) {
          $obj->delete($data[$model_name][$obj->primary_key]);
        }
      }
    }

    $sql = "DELETE FROM " . $this->table_name . " WHERE " . $this->primary_key . "=:" . $this->primary_key;
    $stmt = $this->dbh->prepare($sql);
    $stmt->bindValue($this->primary_key, $id, $this->getColumnType($this->primary_key));
    $stmt->execute();
  }

  //  共通
  protected function setValue($key, $value){
    $type = $this->column_conf[$key]['type'];
    if (is_array($value)) {
      if ($type == 'SET') {
        $val_tmp = '';
        foreach ($value as $key => $val) {
          $val = mysqli_escape_string($val);
          $val .= htmlspecialchars($val, ENT_QUOTES);
          $val_tmp .= $val_tmp ? $val_tmp : ", " . $val_tmp;
        }
        $value = $val_tmp;
      }
    }

    $value = $value == 'string' ? 'varchar' : $value;

    switch ($type) {
      case 'int':
      case 'tinyint':
      case 'smallint':
      case 'bigint':
      case 'float':
      case 'double':
        return $value;
      case 'set':
        return "'" . $value . "'";
      default:
        return "'".$value."'";
        break;
    }
  }

  public function getColumnType($col_name) {
    $type = $this->column_conf[$col_name]['type'];
    switch ($type) {
      case 'int':
      case 'tinyint':
      case 'smallint':
      case 'bigint':
        return \PDO::PARAM_INT;
        break;
      case 'float':
      case 'double':
        return \PDO::PARAM_INT;
        break;
      case 'bool':
        return \PDO::PARAM_BOOL;
        break;
      case 'set':
      default:
        return \PDO::PARAM_STR;
        break;
    }
  }

  public function getColumns() {
    return array_keys($this->column_conf);
  }

  public function validation($form) {
    // $this->form
  }

  /**
   *  Array/Hash判定メソッド
   *
   *  @param Object $data 判定対象オブジェクト
   *  @return boolean
   */
  public function is_hash($data) {
    if (is_array($data)){
      foreach ($data as $value) {
        if (!is_numeric($value)) continue;
        else return true;
      }
      return false;
    }
    return false;
  }

  public function createForm() {
    $keys = array_keys($this->column_conf);
    $form = [];
    foreach ($keys as $value) {
      $form[$this->model_name][$value] = '';
    }
    return $form;
  }

  /**
   * Set Join modelse
   */
  public function contain($joins = [])
  {
    $this->debug->log("BaseModel::contain() joins:" . print_r($joins, true));
    $this->joins = $joins;
    return $this;
  }
}
