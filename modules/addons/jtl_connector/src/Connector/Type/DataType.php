<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Model
 */

namespace jtl\Connector\Type;

use jtl\Connector\Type\PropertyInfo;

/**
 * Entity data model
 *
 * @access public
 * @package jtl\Connector\Type
 */
abstract class DataType
{
    /**
     * List of PropertyInfos
     * @var PropertyInfo[]
     */
    private $_propertyInfo = null;

    /**
     * Returns all the public properties of the current Type.
     *
     * @return PropertyInfo[]
     */
    public function getProperties()
    {
        if (is_null($this->_propertyInfo)) {
            $this->_propertyInfo = $this->loadProperties();
        }
            
        return $this->_propertyInfo;
    }
    
    /**
     * Searches for the public property with the specified name.
     *
     * @param string $propertyName
     * @return PropertyInfo
     */
    public function getProperty($propertyName)
    {
        foreach ($this->getProperties() as $property) {
            if ($property->getName() == $propertyName) {
                return $property;
            }
        }
        
        return null;
    }
    
    /**
     * @return PropertyInfo[]
     */
    abstract protected function loadProperties();
    abstract public function isMain();
}
