<?php
namespace strangerfw\core;

class ClassLoader {
  public static function loadClass($class){
    try{

      $debug = new \strangerfw\utils\Logger('DEBUG');
      $debug->log("ClassLoader::loadClass() search class is ${class}");
      $scan_dir_list = [
            CONTROLLER_PATH,
            MODEL_PATH,
            LIB_PATH,
            MIGRATION_PATH,
            HELPER_PATH,
            // SERVICE_PATH,
          ];
      $loaded_calasses = get_declared_classes();
      $debug->log("ClassLoader::loadClass() loaded_calasses:".print_r($loaded_calasses, true));
      $debug->log("ClassLoader::loadClass() scan_dir_list:".print_r($scan_dir_list, true));
      $debug->log("ClassLoader::loadClass() class(2)[".$class."]");
      $class = str_replace("\\", "/", $class);
      $class = str_replace("//", "/", $class);
      if(self::checkStrangerfw($class)) return true;
      $debug->log("ClassLoader::loadClass() class(5)[".$class."]");
      $debug->log("ClassLoader::loadClass() CH-02");
      foreach ($scan_dir_list as $scan_dir) {
        // $debug->log("ClassLoader::loadClass() CH-03");
        $debug->log("ClassLoader::loadClass() scan_dir:${scan_dir} file_name:${file_name} class:${class} ");
        $debug->log("ClassLoader::loadClass() scan_dir:".$scan_dir);
        $file_name = '';
        foreach (self::getDirList($scan_dir) as $directory) {
          $debug->log("ClassLoader::loadClass() directory[${directory}] class(1)[${class}]");
          $debug->log("ClassLoader::loadClass() CH-04");
          $file_name = $directory.$class.'.php';
          $debug->log('ClassLoader::loadClass() file_name(1)['.$file_name.']');

          if (is_file($file_name)) {
            $debug->log("ClassLoader::loadClass() file_name(2)[${file_name}]");
            require_once $file_name;
            $debug->log("ClassLoader::loadClass() file_name(3)[${file_name}] required.");
            return true;
          }
        }
      }
    } catch (\Exception $e) {
      $debug->log("ClassLoader::loadClass() Error:".$e->getMessage());
    }
  }

  public static function checkStrangerfw($class) {
    if(preg_match('/strangerfw/', '', $class, $matches)){
      $debug->log("ClassLoader::checkStranger() class(3)[${class}]");
      $class = str_replace('/strangerfw', '', $class);
      $debug->log("ClassLoader::checkStranger() class(4)[${class}]");
      $file_name = LIB_PATH.$class.'.php';
      $debug->log("ClassLoader::checkStranger() file_name(1)[${file_name}]");
      if(file_exists($file_name)){
        $debug->log("ClassLoader::checkStranger() file_name(2)[${file_name}]");
        require_once $file_name;
      }
      return true;
    }
    return false;
  }

  private static function getDirList($dir) {
    $debug = new \strangerfw\utils\Logger('DEBUG');
    $debug->log("ClassLoader::getDirList() CH-01");
    $files = scandir($dir);
    $debug->log("ClassLoader::getDirList() files:".print_r($files, true));
    $files = array_filter($files, function ($file) {
      return !in_array($file, ['.', '..', '.gitkeep']);
    });
    $debug->log("ClassLoader::getDirList() CH-04");

    $list = [];
    $list[] = $dir;
    foreach ($files as $file) {
      $fullpath = rtrim($dir, '/') . '/' . $file;
      if (is_dir($fullpath)) {
        $list[] = $fullpath;
        $list = array_merge($list, self::getDirList($fullpath));
      }
    }
    $debug->log("ClassLoader::getDirList() CH-05");

    return $list;
  }
}
