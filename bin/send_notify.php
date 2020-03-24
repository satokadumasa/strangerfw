<?php
require_once dirname(dirname(dirname(__FILE__))) . "/config/config.php";
require_once LIB_PATH . "/core/ClassLoader.php";

ini_set('error_reporting', 0);

putenv("ENVIRONMENT=development");

spl_autoload_register(['ClassLoader', 'loadClass']);

$stranger = new \strangerfw\utils\SendNotify();
$stranger->sendNotify();
