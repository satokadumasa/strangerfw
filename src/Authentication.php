<?php
#require_once __DIR__ . '/../vendor/autoload.php';
namespace strangerfw;

class Authentication{
  public static function auth(&$dbh, $request){
    $debug = new \strangerfw\utils\Logger('DEBUG');
    $auths = new \User($dbh);
    $debug->log("Authentication::auth() auth:" . print_r($auths, true));
    $auth = $auths->auth($request);
    if ($auth){
      $user_cookie_name = \strangerfw\utils\StringUtil::makeRandStr(USER_COOKIE_NAME_LENGTH);
      setcookie(COOKIE_NAME, $user_cookie_name, time() + COOKIE_LIFETIME);
      $data['Auth'] = $auth;
      \strangerfw\core\Session::set($data);
      $debug->log("Authentication::auth() SESSION:" . print_r($_SESSION, true));
      return true;
    }
    else {
      return false;
    }
  }

  public static function isAuth(){
    $debug = new \strangerfw\utils\Logger('DEBUG');
    if (DEFAULT_FLAG_OF_AUTHENTICATION ) {
      $session = \strangerfw\core\Session::get();
      $debug->log("Authentication::isAuth() session:" . print_r($session, true));
      return isset($session['Auth']) ? $session['Auth'] : false;
    }
    return true;
  }

  public static function roleCheck($role_ids, $action) {
    if (!isset($role_ids['acc'][$action]) || $role_ids['acc'][$action] = '') return true;
    $session = \strangerfw\core\Session::get();
    return in_array($session['Auth']['User']['role_id'], $role_ids['acc'][$action]);
  }
}


