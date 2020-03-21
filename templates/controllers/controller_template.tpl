<?php
class <!----class_name---->Controller extends \strangerfw\core\controller\BaseController{
  public function __construct($uri, $url = null) {
    $conf = \strangerfw\core\Config::get('database.config');
    $database = $conf['default_database'];
    parent::__construct($database, $uri, $url);
    $this->controller_class_name = str_replace('Controller', '', get_class($this));;
  }

  <!----controller_method---->
}