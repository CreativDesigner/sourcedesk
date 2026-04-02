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
 * Customer group model.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class CustomerGroup extends DataModel
{
    /**
     * @var Identity Unique customerGroup id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var boolean Optional: Show net prices default instead of gross prices
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("applyNetPrice")
     * @Serializer\Accessor(getter="getApplyNetPrice",setter="setApplyNetPrice")
     */
    protected $applyNetPrice = false;

    /**
     * @var double Optional percentual discount on all products. Negative Value means surcharge. 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("discount")
     * @Serializer\Accessor(getter="getDiscount",setter="setDiscount")
     */
    protected $discount = 0.0;

    /**
     * @var boolean Optional: Flag default customer group
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isDefault")
     * @Serializer\Accessor(getter="getIsDefault",setter="setIsDefault")
     */
    protected $isDefault = false;

    /**
     * @var \jtl\Connector\Model\CustomerGroupAttr[]
     * @Serializer\Type("array<jtl\Connector\Model\CustomerGroupAttr>")
     * @Serializer\SerializedName("attributes")
     * @Serializer\AccessType("reflection")
     */
    protected $attributes = array();

    /**
     * @var \jtl\Connector\Model\CustomerGroupI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\CustomerGroupI18n>")
     * @Serializer\SerializedName("i18ns")
     * @Serializer\AccessType("reflection")
     */
    protected $i18ns = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
    }

    /**
     * @param Identity $id Unique customerGroup id
     * @return \jtl\Connector\Model\CustomerGroup
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique customerGroup id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $applyNetPrice Optional: Show net prices default instead of gross prices
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function setApplyNetPrice($applyNetPrice)
    {
        return $this->setProperty('applyNetPrice', $applyNetPrice, 'boolean');
    }

    /**
     * @return boolean Optional: Show net prices default instead of gross prices
     */
    public function getApplyNetPrice()
    {
        return $this->applyNetPrice;
    }

    /**
     * @param double $discount Optional percentual discount on all products. Negative Value means surcharge. 
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function setDiscount($discount)
    {
        return $this->setProperty('discount', $discount, 'double');
    }

    /**
     * @return double Optional percentual discount on all products. Negative Value means surcharge. 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param boolean $isDefault Optional: Flag default customer group
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function setIsDefault($isDefault)
    {
        return $this->setProperty('isDefault', $isDefault, 'boolean');
    }

    /**
     * @return boolean Optional: Flag default customer group
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param \jtl\Connector\Model\CustomerGroupAttr $attribute
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function addAttribute(\jtl\Connector\Model\CustomerGroupAttr $attribute)
    {
        $this->attributes[] = $attribute;
        return $this;
    }
    
    /**
     * @param array $attributes
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\CustomerGroupAttr[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function clearAttributes()
    {
        $this->attributes = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\CustomerGroupI18n $i18n
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function addI18n(\jtl\Connector\Model\CustomerGroupI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\CustomerGroupI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\CustomerGroup
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
