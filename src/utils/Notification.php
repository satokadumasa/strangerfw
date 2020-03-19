<?php
require_once BAMBOO_ROOT . '/vendor/autoload.php';

class Notification {
  //  ログ関連
  public $error_log;
  public $info_log;
  public $debug;

  public $conf = null;
  public function __construct() {
    $this->error_log = new Logger('ERROR');
    $this->info_log = new Logger('INFO');
    $this->debug = new Logger('DEBUG');
    $conf = Config::get('mailer');
    $this->conf = $conf['mailer'];
  }

  public function sendRegistNotify($data, $body, $subject){
    $transport = \Swift_SmtpTransport::newInstance()
        ->setHost($this->conf['host'])
        ->setPort($this->conf['port'])
        ->setEncryption($this->conf['encrypt'])
        ->setUsername($this->conf['username'])
        ->setPassword($this->conf['password'])
    ;
    $mailer = \Swift_Mailer::newInstance($transport);
    $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom(array($this->conf['from'] => $this->conf['name']))
        ->setTo(array($data['User']['email'] => $this->conf['name']))
        ->setBody($body, 'text/plain')
        ;
    $failedRecipients = array();
    $mailer->send($message, $failedRecipients);
  }

  public function geterateRegistNotifyMessage($form, $class_name, $teplate_name) {
    $url = BASE_URL . 'confirm/' . $form['User']['authentication_key'] .'/';

    $site_info = Config::get('site_info');

    $mailer_data = [
      'Mailer' => [
        'username' => $form['User']['username'],
        'email' => $form['User']['email'],
        'url' => $url,
        'site_name' => $site_info['site_info']['site_name'],
        'address' => $site_info['site_info']['address'],
        'tel' => $site_info['site_info']['tel'],
        'admin_mail' => $this->conf['from'],
      ],
    ];
    $view = new View($teplate_name);
    $body = [];
    $file_name = VIEW_TEMPLATE_PATH.$class_name.'/'.$teplate_name.'.tpl';
    $view->framingView($body, $mailer_data, $file_name, 'Mailer');
    return implode('', $body);
  }
}