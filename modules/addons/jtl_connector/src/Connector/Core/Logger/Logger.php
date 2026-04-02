<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core
 */

namespace jtl\Connector\Core\Logger;

use jtl\Connector\Core\IO\Path;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;

class Logger extends \Monolog\Logger
{
    /**
     * Adds a log record at an arbitrary level.
     *
     * @param mixed $message The log message
     * @param mixed $level The log level
     * @param string $channel The log channel
     * @return Boolean Whether the record has been processed
     */
    public static function write($message, $level = self::ERROR, $channel = 'general')
    {
        $forceWriting = false;
        if (function_exists('Application') && !is_null(Application()->getConfig())) {
            try {
                $forceWriting = Application()->getConfig()->get('developer_logging');
            } catch (\Exception $e) { }
        }

        if (!$forceWriting && $level == self::DEBUG && getenv('APPLICATION_ENV') !== 'development') {
            return null;
        }

        if (defined('LOG_DIR')) {
            $path = array(
                LOG_DIR,
                "{$channel}.log"
            );
        }
        else {
            $path = array(
                CONNECTOR_DIR,
                'logs',
                "{$channel}.log"
            );
        }

        $log = LoggerFactory::get($channel);
        if (!$log->isHandling($level)) {
            $log->pushHandler(new RotatingFileHandler(implode(DIRECTORY_SEPARATOR, $path)), 2, $level);
        }

        return @$log->log($level, $message);
    }

    /**
     * Gets the current logger
     *
     * @param string $channel The log channel
     * @return \Monolog\Logger
     */
    public static function getLogger($channel)
    {
        return LoggerFactory::get($channel);
    }
}
