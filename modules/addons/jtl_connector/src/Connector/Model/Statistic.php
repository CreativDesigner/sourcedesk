<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Internal 
 */

namespace jtl\Connector\Model;

/**
 * Statistic Model
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Internal
 */
class Statistic extends DataModel
{
    /**
     * @type int
     */
    protected $available = 0;

    /**
     * @type string
     */
    protected $controllerName = '';

    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Model\DataModel::map()
     */ 
    public function map($toWawi = false, \stdClass $obj = null)
    {
    
    }

    /**
     * @return integer
     */
    public function getAvailable()
    {
        return $this->available;
    }
    
    /**
     * @param  integer $available
     * @return \jtl\Connector\Model\Statistic
     * @throws \InvalidArgumentException if the provided argument is not of type 'integer'.
     */
    public function setAvailable($available)
    {
        return $this->setProperty('available', $available, 'integer');
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }
    
    /**
     * @param  string $controllerName
     * @return \jtl\Connector\Model\Statistic
     * @throws \InvalidArgumentException if the provided argument is not of type 'string'.
     */
    public function setControllerName($controllerName)
    {
        return $this->setProperty('controllerName', $controllerName, 'string');
    }
}
