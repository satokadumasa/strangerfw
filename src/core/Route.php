<?php
namespace strangerfw\core;

class Route {
  public $route = [];
  private $default_actions = ['edit', 'create', 'save', 'update', 'confirm', 'detail', 'delete', 'index'  ];
  private $default_need_id_actions = ['edit', 'confirm', 'detail', 'delete'];
  private $default_need_id_confirm_str = ['confirm'];
  private $url_not_found = [
    'controller' => 'DefaultController', 
    'action' => 'index', 
    'uri' => '/Default/index/'
  ];

  public $error_log;
  public $info_log;
  public $debug;
  private $CONV_STRING_LIST;

  public function __construct($CONV_STRING_LIST) {
    $this->CONV_STRING_LIST = $CONV_STRING_LIST;
    $this->error_log = new \strangerfw\utils\Logger('ERROR');
    $this->info_log = new \strangerfw\utils\Logger('INFO');
    $this->debug = new \strangerfw\utils\Logger('DEBUG');

    $this->setDefaultRoutes();
  }

  public function setRoute ($uri, $controller, $action) {
    $this->route[$uri] = ['controller' => $controller, 'action' => $action];
  }

  private function setDefaultRoutes() {
    $file_list = $this->getFileList(CONTROLLER_PATH);
    foreach ($file_list as $file_name) {
      $namespace  = null;
      $controller = str_replace(CONTROLLER_PATH, '', $file_name);
      $controller = str_replace('Controller.php', '', $controller);
      $arr = explode('/', $controller);
      if(count($arr) > 1){
        $namespace = $arr[0];
        $controller = $arr[1];
      }

      if($namespace){
        $this->debug->log("Route::setDefaultRoutes() namespace:".$namespace);
      }

      foreach ($this->default_actions as $action) {
        $uri = null;
        if($namespace)
          $uri = DOCUMENT_ROOT.$namespace;
        if(in_array($action, $this->default_need_id_actions)) {
          if($action == 'index') {
            $uri .= DOCUMENT_ROOT.$controller.'/ID';
          } else {
            $uri .= DOCUMENT_ROOT.$controller.'/'.$action.'/ID';
          }
        }
        else if (in_array($action, $this->default_need_id_confirm_str)){
          if($action == 'index') {
            $uri = DOCUMENT_ROOT.$controller.'/CONFIRM_STRING';
          } else {
            $uri = DOCUMENT_ROOT.$controller.'/'.$action.'/CONFIRM_STRING';
          }
        }
        else {
          if($action == 'index') {
            $uri .= DOCUMENT_ROOT.$controller.'/';
          } else {
            $uri .= DOCUMENT_ROOT.$controller.'/'.$action.'/';
          }
        }

        if($namespace){
          $this->route[$uri] = [
            'namespace' => $namespace, 
            'controller' => $controller.'Controller', 
            'action' => $action
          ];
        }
        else{
          $this->route[$uri] = [
            'controller' => $controller.'Controller', 
            'action' => $action
          ];
        }
        // $this->debug->log("Route::setDefaultRoutes() route:".print_r($this->route, true));
      }
    }
  }

  public function findRoute($url) {
    $this->debug->log("Route::findRoute() Start");
    $this->debug->log("Route::findRoute() url:".$url);
    if (preg_match('favicon.ico', $url)) {
      return;
    }

    if (preg_match('/favicon.ico/', $url)) {
      return;
    }

    foreach ($this->route as $key => $value) {
      $uri = $key;
      $key = str_replace('/', '\/', $key);
      foreach ($this->CONV_STRING_LIST as $k => $v) {
        $key = str_replace($k, $v, $key);
      }
      $pattern = "/".$key."/";
      $this->debug->log("Route::findRoute() pattern:${pattern}");
      if (preg_match($pattern, $url)) {
        $value['uri'] = $uri;
        $this->debug->log("Route::findRoute() route find.");
        return $value;
      }
    }
    return $this->url_not_found;
  }

  public function getFileList($dir) {
    $files = scandir($dir);
    $files = array_filter($files, function ($file) { // 注(1)
      return !in_array($file, ['.', '..']);
    });

    $list = [];
    foreach ($files as $file) {
      $fullpath = rtrim($dir, '/') . '/' . $file; // 注(2)
      if (is_file($fullpath)) {
        $list[] = $fullpath;
      }
      if (is_dir($fullpath)) {
        $list = array_merge($list, $this->getFileList($fullpath));
      }
    }
   
    return $list;
  }

  public function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  }
}
