<?php
/* @ignore
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Integrations\PhpSdk;

use Monolog\Monolog;
use Monolog\Handler\RotatingFileHandler;

/**
 * Log API requests and responses from Turnitin.
 */
class Logger {

    /**
     * The location of the log directory.
     */
    const LOG_DIR = '/turnitinsim/logs/';

    /**
     * The number of logs to keep.
     */
    const KEEPLOGS = 10;

    /**
     * The prefix for the API log file name.
     */
    const APILOG_PREFIX = 'apilog_';

    private \Monolog\Logger $logger;

    /**
     * plagiarism_turnitinsim_logger constructor.
     */
    public function __construct() {
        global $CFG;
        
        $this->logger = new \Monolog\Logger(self::APILOG_PREFIX);

        // Use RotatingFileHandler for automatic log rotation
        $handler = new RotatingFileHandler($CFG->tempdir.'/'.self::LOG_DIR, self::KEEPLOGS, \Monolog\Logger::DEBUG);
        $this->logger->pushHandler($handler);
    }


    public function debug($string) {
      $this->logger->debug($string);
    }
  
    public function info($string) {
      $this->logger->info($string);
    }

    public function warning($string) {
        $this->logger->warning($string);
    }

    public function error($string) {
        $this->logger->error($string);
    }
}

