<?php
/**
 * @copyright 2010-2015 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Product
 */

namespace jtl\Connector\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;
use jtl\Connector\Checksum\IChecksum;

/**
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class Checksum extends DataModel implements IChecksum
{
    /**
     * @var int - Checksum used to check variations for change
     */
    const TYPE_VARIATION = 1;

    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("foreignKey")
     * @Serializer\Accessor(getter="getForeignKey",setter="setForeignKey")
     */
    protected $foreignKey = null;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("endpoint")
     * @Serializer\Accessor(getter="getEndpoint",setter="setEndpoint")
     */
    protected $endpoint = '';

    /**
     * @var boolean 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("hasChanged")
     * @Serializer\Accessor(getter="getHasChanged",setter="setHasChanged")
     */
    protected $hasChanged = false;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("host")
     * @Serializer\Accessor(getter="getHost",setter="setHost")
     */
    protected $host = '';

    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("type")
     * @Serializer\Accessor(getter="getType",setter="setType")
     */
    protected $type = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->foreignKey = new Identity();
    }

    /**
     * @param Identity $foreignKey 
     * @return \jtl\Connector\Model\Checksum
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setForeignKey(Identity $foreignKey)
    {
        return $this->setProperty('foreignKey', $foreignKey, 'Identity');
    }

    /**
     * @return Identity 
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @param string $endpoint 
     * @return \jtl\Connector\Model\Checksum
     */
    public function setEndpoint($endpoint)
    {
        return $this->setProperty('endpoint', $endpoint, 'string');
    }

    /**
     * @return string 
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param boolean $hasChanged 
     * @return \jtl\Connector\Model\Checksum
     */
    public function setHasChanged($hasChanged)
    {
        return $this->setProperty('hasChanged', $hasChanged, 'boolean');
    }

    /**
     * @return boolean 
     */
    public function getHasChanged()
    {
        return $this->hasChanged;
    }

    /**
     * @return boolean
     */
    public function hasChanged()
    {
        return $this->hasChanged;
    }

    /**
     * @param string $host 
     * @return \jtl\Connector\Model\Checksum
     */
    public function setHost($host)
    {
        return $this->setProperty('host', $host, 'string');
    }

    /**
     * @return string 
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param integer $type
     * @return \jtl\Connector\Model\Checksum
     * @throws \InvalidArgumentException if the provided argument is not of type 'integer'.
     */
    public function setType($type)
    {
        return $this->setProperty('type', $type, 'integer');
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
}
