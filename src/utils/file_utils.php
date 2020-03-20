<?php
namespace strangerfw\utils;

class FileUtil{
  private $fileatime = null;

  public function __construct($fileatime){
    $this->fileatime = $fileatime;
  }

  public static function is_hash($arr) {
    if (array_values($arr) === $arr) {
      return false;
      echo '$arrは連想配列';
    }
    else{
      return true;
    }
  }
}