<?php
namespace OCA\SsoAuth\Service;

use OCP\IConfig;

class LogService {
    private $logLevel;
    private $logLocation;

    private const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARN' => 2,
        'ERROR' => 3
    ];

    public function __construct(IConfig $config) {
        $this->logLevel = $config->getSystemValue('loglevel', '0');
        $this->logLocation = $config->getSystemValue('logfile', '/var/www/owncloud/data/sso_auth.log');

        // Ensure log directory exists
        $logDir = dirname($this->logLocation);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->logLocation)) {
            touch($this->logLocation);
            chmod($this->logLocation, 0644);
        }
    }

    public function log($level, $message) {
        // Check if log level is high enough
        if (!isset(self::LEVELS[$level]) || self::LEVELS[$level] < $this->logLevel) {
            return;
        }

        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);

        // Write to log file
        file_put_contents($this->logLocation, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public function debug($message) {
        $this->log('DEBUG', $message);
    }

    public function info($message) {
        $this->log('INFO', $message);
    }

    public function warn($message) {
        $this->log('WARN', $message);
    }

    public function error($message) {
        $this->log('ERROR', $message);
    }
}