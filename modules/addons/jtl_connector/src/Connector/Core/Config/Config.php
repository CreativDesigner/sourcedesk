<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Config
 */

namespace jtl\Connector\Core\Config;

/**
 * Config Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.com>
 */
class Config extends \Noodlehaus\Config
{
    /**
     * @var string
     */
    protected $path = '';
    
    /**
     * Config constructor.
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function __construct($path)
    {
        if (!is_string($path) || !file_exists($path)) {
            throw new \InvalidArgumentException('Parameter path must be a string to an existing file');
        }
        
        parent::__construct($path);
        
        $this->path = $path;
    }
    
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function read($key, $default = null)
    {
        return $this->get($key, $default);
    }
    
    /**
     * Will set data into cache and save to file
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function save($key, $value)
    {
        parent::set($key, $value);
        
        return file_put_contents($this->path, json_encode($this->all(), JSON_PRETTY_PRINT));
    }
}
