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
        $this->debug->log("Dispatcher::dispatcheController() route:".print_r($route, true));
        $controller_name = $route['controller'];
        // $this->debug->log("Dispatcher::dispatcheController() controller_name:".print_r($controller_name, true));
        $controller_name = "\\".$controller_name;
        $this->debug->log("Dispatcher::dispatcheController() controller_name:".$controller_name);
        $controller = new $controller_name($route['uri'], $_SERVER['REQUEST_URI']);
        $this->debug->log("Dispatcher::dispatcheController() CH-01");
        $controller->setAction($route['action']);
        $this->debug->log("Dispatcher::dispatcheController() CH-02");
        $controller->beforeAction();
        $this->debug->log("Dispatcher::dispatcheController() CH-03");
        $action = $route['action'];
        $this->debug->log("Dispatcher::dispatcheController() CH-04");
        $controller->$action();
        $this->debug->log("Dispatcher::dispatcheController() CH-05");
        $controller->afterAction();
        $this->debug->log("Dispatcher::dispatcheController() CH-06");
        $controller->render();
      }
      exit();
    } catch (\Exception $e) {
      $this->debug->log("Dispatcher::dispatcheController() error:".$e->getMessage());
    }
  }
}