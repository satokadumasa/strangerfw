<?php
namespace strangerfw\core;

class Config {
  public static function get($config) {
    require CONFIG_PATH.ENVIRONMENTS."/".$config.".php";
    $vars = get_defined_vars();
    return $vars;
  }
}
