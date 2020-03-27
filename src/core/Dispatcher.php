<?php
namespace strangerfw\core;

class Dispatcher {
  private $route;
  private $default_database;
  public function __construct($route) {
    $this->route = $route;
    $this->error_log = new \strangerfw\utils\Logger('ERROR');
    $this->info_log = new \strangerfw\utils\Logger('INFO');
    $this->debug = new \strangerfw\utils\Logger('DEBUG');
  }

  public function dispatcheController() {
    try {
      $route = $this->route->findRoute($_SERVER['REQUEST_URI']);
      if($route) {
        $controller_name = $route['controller'];
        $controller_name = "\\".$controller_name;
        $controller = new $controller_name($route['uri'], $_SERVER['REQUEST_URI']);
        $controller->setAction($route['action']);
        $controller->beforeAction();
        $action = $route['action'];
        $controller->$action();
        $controller->afterAction();
        $controller->render();
      }
      exit();
    } catch (\Exception $e) {
      $this->debug->log("Dispatcher::dispatcheController() error:".$e->getMessage());
    }
  }
}