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
 * Currency model properties.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class Currency extends DataModel
{
    /**
     * @var Identity Unique currency id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var string Optional delimiter char for cent, default=",". Ignore this flag if you have the correct user locale preference.
     * @Serializer\Type("string")
     * @Serializer\SerializedName("delimiterCent")
     * @Serializer\Accessor(getter="getDelimiterCent",setter="setDelimiterCent")
     */
    protected $delimiterCent = '';

    /**
     * @var string Optional delimiter char for thousand. Default=".". Ignore this flag if you have the correct user locale preference.
     * @Serializer\Type("string")
     * @Serializer\SerializedName("delimiterThousand")
     * @Serializer\Accessor(getter="getDelimiterThousand",setter="setDelimiterThousand")
     */
    protected $delimiterThousand = '';

    /**
     * @var double Optional conversion factor to default currency. Default is 1 (equals default currency)
     * @Serializer\Type("double")
     * @Serializer\SerializedName("factor")
     * @Serializer\Accessor(getter="getFactor",setter="setFactor")
     */
    protected $factor = 0.0;

    /**
     * @var boolean Optional: Display currency before or after value. Ignore this flag if you have the correct user locale preference. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("hasCurrencySignBeforeValue")
     * @Serializer\Accessor(getter="getHasCurrencySignBeforeValue",setter="setHasCurrencySignBeforeValue")
     */
    protected $hasCurrencySignBeforeValue = false;

    /**
     * @var boolean Optional: Flag default currency. True, if this is the default currency. Exact one currency must be marked as default. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isDefault")
     * @Serializer\Accessor(getter="getIsDefault",setter="setIsDefault")
     */
    protected $isDefault = false;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("iso")
     * @Serializer\Accessor(getter="getIso",setter="setIso")
     */
    protected $iso = '';

    /**
     * @var string Currency name
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     * @Serializer\Accessor(getter="getName",setter="setName")
     */
    protected $name = '';

    /**
     * @var string Optional HTML name e.g. "&euro;"
     * @Serializer\Type("string")
     * @Serializer\SerializedName("nameHtml")
     * @Serializer\Accessor(getter="getNameHtml",setter="setNameHtml")
     */
    protected $nameHtml = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
    }

    /**
     * @param Identity $id Unique currency id
     * @return \jtl\Connector\Model\Currency
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique currency id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $delimiterCent Optional delimiter char for cent, default=",". Ignore this flag if you have the correct user locale preference.
     * @return \jtl\Connector\Model\Currency
     */
    public function setDelimiterCent($delimiterCent)
    {
        return $this->setProperty('delimiterCent', $delimiterCent, 'string');
    }

    /**
     * @return string Optional delimiter char for cent, default=",". Ignore this flag if you have the correct user locale preference.
     */
    public function getDelimiterCent()
    {
        return $this->delimiterCent;
    }

    /**
     * @param string $delimiterThousand Optional delimiter char for thousand. Default=".". Ignore this flag if you have the correct user locale preference.
     * @return \jtl\Connector\Model\Currency
     */
    public function setDelimiterThousand($delimiterThousand)
    {
        return $this->setProperty('delimiterThousand', $delimiterThousand, 'string');
    }

    /**
     * @return string Optional delimiter char for thousand. Default=".". Ignore this flag if you have the correct user locale preference.
     */
    public function getDelimiterThousand()
    {
        return $this->delimiterThousand;
    }

    /**
     * @param double $factor Optional conversion factor to default currency. Default is 1 (equals default currency)
     * @return \jtl\Connector\Model\Currency
     */
    public function setFactor($factor)
    {
        return $this->setProperty('factor', $factor, 'double');
    }

    /**
     * @return double Optional conversion factor to default currency. Default is 1 (equals default currency)
     */
    public function getFactor()
    {
        return $this->factor;
    }

    /**
     * @param boolean $hasCurrencySignBeforeValue Optional: Display currency before or after value. Ignore this flag if you have the correct user locale preference. 
     * @return \jtl\Connector\Model\Currency
     */
    public function setHasCurrencySignBeforeValue($hasCurrencySignBeforeValue)
    {
        return $this->setProperty('hasCurrencySignBeforeValue', $hasCurrencySignBeforeValue, 'boolean');
    }

    /**
     * @return boolean Optional: Display currency before or after value. Ignore this flag if you have the correct user locale preference. 
     */
    public function getHasCurrencySignBeforeValue()
    {
        return $this->hasCurrencySignBeforeValue;
    }

    /**
     * @param boolean $isDefault Optional: Flag default currency. True, if this is the default currency. Exact one currency must be marked as default. 
     * @return \jtl\Connector\Model\Currency
     */
    public function setIsDefault($isDefault)
    {
        return $this->setProperty('isDefault', $isDefault, 'boolean');
    }

    /**
     * @return boolean Optional: Flag default currency. True, if this is the default currency. Exact one currency must be marked as default. 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param string $iso 
     * @return \jtl\Connector\Model\Currency
     */
    public function setIso($iso)
    {
        return $this->setProperty('iso', $iso, 'string');
    }

    /**
     * @return string 
     */
    public function getIso()
    {
        return $this->iso;
    }

    /**
     * @param string $name Currency name
     * @return \jtl\Connector\Model\Currency
     */
    public function setName($name)
    {
        return $this->setProperty('name', $name, 'string');
    }

    /**
     * @return string Currency name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $nameHtml Optional HTML name e.g. "&euro;"
     * @return \jtl\Connector\Model\Currency
     */
    public function setNameHtml($nameHtml)
    {
        return $this->setProperty('nameHtml', $nameHtml, 'string');
    }

    /**
     * @return string Optional HTML name e.g. "&euro;"
     */
    public function getNameHtml()
    {
        return $this->nameHtml;
    }
}
