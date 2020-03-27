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
      $class = str_replace("\\", "/", $class);
      $class = str_replace("//", "/", $class);
      if(self::checkStrangerfw($class)) return true;
      foreach ($scan_dir_list as $scan_dir) {
        $file_name = '';
        foreach (self::getDirList($scan_dir) as $directory) {
          $file_name = $directory.$class.'.php';

          if (file_exists($file_name)) {
            $debug->log("ClassLoader::loadClass() Requires :${class}");
            require_once $file_name;
            return true;
          }
        }
      }
    } catch (\Exception $e) {
      $debug->log("ClassLoader::loadClass() Error:".$e->getMessage());
    }
  }

  public static function checkStrangerfw($class_name) {
    $debug = new \strangerfw\utils\Logger('DEBUG');
    if(preg_match('/strangerfw/', $class_name, $matches)){
      $class_name = str_replace('strangerfw', '', $class_name);
      $file_name = LIB_PATH.$class_name.'.php';
      if(file_exists($file_name)){
        require_once $file_name;
        return true;
      }
      return false;
    }
    return false;
  }

  private static function getDirList($dir) {
    $debug = new \strangerfw\utils\Logger('DEBUG');
    $files = scandir($dir);
    // $debug->log("ClassLoader::getDirList() files:".print_r($files, true));
    $files = array_filter($files, function ($file) {
      return !in_array($file, ['.', '..', '.gitkeep']);
    });

    $list = [];
    $list[] = $dir;
    foreach ($files as $file) {
      $fullpath = rtrim($dir, '/') . '/' . $file;
      if (is_dir($fullpath)) {
        $list[] = $fullpath;
        $list = array_merge($list, self::getDirList($fullpath));
      }
    }

    return $list;
  }
}
