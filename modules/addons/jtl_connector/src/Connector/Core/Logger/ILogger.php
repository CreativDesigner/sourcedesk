<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core
 */
namespace jtl\Connector\Core\Logger;

/**
 * Abstract logger interface
 *
 * @access public
 * @author Christian Spoo <christian.spoo@jtl-software.com>
 */
interface ILogger
{
    const DEBUG = 3;
    const INFO = 2;
    const WARNING = 1;
    const ERROR = 0;
    
    public static function log($message, $level = ILogger::ERROR, $module = 'General');
    public static function getLogEntries($maxLevel = ILogger::WARNING, $since = null, $module = null);
    public static function clearLog();
}
