<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Base;

/**
 * Base class for all feature classes.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class Base
{

    /**
     * @var string
     */
    protected $_name;
    protected $_classes;

    /**
     * Returns the name.
     *
     * @return string
     */
    final public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the name.
     *
     * @param string $name
     * @return boolean
     */
    final public function setName($name)
    {
        if ($this->_name != $name) {
            $this->_name = $name;
            return true;
        }
        return false;
    }

    /**
     * Returns all declared classes.
     * We need this way, because the autoloader will try to include the class
     * file if we use "class_exists" or similar functions.
     *
     * @return array
     */
    public function getClasses()
    {
        if (!isset($this->_classes)) {
            $this->_classes = get_declared_classes();
        }
        return $this->_classes;
    }
}
