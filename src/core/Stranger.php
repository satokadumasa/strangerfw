<?php
namespace strangerfw\core;

class Stranger {
  protected $error_log = null;
  protected $info_log = null;
  protected $debug = null;
  protected $argv = [];
  protected $table_name = null;
  protected $class_name = null;
  protected $default_database = null;
  protected $dbh = null;

  public function __construct($argv)
  {
    echo "create instance\n";
    $this->argv = $argv;
    $this->error_log = new strangerfw\utils\Logger('ERROR');
    $this->info_log = new strangerfw\utils\Logger('INFO');
    $this->debug = new strangerfw\utils\Logger('DEBUG');
  }

  public function con($conf)
  {
    try {
      echo "  try connect\n";
      $database = $conf['default_database'];
      $this->debug->log("Stranger::execMigration() database:".print_r($database, true));
      $this->default_database = $database;
      $this->dbConnect = new strangerfw\utils\DbConnect();
      $this->dbConnect->setConnectionInfo($database);
      $this->dbh = $this->dbConnect->createConnection();
      echo "  connected\n";
      echo "  Stranger::con() end\n";
      $this->debug->log("Stranger::execMigration() database:".print_r($this->dbh, true));
    } catch (PDOException $e) {
      echo "  can not connect to database\n";
      echo "  >>>>".$e->getMessage()."\n";
    }
  }

  /**
   *  stranger command execute method
   *
   */
  public function execute() {
    /**
    argv = array(
        [0] => ./stranger.php
        [1] => -g
        [2] => scaffold
        [3] => UserInfos
        [4] => name:string
        [5] => address:string
    )
    */
    $this->table_name = isset($this->argv[3]) ? $this->argv[3] : null;
    $this->class_name = strangerfw\utils\StringUtil::convertTableNameToClassName($this->table_name);
    echo "run stranger\n";

    switch ($this->argv[1]) {
      case '-g':
        echo "  run generate\n";
        $this->executeGenerates();
        break;
      case 'migrate:create:schema':
        echo "  exec migrate:create:schema\n";
        $this->createSchema($this->argv[2]);
        exit();
        break;
      case 'migrate:init':
        echo "  exec migrate:init\n";
        $conf = strangerfw\core\Config::get('database.config');
        $this->con($conf);
        $this->initSchema();
        exit();
        break;
      case 'migrate':
        echo "  exec migrate\n";
        $conf = strangerfw\core\Config::get('database.config');
        $this->con($conf);
        $this->execMigration();
        exit();
        break;
      case 'db:migrate':
        echo "  exec db:migrate\n";
        $conf = strangerfw\core\Config::get('database.config');
        $this->con($conf);
        $arr = explode(':', $this->argv[1]);
        exit();
        break;
      case '-d':
        echo "  run destroy\n";
        $this->executeDestroies();
        exit();
        break;
      default:
        echo "  Input Option.\n";
        break;
    }
  }

  /**
   *  データベース作成
   *
   *  @param string $connection_param 接続情報
   *  @return
   */
  public function createSchema($connection_param)
  {
    try {
      // migrate:create:schena host:charset:username:password:schema
      echo "  createSchema\n";
      $arr = explode(':', $connection_param);
      $database = [
        'rdb'      => 'mysql',
        'host'     => $arr[0],
        'dbname'   => 'mysql',
        'charset'  => $arr[1],
        'username' => $arr[2],
        'password' => $arr[3],
      ];
      $dbConnect = new strangerfw\utils\DbConnect();
      $dbConnect->setConnectionInfo($database);
      $dbh = $dbConnect->createConnection();
      echo "  connected\n";
      $schena = $arr[4];
      $sql = <<<EOM
CREATE DATABASE $schena;
EOM;
      echo "  SQL:$sql\n";
      $stmt = $dbh->prepare($sql);
      $stmt->execute();
      echo "  createSchema end\n";
    } catch (Exception $e) {
      echo "Error:".$e->getMessage()."\n";
    }
  }

