<?php
namespace strangerfw\utils;

class SendNotify {
  //  ログ関連
  public $error_log;
  public $info_log;
  public $debug;
  protected $argv = [];
  protected $dbh = null;

  public function __construct() {
    $this->error_log = new \strangerfw\utils\Logger('ERROR');
    $this->info_log = new \strangerfw\utils\Logger('INFO');
    $this->debug = new \strangerfw\utils\Logger('DEBUG');
    $conf = \strangerfw\core\Config::get('database.config');
    $database = $conf['default_database'];
    $dbConnect = new \strangerfw\utils\DbConnect();
    $dbConnect->setConnectionInfo($database);
    $this->dbh = $dbConnect->createConnection();
  }

  public function sendNotify(){
    $user = new \User($this->dbh);
    $users = $user->where('User.notified_at', 'IS NULL', '')->find('all');
    foreach ($users as $key => $form) {
      $body = null;
      $user2 = null;
      $notification = new \strangerfw\utils\Notification();
      $body = $notification->geterateRegistNotifyMessage($form, 'Mailer', 'regist_notify');
      $notification->sendRegistNotify($form, $body, '登録確認メール');
      $form['User']['notified_at'] = date('Y-m-d H:i:s');
      $this->debug->log("SendNotify::sendNotify() form:".print_r($form, true));
      unset($form['User']['password']);
      unset($form['User']['authentication_key']);
      $user2 = new \User($this->dbh);
      $user2->update($form, 'send_notify');
    }
  }

}
