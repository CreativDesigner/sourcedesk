<?php

/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\System
 */

namespace jtl\Connector\Core\System;

/**
 * Filesystem tool class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Tool
{

    /**
     * @var boolean
     */
    public static $test = false;

    /**
     * @var boolean
     */
    public static $exception = false;

    /**
     * @var boolean
     */
    static public $ret_sys_getloadavg = array('0.01', '0.02', '0.01');

    /**
     * @var boolean
     */
    static public $ret_sys_win_getloadavg = array('0.01', '0.02', '0.01');
    
    /**
     * @var mixed
     */
    public static $ret_get_object_vars;

    /**
     * This method will return the average system load for unix based systems.
     *
     * @return array
     */
    public static function sys_getloadavg()
    {
        if (self::$exception) {
            throw new \RuntimeException('Unit Testing Exception');
        }
        if (self::$test) {
            return self::$ret_sys_getloadavg;
        }
        return sys_getloadavg();
    }

    /**
     * This method will return the average system load for windows based systems.
     *
     * @return array
     */
    public static function sys_win_getloadavg()
    {
        if (self::$exception) {
            throw new \RuntimeException('Unit Testing Exception');
        }
        if (self::$test) {
            return self::$ret_sys_win_getloadavg;
        }
        return null;
    }
    
    /**
     * Returns object variables from std class.
     *
     * @param \stdClass $obj
     * @return mixed
     * @throws \RuntimeException
     */
    public static function get_object_vars($obj)
    {
        if (self::$exception) {
            throw new \RuntimeException('Unit Testing Exception');
        }
        if (self::$test) {
            return self::$ret_get_object_vars;
        }
        return get_object_vars($obj);
    }
}
