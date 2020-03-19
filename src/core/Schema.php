<?php
namespace strangerphp\core;
class Schema {
  public static function get($schena) {
    return yaml_parse_file( DB_PATH."/".$schena.".php");
  }
}