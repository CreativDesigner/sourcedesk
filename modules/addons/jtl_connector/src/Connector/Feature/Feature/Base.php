<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Feature;

use \jtl\Connector\Feature\Feature\IFeature;
use \jtl\Connector\Feature\Exception\Method as ExceptionMethod;
use \jtl\Connector\Feature\Base\Base as BaseClass;
use \jtl\Connector\Feature\Method\IMethod;

/**
 * Basic feature class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class Base extends BaseClass implements IFeature
{

    /**
     * @var array
     */
    protected $_methods = array();

    /**
     * Adds a new method to the feature.
     *
     * @param \jtl\Connector\Feature\Method\IMethod $method A method object.
     * @throws ExceptionMethod If the method already exists we need to raise a
     * exception to inform the calling part.
     */
    public function addMethod(IMethod $method)
    {
        if ($this->existsMethod($method->getName())) {
            throw new ExceptionMethod(sprintf('The method "%s" already exists in feature "%s"', $method->getName(), $this->_name));
        }
        $this->_methods[$method->getName()] = $method;
    }

    /**
     * Deletes a method from a feature by name.
     *
     * @param string $name The method name inside of the _methods array.
     * @throws ExceptionMethod If the method doesn't exist we need to raise a
     * exception to inform the calling part.
     */
    public function delMethod($name)
    {
        if (!$this->existsMethod($name)) {
            throw new ExceptionMethod(sprintf('The method "%s" doesn\'t exist in feature "%s"', $name, $this->_name));
        }
        unset($this->_methods[$name]);
    }

    /**
     * Returns a boolean if a method exists in the _methods array.
     *
     * @param string $name The name of the method we're looking for.
     * @return boolean If the method already exists in our _methods array TRUE
     * otherwhise FALSE.
     */
    public function existsMethod($name)
    {
        return isset($this->_methods[$name]);
    }

    /**
     * Returns a method of a feature if it exists.
     *
     * @param string $name The name of your requested method.
     * @return \jtl\Connector\Feature\Method\IMethod The requested method.
     * @throws ExceptionMethod If the method is not set we need to raise a
     * exception to inform the calling part.
     */
    public function getMethod($name)
    {
        if (!$this->existsMethod($name)) {
            throw new ExceptionMethod(sprintf('The method "%s" doesn\'t exist in feature "%s"', $name, $this->_name));
        }
        return $this->_methods[$name];
    }
}
