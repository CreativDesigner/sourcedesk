<?php
/**
 * @copyright 2010-2014 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Ack
 */

namespace jtl\Connector\Model;

use JMS\Serializer\Annotation as Serializer;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * BoolResult
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Internal
 * @Serializer\AccessType("public_method")
 */
class BoolResult extends DataModel
{
    /**
     * @var bool
     */
    protected $result = false;
    
    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * @param bool $result
     * @return BoolResult
     */
    public function setResult($result)
    {
        return $this->setProperty('result', $result, 'boolean');
    }
}
