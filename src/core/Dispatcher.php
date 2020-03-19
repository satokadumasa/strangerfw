<?php
namespace strangerphp\core;
class Dispatcher {
  private $route;
  private $default_database;
  public function __construct($route) {
    $this->route = $route;
    $this->error_log = new Logger('ERROR');
    $this->info_log = new Logger('INFO');
    $this->debug = new Logger('DEBUG');
  }

  public function dispatcheController() {
    try {
      $route = $this->route->findRoute($_SERVER['REQUEST_URI']);
      $this->debug->log("Dispatcher::dispatcheController() route:".print_r($route, true));
      $controller_name = $route['controller'];
      $controller = new $controller_name($route['uri'], $_SERVER['REQUEST_URI']);
      $controller->setAction($route['action']);
      $controller->beforeAction();
      $action = $route['action'];
      $controller->$action();
      $controller->afterAction();
      $controller->render();
      exit();
    } catch (Exception $e) {
      $this->debug->log("Dispatcher::dispatcheController() error:".$e->getMessage());
    }
  }
}