  /**
   *  migrationテーブル作成
   *
   *  @param none
   *  @return
   */
  public function initSchema()
  {
    echo "  create migrations\n";
/*    try {
      $sql = <<<EOM
DROP TABLE migrations;
EOM;
      $stmt = $this->dbh->prepare($sql);
      $stmt->execute();

    } catch (PDOException $e) {
      echo "can not drop maigrations table.\n";
    }
*/
    $sql = <<<EOM
CREATE TABLE migrations (
  version BIGINT,
  name varchar(255) NOT NULL,
  created_at datetime NOT NULL,
  modified_at datetime NOT NULL,
  PRIMARY KEY (`version`),
  KEY index_users_id (`version`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
EOM;
    $stmt = $this->dbh->prepare($sql);
    $stmt->execute();
    echo "  create migrations success\n";
  }

  /**
   *  テンプレート、モデル、コントローラー生成
   */
  public function executeGenerates(){
      echo "  run executeGenerates\n";
    if ($this->argv[1] !== '-g') {
      echo "    Please specify the correct parameter.\n";
      return;
    }
    switch ($this->argv[2]) {
      case 'scaffold':
        echo "    run create scaffold\n";
        $this->scaffoldGenerate();
        break;
      case 'controller':
        echo "    run create controller\n";
        $this->controllerGenerate();
        break;
      case 'model':
        echo "    run create model\n";
        $this->modelGenerate();
        break;
      case 'column':
        echo "    run add column migrations\n";
        $this->maigrateGenerate();
        break;

      default:
        echo "    Please specify the correct parameter.\n";
        break;
    }
  }

  /**
   *  テンプレート、モデル、コントローラー削除
   */
  public function executeDestroies(){
    if ($this->argv[1] !== '-d') {
      echo "    Please specify the correct parameter.\n";
      return;
    }
    switch ($this->argv[2]) {
      case 'scaffold':
        echo "    run destroy scaffold\n";
        $this->scaffoldDestroy();
        break;
      case 'controller':
        echo "    run destroy controller\n";
        $this->controllerDestroy();
        break;
      case 'model':
        echo "    run destroy model\n";
        $this->modelDestroy();
        break;
      case 'column':
        echo "    run destroy model/column\n";
        $this->maigrateGenerate();
        break;
      default:
        echo "    Please specify the correct parameter.\n";
        break;
    }
  }

  //  generate scaffold
  /**
   *  キャッフォルドファイル一括生成メソッド
   */
  public function scaffoldGenerate(){
    $this->controllerGenerate();
    $this->modelGenerate();
    $this->maigrateGenerate();
    $this->templateGenerate();
  }

  //  destroy scaffold
  /**
   *  キャッフォルドファイル一括削除メソッド
   */
  public function scaffoldDestroy(){
    $this->controllerDestroy();
    $this->modelDestroy();
    $this->maigrateDestroy();
    $this->templateDestroy();
    $this->schemaFileDestroy();
  }

  public function execMigration(){
    echo "  execMigration Start\n";
    try{
      $this->debug->log("Stranger::execMigration() Start\n");
      $migration_files = $this->getFileList(MIGRATION_PATH);
      $this->debug->log("Stranger::execMigration() dbh:".print_r($this->dbh, true));
      $this->debug->log("Stranger::execMigration() CH-01\n");
      $migrate = new strangerfw\core\model\Migration($this->dbh);
      $this->debug->log("Stranger::execMigration() CH-02\n");
      $versions = [];
      if(isset($this->argv[2])) $versions = explode('=', $this->argv[2]);
      $this->debug->log("Stranger::execMigration() CH-03\n");
      if (isset($this->argv[2]) && $versions[0] == 'version') {
        echo "    Migrate drop table and add column.\n";
        $this->debug->log("Stranger::execMigration() Migrate drop table and add column");

        $migrate_classes = $migrate->where('version', '>', $versions[1])->desc('version')->find('all');

        foreach ($migrate_classes as $key => $migrate_class) {
          $migration_file = $migrate_class['Migration']['name'];
          echo "      ========== Migrate ".$migration_file." down start ==========\n";
          require_once MIGRATION_PATH . $migration_file . ".php";
          $migration = new $migration_file($this->default_database);
          $migration->down();
          $migrate->delete($migrate_class['Migration']['version']);
          echo "      ========== Migrate ".$migration_file." down end   ==========\n";
        }
      }
      else {
        echo "    Migrate create table and add column.\n";
        $this->debug->log("Stranger::execMigration() Migrate create table and add column");
        $max_version = $migrate->getMaxVersion();
        $this->debug->log("Stranger::execMigration() max_version[${max_version}]");


        foreach ($migration_files as $key => $value) {
          $this->debug->log("Stranger::execMigration() key[${key}] value[${value}]");
          if (!strpos($value, '.php')) continue;

          $arr = explode('/', $value);
          $migration_file = $arr[count($arr) - 1];
          $migration_file = str_replace('.php', '', $migration_file);

          //  バージョン取得
          $varsion = preg_replace('/[^0-9]/', '', $migration_file);

          if ($varsion <= $max_version) continue;
          echo "      ========== Migrate ".$migration_file." up start ==========\n";
          require_once $value;
          $migration = new $migration_file($this->default_database);
          $migration->up();
          $migrate->insert(['Migration' => ['version' => $varsion, 'name' => $migration_file]]);
          echo "      ========== Migrate ".$migration_file." up end   ==========\n";
        }
      }
    } catch (Exception $e) {
      $this->debug->log("Stranger::execMigration() error:".$e->getMessage());
      echo "Stranger::execMigration() error\n";
      echo "  >>>>".$e->getMessage()."\n";
    }
  }

  public function getFileList($dir) {
    $files = scandir($dir);
    $files = array_filter($files, function ($file) {
      return !in_array($file, ['.', '..']);
    });

    $list = [];
    foreach ($files as $file) {
      $fullpath = rtrim($dir, '/') . '/' . $file;
      if (is_file($fullpath)) {
        $list[] = $fullpath;
      }
      if (is_dir($fullpath)) {
        $list = array_merge($list, $this->getFileList($fullpath));
      }
    }

    return $list;
  }

  //  generate controller
  /**
   *  コントローラー生成メソッド
   */
  private function controllerGenerate(){
    $template_fileatime = SCAFFOLD_TEMPLATE_PATH . 'controllers/controller_template.tpl';
    //  出力先ファイルを開く
    $out_put_filename =  CONTROLLER_PATH . $this->class_name . "Controller.php";
    echo "  create ".$out_put_filename."\n";
    $fp = fopen($out_put_filename, "w");
    $return = $this->applyTemplate($template_fileatime, $fp, $this->class_name, null, null);
  }

  //  destroy controller
  /**
   *  コントローラー削除メソッド
   */
  private function controllerDestroy(){
    $out_put_filename =  CONTROLLER_PATH . $this->class_name . "Controller.php";
    echo "  rm ".$out_put_filename."\n";
    unlink($out_put_filename);
  }

  //  generate model
  /**
   *  モデル生成メソッド
   */
  private function modelGenerate(){
    //  テンプレートファイル名作成
    $template_fileatime = SCAFFOLD_TEMPLATE_PATH . 'models/model_template.tpl';
    //  出力先ファイルを開く
    $out_put_filename =  MODEL_PATH . $this->class_name . '.php';
    echo "  create ".$template_fileatime."\n";
    $fp = fopen($out_put_filename, 'w');
    echo "  create ".$out_put_filename."\n";
    $return = $this->applyTemplate($template_fileatime, $fp, $this->class_name, null);
    fclose($fp);
    if ($return === false) {
      return false;
    }
    $this->generateTableConfig();
  }

  //  destroy model
  /**
   *  モデル削除メソッド
   */
  private function modelDestroy(){
    $out_put_filename =  MODEL_PATH . $this->class_name . '.php';
    echo "  rm ".$out_put_filename."\n";
    unlink($out_put_filename);
  }

  //  generate template
  /**
   *  Viewテンプレート生成メソッド
   */
  private function templateGenerate(){
    $templates = [
       'index'
      ,'detail'
      ,'create'
      ,'edit'
      ,'save'
    ];
    try{
      echo "create view templates.\n";
      $view_template_folder = VIEW_TEMPLATE_PATH . $this->class_name . '/';
      echo "  mkdir ".$view_template_folder."\n";
      if (!file_exists($view_template_folder)) {
        if(!mkdir($view_template_folder)) return false;
      }
      foreach ($templates as $key => $method) {
        $this->createViewTemplate(
          SCAFFOLD_TEMPLATE_PATH . "/views/${method}.tpl", 
          $view_template_folder . $method.'.tpl', 
          $method
        );
      }
    } catch (Exception $e) {
      echo "    ".$e->getMessage()."\n";
      $this->debug->log("Stranger::templateGenerate() :".$e->getMessage());
    }
  }

  private function createViewTemplate($template_fileatime, $view_template, $method) {
    echo "create ${method} template.\n";
    $fp = fopen($view_template, "w");
    echo "  create ".$view_template."\n";
    $return = $this->applyTemplate($template_fileatime, $fp, $this->class_name, null, $method);
    fclose($fp);
    if ($return === false) {
      throw new Exception("Can not create ${$method} view template.", 1);
    }
  }

  //  destroy template
  /**
   *  Viewテンプレート削除メソッド
   */
  private function templateDestroy(){
    $file_list = $this->getFileList(VIEW_TEMPLATE_PATH . $this->class_name . '/');
    foreach ($file_list as $key => $value) {
      echo "  rm ".$value."\n";
      unlink($value);
    }
    rmdir(VIEW_TEMPLATE_PATH . $this->class_name);
  }

  //  generate maigrate_file
  /**
   *  マイグレーションファイル生成メソッド
   */
  private function maigrateGenerate(){
    //  テンプレートファイル名作成
    $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/migration_template.tpl';
    $now_date = date('YmdHis');
    $create = null;
    if ($this->argv[2] == 'scaffold' || $this->argv[2] == 'model') {
      $create = $this->argv[1] == '-g' ? 'CreateTable' : 'DropTable';
    } else if ($this->argv[2] == 'column') {
      $create = $this->argv[1] == '-g' ? 'AddColumn' : 'DropColumn';
    }
    $migration_class_name = 'Migrate' . $now_date . $create . $this->class_name;
    $out_put_filename = MIGRATION_PATH . $migration_class_name . '.php';
    //  出力先ファイルを開く
    $fp = fopen($out_put_filename, 'w');
    echo "  create ".$out_put_filename."\n";
    $return = $this->applyTemplate($template_fileatime, $fp, $this->class_name, $migration_class_name);
    fclose($fp);
    if ($return === false) {
      return false;
    }
  }

  private function schemaFileDestroy()
  {
    $file_list = $this->getFileList(SCHEMA_PATH);
    $schema_file_name = $this->table_name . '.yaml';
    foreach ($file_list as $key => $value) {
      if (strpos($value, $schema_file_name)) {
        unlink($value);
        break;
      }
    }
  }

  // geterate generateTableConfig()
  /**
   * Generate or Update Schema File.
   * generateTableConfig($table_name)
   *
   * @param String table_name
   * @return
   */
  private function generateTableConfig()
  {
    try{

      echo "    exec generateTableConfig()\n";
      echo "      Schema File:".SCHEMA_PATH.$this->argv[3].".yaml\n";
      $table_config = [];
      if(file_exists(SCHEMA_PATH.$this->argv[3].".yaml")){
        $table_config = Spyc::YAMLLoad(SCHEMA_PATH.$this->argv[3].".yaml");
      }
      else {
        $table_config[$this->argv[3]]['id'] = [
            'type'    => 'int'
          , 'length'  => 10
          , 'null'    => false
          , 'key'     => 'PRI'
          , 'default' => null
        ];
      }

      for ($j = 4; $j < count($this->argv); $j++) {
        $column_conf = explode(':', $this->argv[$j]);

        $table_config[$this->argv[3]][$column_conf[0]] = [
            'type'    => $column_conf[1]
          , 'length'  => intval($column_conf[2])
          , 'null'    => $column_conf[3] == 'true' ? true : false
          , 'key'     => $column_conf[4] ? 'PRI' : ''
          , 'default' => $column_conf[5] ? $this->argv[$j][5] : ''
        ];
        // echo "      table_config(3):".print_r($table_config, true)."\n";
      }
      if(!array_key_exists('created_at', $table_config[$this->argv[3]])){
        $table_config[$this->argv[3]]['created_at'] = [
            'type'    => 'datetime'
          , 'length'  => 19
          , 'null'    => 'false'
          , 'key'     => ''
          , 'default' => null
        ];
        $table_config[$this->argv[3]]['modified_at'] = [
            'type'    => 'datetime'
          , 'length'  => 19
          , 'null'    => 'false'
          , 'key'     => ''
          , 'default' => null
        ];
      }
      $fp .= Spyc::YAMLDump($table_config,true , true, true);
      file_put_contents(SCHEMA_PATH.$this->argv[3].".yaml", $fp);
    } catch (Exception $e) {
      echo "  Can not create schema file\n";
      echo "  >>>>".$e->getMessage()."\n";
    }
  }

  ///////////////////////////////
  /**
   *
   *
   *  column_define :
   */
  protected function addColumnArrayToTable($column_define, $type){
    $columns_str = "";
    $arr = explode(':', $column_define);
    $value = null;
    $datas = [
        'column_name' => $arr[0],
        'type' => $arr[1],
        'type_str' => $this->convertTypeKeyString($arr[1], (isset($arr[2]) ? $arr[2] : 255), $value),
        'length' => isset($arr[2]) ? $arr[2] : 255,
        'null' => (isset($arr[3]) && $arr[3] != '') ? 'false' : 'true',
        'null_ok' => (isset($arr[3]) && $arr[3] != '')? 'NOT NULL' : '',
        'key' => isset($arr[4]) ? $arr[4] : '',
        'default' => (isset($arr[5]) && ($arr[5] != ''  || $arr[5] != null))? $arr[5] : 'null',
        'model_name' => $this->class_name,
      ];

    switch ($type) {
      case 'model':
        $template_fileatime = SCAFFOLD_TEMPLATE_PATH."/models/parts/column.tpl";
        break;
      case 'sql':
        $template_fileatime = SCAFFOLD_TEMPLATE_PATH."/migrate/parts/column.tpl";
        break;
      case 'add_col':
        $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/parts/add_column.tpl';
        break;
      case 'drop_col':
        $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/parts/drop_column.tpl';
        break;
      case 'view':
        $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/views/parts/column.tpl';
        break;
      case 'form':
        $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/views/parts/form/column.tpl';
        break;
      default:
        break;
    }

    $file_context = file($template_fileatime);
    for($i = 0; $i < count($file_context); $i++) {
      $value = $file_context[$i];
      preg_match_all(
        '|<!----(.*)---->|U',
        $value,
        $matchs
      );
      if (count($matchs[1]) > 0){
        $columns_str .= $this->convertKeyToValue($value, $matchs[1], $datas);
      }
      else {
        $columns_str .= $value ;
      }
    }

    return $columns_str;
  }
  ///////////////////////////////

  //  destroy maigrate_file
  /**
   *  マイグレーションファイル削除メソッド
   */
  public function maigrateDestroy(){
    $file_list = $this->getFileList(MIGRATION_PATH);
    foreach ($file_list as $key => $value) {
      if (strpos($value, $this->class_name)) {
        unlink($value);
        break;
      }
    }
  }

  /**
   *  テンプレート適用メソッド
   *
   *  template_fileatime : テンプレートファイル名
   *  fp : 出力先ファイルポインター
   *  class_name : クラス名
   *  migration_class_name : マイグレーションファイル　クラス名
   *  method_name : コントローラメソッド名
   */
  public function applyTemplate($template_fileatime, &$fp, $class_name, $migration_class_name = null, $method_name = null) {
    //  テンプレートファイル読み込み
    $file_context = file($template_fileatime);
    for($i = 0; $i < count($file_context); $i++) {
      $value = $file_context[$i];
      //  展開先の取得
      preg_match_all(
        '<!----(.*)---->',
        $value,
        $matchs
      );

      if (count($matchs[1]) > 0) {
        if (strpos($value, '<!----migration_class_name---->')) {
          $value = str_replace('<!----migration_class_name---->', $migration_class_name, $value);
        }
        if (strpos($value, '<!----class_name---->')) {
          $value = str_replace('<!----class_name---->', $this->class_name, $value);
        }
        if (strpos($value, '<!----action_name---->')) {
          $value = str_replace('<!----action_name---->', $method_name, $value);
        }
        if (strpos($value, '<!----table_name---->')) {
          $value = str_replace('<!----table_name---->', $this->table_name, $value);
        }
        if (strpos($value, '<!----pk_name---->')) {
          $value = str_replace('<!----pk_name---->', 'id', $value);
        }
        if (strpos($value, '<!----method_name---->')) {
          $value = str_replace('<!----method_name---->', $method_name, $value);
        }
        if (strpos($value, '<!----form_columns---->')) {
          $columns_str = "";
          for ($j = 4; $j < count($this->argv); $j++) {
            $columns_str .= $this->generateColumnsStr($this->argv[$j], 'form');
          }
          $value = $columns_str;
        }
        if (strpos($value, '<!----columns---->')) {
          $columns_str = "";
          for ($j = 4; $j < count($this->argv); $j++) {
            $columns_str .= $this->generateColumnsStr($this->argv[$j], 'sql');
          }
          $value = $columns_str;
        }
        if (strpos($value, '<!----model_columns---->')){
          $columns_str = "";
          for ($j = 4; $j < count($this->argv); $j++) {
            $columns_str .= $this->generateColumnsStr($this->argv[$j], 'model');
          }
          $value = $columns_str;
        }

        if (strpos($value, '<!----up_template---->')) {
          if ($this->argv[2] == 'scaffold' || $this->argv[2] == 'model') {
            $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/parts/create_table.tpl';
            $this->applyTemplate($template_fileatime, $fp, $this->class_name);
          }
          else if ($this->argv[2] == 'column'){
            $type = $this->argv == '-g' ? 'add_col' : 'drop_col';
            $columns_str = null;
            for ($j = 4; $j < count($this->argv); $j++) {
              $columns_str .= $this->generateColumnsStr($this->argv[$j], $type);
            }
            $fwrite = fwrite($fp, $columns_str);
            if ($fwrite === false) {
              return false;
            }
          }
          continue;
        }
        if (strpos($value, '<!----down_template---->')) {
          if ($this->argv[2] == 'scaffold' || $this->argv[2] == 'model') {
            $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/parts/drop_table.tpl';
            $this->applyTemplate($template_fileatime, $fp, $class_name);
          }
          else if ($this->argv[2] == 'column'){
            $type = $this->argv == '-g' ? 'drop_col' : 'add_col';
            $columns_str = null;
            for ($j = 4; $j < count($this->argv); $j++) {
              $columns_str .= $this->generateColumnsStr($this->argv[$j], $type);
            }
            $fwrite = fwrite($fp, $columns_str);
            if ($fwrite === false) {
              return false;
            }
          }
          continue;
        }
        if (strpos($value, '<!----controller_method---->')) {
          if ($this->argv[2] == 'scaffold') {
            echo "create scaffold controller.\n";
            $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/controllers/parts/scaffold_controller_methods_template.tpl';
            $this->applyTemplate($template_fileatime, $fp, $class_name, null, null);
          }
          else {
            echo "create non-scaffold controller.\n";
            for ($j = 4; $j < count($this->argv); $j++) {
              $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/controllers/parts/method_template.tpl';
              $this->applyTemplate($template_fileatime, $fp, $class_name, null, $this->argv[$j]);
            }
          }
          continue;
        }
        if (strpos($value, '<!----detail_columns---->')) {
          $value = $this->geterateColumnString($this->argv);
          $fwrite = fwrite($fp, $value);
          if ($fwrite === false) {
            return false;
          }
          continue;
        }
        if (strpos($value, '<!----details---->')) {
          //  詳細画面テンプレート挿入
          echo "insert detail template.\n";
          $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/views/detail.tpl';
          $this->applyTemplate($template_fileatime, $fp, $class_name, null, null);
          $fwrite = fwrite($fp, $value);
          if ($fwrite === false) {
            return false;
          }
          continue;
        }
        if (strpos($value, '<!----columun_name---->')) {
          echo "covert [columun_name].\n";
          for ($j = 4; $j < count($this->argv); $j++) {
            $columns_str .= $this->convertKeyToValue($value, $matchs[0], $this->argv);
          }
          $value = $columns_str;
          $fwrite = fwrite($fp, $value);
          if ($fwrite === false) {
            return false;
          }
          continue;
        }
      }
      $fwrite = fwrite($fp, $value);
      if ($fwrite === false) {
        return false;
      }
    }
  }

  /**
   *  キー置換メソッド
   *
   *  context   : 置換対象文字列
   *  matchs    : 置換対象キー文字列
   *  datas     : 置き換えデータ
   */
  protected function convertKeyToValue($context, $matchs, $datas){
    foreach ($matchs as $match) {
      $search = '<!----'.$match.'---->';
      if ($match == 'table_name') {
        $context = str_replace($search, $this->table_name, $context);
      } else {
        $context = str_replace($search, $datas[$match], $context);
      }
    }
    return $context;
  }

  /**
   *
   *
   *  column_define :
   */
  protected function generateColumnsStr($column_define, $type){
    $columns_str = "";
    $arr = explode(':', $column_define);
    $value = null;
    $datas = [
        'column_name' => $arr[0],
        'type' => $arr[1],
        'type_str' => $this->convertTypeKeyString($arr[1], (isset($arr[2]) ? $arr[2] : 255), $value),
        'length' => isset($arr[2]) ? $arr[2] : 255,
        'null' => (isset($arr[3]) && $arr[3] != '') ? 'false' : 'true',
        'null_ok' => (isset($arr[3]) && $arr[3] != '')? 'NOT NULL' : '',
        'key' => isset($arr[4]) ? $arr[4] : '',
        'default' => (isset($arr[5]) && ($arr[5] != ''  || $arr[5] != null))? $arr[5] : 'null',
        'model_name' => $this->class_name,
      ];

    if ($type == 'model') {
      $template_fileatime = SCAFFOLD_TEMPLATE_PATH."/models/parts/column.tpl";
    }
    else if ($type == 'sql'){
      $template_fileatime = SCAFFOLD_TEMPLATE_PATH."/migrate/parts/column.tpl";
    }
    else if ($type == 'add_col') {
      $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/parts/add_column.tpl';
    }
    else if ($type == 'drop_col') {
      $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/migrate/parts/drop_column.tpl';
    }
    else if ($type == 'view') {
      $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/views/parts/column.tpl';
    }
    else if ($type == 'form') {
      $template_fileatime = SCAFFOLD_TEMPLATE_PATH . '/views/parts/form/column.tpl';
    }
    $file_context = file($template_fileatime);
    for($i = 0; $i < count($file_context); $i++) {
      $value = $file_context[$i];
      preg_match_all(
        '|<!----(.*)---->|U',
        $value,
        $matchs
      );
      if (count($matchs[1]) > 0){
        $columns_str .= $this->convertKeyToValue($value, $matchs[1], $datas);
      }
      else {
        $columns_str .= $value ;
      }
    }

    return $columns_str;
  }

  /**
   *
   *
   * @param $argv
   */
  protected function geterateColumnString($argv) {
    $this->debug->log('Stranger::geterateColumnString() start:');
    $column_string = null;
    for ($i = 4; $i < count($argv); $i++) {
      $arr = explode(':', $argv[$i]);

      $datas = [
          'column_name' => $arr[0],
          'type' => $arr[1],
          'length' => isset($arr[2]) ? $arr[2] : 255,
          'null' => isset($arr[3]) ? $arr[3] : 'false',
          'null_ok' => isset($arr[3]) ? '' : 'NOT NULL',
          'key' => isset($arr[4]) ? $arr[4] : '',
          'default' => isset($arr[5]) ? $arr[5] : 'null',
        ];
      $column_string .= "  <div class='detail_rows'>\n";
      $column_string .= "    <div class='label_clumn'>\n";
      $column_string .= "      " . $this->class_name . " " . $datas['column_name'] . "\n";
      $column_string .= "    </div>\n";
      $column_string .= "    <div class='input_clumn'>\n";
      $column_string .= "      <!----value:" . $this->class_name . ":" . $datas['column_name'] . "---->\n";
      $column_string .= "    </div>\n";
      $column_string .= "  </div>\n";

    }
    $this->debug->log('Stranger::geterateColumnString() end:');
    return $column_string;
  }

  protected function convertTypeKeyString($type, $length, $value) {
    switch ($type) {
      case 'int':
        return $type."(8)";
      case 'tinyint':
        return $type."(1)";
      case 'smallint':
        return $type."(3)";
      case 'bigint':
      case 'float':
      case 'double':
        return $type."($length)";
        break;
      case 'date':
      case 'datetime':
      case 'timestamp':
      case 'time':
      case 'year':
        return $type;
      case 'set':
      case 'enum':
        return $type . "(" . $length . ")";
      case 'string':
        return  "varchar(" . $length . ")";
      case 'text':
        return $type;
      default:
        return $type . "(" . $length . ")";
    }
  }
}
