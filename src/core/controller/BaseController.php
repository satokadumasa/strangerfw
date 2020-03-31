<?php
namespace strangerfw\core\controller;

class BaseController {
  //  ログ関連
  public $error_log;
  public $info_log;
  public $debug;

  //  データベースハンドラー  
  protected $dbh = null;
  protected $dbConnect = null;
  protected $request = [];
  protected $view = null;
  protected $data = [];

  public $action = null;
  public $controller_class_name = null;
  //  認証関連
  protected $auth_check = [];
  public $roles = [];
  public $role_ids = [];
  protected $auth = null;

  protected $template = null;

  public function __construct($database, $uri, $url) {
    $this->error_log = new \strangerfw\utils\Logger('ERROR');
    $this->info_log = new \strangerfw\utils\Logger('INFO');
    $this->debug = new \strangerfw\utils\Logger('DEBUG');
    $this->debug->log("BaseController::__construct()");
    \strangerfw\core\Session::sessionStart();
    $this->dbConnect = new \strangerfw\utils\DbConnect();
    $this->dbConnect->setConnectionInfo($database);
    $this->dbh = $this->dbConnect->createConnection();
    $this->defaultSet();
    $this->setRequest($uri, $url);
    $this->view = new \strangerfw\utils\View();
  }

  protected function defaultSet(){
    $this->set('document_root',DOCUMENT_ROOT);
    $log_out_str = "";
    if (isset($_SESSION[COOKIE_NAME]['error_message'])) {
      $this->set('error_message', $_SESSION[COOKIE_NAME]['error_message']);
    }
    $this->set('Sitemenu',$log_out_str);
    $session = \strangerfw\core\Session::get();
    $menu_helper = new \MenuHelper($session['Auth']);
    if (isset($session['Auth'])) {
      $log_out_str = $menu_helper->site_menu($session['Auth'], 'logined');
      $this->auth = $session['Auth'];
    }
    else {
      $log_out_str = $menu_helper->site_menu($session['Auth'], 'nologin');
      $log_out_str = "<a href='".DOCUMENT_ROOT."login/'>Login</a>";
    }
    $this->set('Sitemenu',$log_out_str);
    \strangerfw\core\Session::deleteMessage('error_message');
    
    $this->set('document_root', DOCUMENT_ROOT);
    $this->set('site_name', SITE_NAME);
  }

  public function setRequest($uri, $url) {
    if (isset($_POST)) {
      foreach ($_POST as $key => $value) {
        $this->perseKey($key, $value);
      }
    }

    if (isset($_GET)) {
      foreach ($_GET as $key => $value) {
        $this->perseKey($key, $value);
      }
    }

    $arr = explode('?', $url);
    $urls = explode('/', $arr[0]);
    $uris = explode('/', $uri);
    
    for ($i = 0; $i < count($urls); $i++) { 
      if(!isset($uris[$i])) continue;
      if($uris[$i] == $urls[$i]) continue;
      $this->request[mb_strtolower($uris[$i], 'UTF-8')] = $urls[$i];
    }
  }

  /**
   *
   */
  public function setAction($action) {
    $this->action = $action;
  }

  /**
   *
   */
  public function beforeAction() {
    if ($this->auth_check && in_array($this->action, $this->auth_check)) {
      $auth = \strangerfw\Authentication::isAuth();
      if($auth){
        if ($this->role_ids && \strangerfw\Authentication::roleCheck($this->role_ids, $this->action)){
          $this->redirect(DOCUMENT_ROOT);
          exit();
        }
      } 
    }
  }

  /**
   *
   */
  public function afterAction() {
    $this->debug->log("BaseController::after()");
  }

  /**
   *
   */
  protected function set($key, $data){
    $this->data[$key] = $data;
  }

  /**
   *
   */
  public function render(){
    $this->debug->log("BaseController::render()");
    $this->debug->log("BaseController::render() data:" . print_r($this->data, true));
    $this->set('SiteTitle', SITE_NAME);
    $this->template = $this->template ? $this->template : $this->action;
    $this->debug->log("BaseController::render() template[" . $this->template . "]");
    $this->view->render($this->controller_class_name, $this->template, $this->data);
  }

  /**
   *
   */
  public function setTemplate($template)
  {
    $this->template = $template;
  }

  /**
   *
   */
  protected function perseKey($key, $value) {
    $this->request[$key] = $value;
  }

  /**
   *
   */
  protected function redirect($url) {
    header("Location: {$url}");
    exit;
  }

  /**
   *
   */
  public function setAuthCheck($actions) {
    $this->auth_check = $actions;
  }
}
