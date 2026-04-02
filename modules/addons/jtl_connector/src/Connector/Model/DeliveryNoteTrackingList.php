<?php
/**
 * @copyright 2010-2015 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Product
 */

namespace jtl\Connector\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;

/**
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class DeliveryNoteTrackingList extends DataModel
{
    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     * @Serializer\Accessor(getter="getName",setter="setName")
     */
    protected $name = '';

    /**
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("codes")
     * @Serializer\AccessType("reflection")
     */
    protected $codes = array();


    /**
     * @param string $name 
     * @return \jtl\Connector\Model\DeliveryNoteTrackingList
     */
    public function setName($name)
    {
        return $this->setProperty('name', $name, 'string');
    }

    /**
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $code
     * @return \jtl\Connector\Model\DeliveryNoteTrackingList
     */
    public function addCode($code)
    {
        $this->codes[] = $code;
        return $this;
    }
    
    /**
     * @param array $codes
     * @return \jtl\Connector\Model\DeliveryNoteTrackingList
     */
    public function setCodes(array $codes)
    {
        $this->codes = $codes;
        return $this;
    }
    
    /**
     * @return string[]
     */
    public function getCodes()
    {
        return $this->codes;
    }

    /**
     * @return \jtl\Connector\Model\DeliveryNoteTrackingList
     */
    public function clearCodes()
    {
        $this->codes = array();
        return $this;
    }
}
