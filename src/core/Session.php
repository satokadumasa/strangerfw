<?php
namespace strangerphp\core;
class Session {
  public static function sessionStart() {
    session_start(['cookie_lifetime' => COOKIE_LIFETIME]);
  }

  public static function get() {
    return isset($_SESSION[COOKIE_NAME]) ? $_SESSION[COOKIE_NAME] : false;
  }

  public static function set($value) {
    $_SESSION[COOKIE_NAME] = $value;
  }

  public static function setMessage($message, $type){
    $_SESSION[COOKIE_NAME][$type][] = $value;
  }

  public static function deleteMessage($type) {
    unset($_SESSION[COOKIE_NAME][$type]);
  }
}