<?php
namespace strangerfw\core;

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

  public static function getCSRFToken()
  {
    $bytes = function_exists('random_bytes') ? 
    random_bytes(48) : openssl_random_pseudo_bytes(48);
    $nonce = base64_encode($bytes);

    if (!empty($_SESSION[COOKIE_NAME]['csrf_tokens'])) {
      $_SESSIO[COOKIE_NAME]['csrf_tokens'] = [];
    }

    $_SESSION[COOKIE_NAME]['csrf_tokens'][$nonce] = true;

    return $nonce;
  }

  public static function validateCSRFToken($token)
  {
      if (isset($_SESSION[COOKIE_NAME]['csrf_tokens'][$token])) {
          unset($_SESSION[COOKIE_NAME]['csrf_tokens'][$token]);
          return true;
      }

      return false;
  }
}