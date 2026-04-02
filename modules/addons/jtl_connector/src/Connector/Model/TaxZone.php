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
 * Tax zone model (set in JTL-Wawi ERP).
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class TaxZone extends DataModel
{
    /**
     * @var Identity Unique taxZone id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var string Optional tax zone name e.g. "EU Zone"
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     * @Serializer\Accessor(getter="getName",setter="setName")
     */
    protected $name = '';

    /**
     * @var \jtl\Connector\Model\TaxZoneCountry[]
     * @Serializer\Type("array<jtl\Connector\Model\TaxZoneCountry>")
     * @Serializer\SerializedName("countries")
     * @Serializer\AccessType("reflection")
     */
    protected $countries = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
    }

    /**
     * @param Identity $id Unique taxZone id
     * @return \jtl\Connector\Model\TaxZone
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique taxZone id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name Optional tax zone name e.g. "EU Zone"
     * @return \jtl\Connector\Model\TaxZone
     */
    public function setName($name)
    {
        return $this->setProperty('name', $name, 'string');
    }

    /**
     * @return string Optional tax zone name e.g. "EU Zone"
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \jtl\Connector\Model\TaxZoneCountry $country
     * @return \jtl\Connector\Model\TaxZone
     */
    public function addCountry(\jtl\Connector\Model\TaxZoneCountry $country)
    {
        $this->countries[] = $country;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\TaxZoneCountry[]
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @return \jtl\Connector\Model\TaxZone
     */
    public function clearCountries()
    {
        $this->countries = array();
        return $this;
    }
}
