<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Result
 */
namespace jtl\Connector\Result;

/**
 * Connector Handle Result
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Action
{
    /**
     * Status of the processing of an action
     *
     * @var bool
     */
    protected $_isHandled;
    
    /**
     * Action Result
     *
     * @var mixed
     */
    protected $_result;
    
    /**
     * Action Error
     *
     * @var mixed
     */
    protected $_error;

    /**
     * Getter for $_isHandled
     *
     * @return bool
     */
    public function isHandled()
    {
        return $this->_isHandled;
    }

    /**
     * Constructor
     *
     * @param bool $handled
     * @param mixed $result
     * @param mixed $error
     */
    public function __construct($handled = null, $result = null, $error = null)
    {
        $this->_isHandled = $handled;
        $this->_result = $result;
        $this->_error = $error;
    }

    /**
     * Setter for $_isHandled
     *
     * @param bool $handled
     * @return \jtl\Connector\Result\Action
     */
    public function setHandled($handled)
    {
        $this->_isHandled = (bool)$handled;
        return $this;
    }

    /**
     * Getter for $_result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Setter for $_result
     *
     * @param mixed $result
     * @return \jtl\Connector\Result\Action
     */
    public function setResult($result)
    {
        $this->_result = $result;
        return $this;
    }

    /**
     * Getter for $_error
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Setter for $_error
     *
     * @param mixed $error
     * @return \jtl\Connector\Result\Action
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * Return true if an error occurs otherwise false
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->_error !== null);
    }
}
