<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Session
 */

namespace jtl\Connector\Session;

use \jtl\Connector\Core\Exception\SessionException;

class SessionHelper
{
    protected $namespace;
    
    /**
     * Constructor
     *
     * @param string $namespace
     * @throws \jtl\Connector\Core\Exception\SessionException
     */
    public function __construct($namespace = "default")
    {
        if ($namespace === "") {
            throw new SessionException("Session namespace must be a non-empty string.");
        }
        
        if ($namespace[0] == "_") {
            throw new SessionException("Session namespace must not start with an underscore.");
        }
        
        if (preg_match('#(^[0-9])#i', $namespace[0])) {
            throw new SessionException("Session namespace must not start with a number.");
        }
        
        $this->namespace = $namespace;
    }
    
    /**
     * Magic Set
     *
     * @param string $name
     * @param mixed $value
     * @throws \jtl\Connector\Core\Exception\SessionException
     */
    public function __set($name, $value)
    {
        if ($name === null || $name === "") {
            throw new SessionException("The '{$name}' key must be a non-empty string");
        }
        
        $name = (string)$name;
        $_SESSION[$this->namespace][$name] = $value;
    }
    
    /**
     * Magic Get
     *
     * @param string $name
     * @throws \jtl\Connector\Core\Exception\SessionException
     * @return NULL
     */
    public function & __get($name)
    {
        if ($name === null || $name === "") {
            throw new SessionException("The '{$name}' key must be a non-empty string");
        }
        
        $name = (string)$name;
        if (isset($_SESSION[$this->namespace][$name])) {
            return $_SESSION[$this->namespace][$name];
        }

        $null = null;
        return $null;
    }
    
    /**
     * Magic Isset
     *
     * @param string $name
     * @throws \jtl\Connector\Core\Exception\SessionException
     */
    public function __isset($name)
    {
        if ($name === null || $name === "") {
            throw new SessionException("The '{$name}' key must be a non-empty string");
        }
        
        $name = (string)$name;
        
        return isset($_SESSION[$this->namespace][$name]);
    }
    
    /**
     * Magic Unset
     * @param string $name
     * @throws \jtl\Connector\Core\Exception\SessionException
     */
    public function __unset($name)
    {
        if ($name === null || $name === "") {
            throw new SessionException("The '{$name}' key must be a non-empty string");
        }
        
        $name = (string)$name;
        if (isset($_SESSION[$this->namespace][$name])) {
            unset($_SESSION[$this->namespace][$name]);
        }
    }
}
