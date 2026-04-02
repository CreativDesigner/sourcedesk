<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

/**
 * Abstract Singleton Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
abstract class Singleton
{
    /**
     * Array of \jtl\Connector\Core\Utilities\Singleton Objects
     *
     * @var multiple: \jtl\Connector\Core\Utilities\Singleton
     */
    protected static $_instances = array();

    /**
     * Basic Singleton implementation
     *
     * @return \jtl\Connector\Core\Utilities\Singleton:
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class();
        }
        
        return self::$_instances[$class];
    }

    /**
     * Basic Constructor
     */
    protected function __construct()
    {
    }
    
    /**
     * Clone Method
     */
    final protected function __clone()
    {
    }
}
