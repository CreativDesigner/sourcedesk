<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Model
 */

namespace jtl\Connector\Type;

/**
 * Entity data model
 *
 * @access public
 * @package jtl\Connector\Type
 */
class PropertyInfo
{
    /**
     * Property name
     * @var string
     */
    protected $name = '';

    /**
     * Property type
     * @var string
     */
    protected $type = '';

    /**
     * Property default value
     * @var string
     */
    protected $defaultValue;

    /**
     * Property primary status
     * @var boolean
     */
    protected $isPrimary;

    /**
     * Property identity status
     * @var boolean
     */
    protected $isIdentity;

    /**
     * Property navigation status
     * @var boolean
     */
    protected $isNavigation;

    /**
     * Class Constructor
     *
     * @param string $name
     * @param string $type
     * @param string $defaultValue
     * @param boolean $isPrimary
     * @param boolean $isIdentity
     * @param boolean $isNavigation
     */
    public function __construct($name, $type, $defaultValue, $isPrimary, $isIdentity, $isNavigation)
    {
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->isPrimary = $isPrimary;
        $this->isIdentity = $isIdentity;
        $this->isNavigation = $isNavigation;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
    
    /**
     * @return boolean
     */
    public function isPrimary()
    {
        return $this->isPrimary;
    }
    
    /**
     * @return boolean
     */
    public function isIdentity()
    {
        return $this->isIdentity;
    }
    
    /**
     * @return boolean
     */
    public function isNavigation()
    {
        return $this->isNavigation;
    }
}
