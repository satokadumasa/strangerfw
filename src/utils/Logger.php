<?php
namespace strangerfw\utils;

class Logger {
  private $log_file = "";
  private $log_level = 'INFO';

  public function __construct($log_level){
    $this->log_level = $log_level;
    $date = new \DateTime();
    $now_date = $date->format('Ymd');
    $this->log_file = LOG_PATH . $this->log_level . "_". $now_date . ".log";
  }

  public function log($message){
    if ($this->log_level == 'DEBUG' && LOG_LEVEL === PRODUCTION) return;

    if ($fp = fopen($this->log_file, 'a')) {
      list($sec, $usec) = explode('.',microtime(true));
      $date = new \DateTime();
      $now_date = $date->format('Y-m-d H:i:s').".".sprintf('%05d', $usec); // 2014-08-06 21:15:49.00001
      $message = "[${now_date}]  ${message}";
      fwrite($fp, $message . "\n");
      fclose($fp);
    }
  }
}