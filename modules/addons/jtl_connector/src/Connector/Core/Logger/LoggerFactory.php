<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core
 */

namespace jtl\Connector\Core\Logger;

class LoggerFactory
{
    /**
     * Array of Logger channels
     */
    protected static $logger;

    /**
     * @param string $channel The log channel
     * @return \Monolog\Logger
     */
    public static function create($channel)
    {
        if (static::$logger === null) {
            static::$logger = array();
        }

        if (!isset(static::$logger[$channel])) {
            static::$logger[$channel] = new \Monolog\Logger($channel);
        }

        return static::$logger[$channel];
    }

    /**
     * @param string $channel The log channel
     * @return \Monolog\Logger
     */
    public static function get($channel)
    {
        if (isset(static::$logger[$channel])) {
            return static::$logger[$channel];
        } else {
            return static::create($channel);
        }
    }
}
