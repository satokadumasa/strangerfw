<?php
namespace strangerfw\core;

class ClassLoader {
  public static function loadClass($class){
    $scan_dir_list = array(
      CONTROLLER_PATH,
      MODEL_PATH,
      // LIB_PATH,
      MIGRATION_PATH,
      HELPER_PATH,
      // SERVICE_PATH,
    );
    $class = str_replace("\\", "/", $class);
    foreach ($scan_dir_list as $scan_dir) {
      foreach (self::getDirList($scan_dir) as $directory) {
        $file_name = "{$directory}/{$class}.php";

        if (is_file($file_name)) {
          require_once $file_name;
          return true;
        }
      }
    }
  }
  private static function getDirList($dir) {
    $files = scandir($dir);
    $files = array_filter($files, function ($file) {
      return !in_array($file, array('.', '..'));
    });
   
    $list = array();
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
