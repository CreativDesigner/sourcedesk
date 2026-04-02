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
 * customer order item variation
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class CustomerOrderItemVariation extends DataModel
{
    /**
     * @var Identity Reference to customerOrderItem
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("customerOrderItemId")
     * @Serializer\Accessor(getter="getCustomerOrderItemId",setter="setCustomerOrderItemId")
     */
    protected $customerOrderItemId = null;

    /**
     * @var Identity Unique customerOrderItemVariation id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity Reference to productVariation
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productVariationId")
     * @Serializer\Accessor(getter="getProductVariationId",setter="setProductVariationId")
     */
    protected $productVariationId = null;

    /**
     * @var Identity Reference to productVariationValue
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productVariationValueId")
     * @Serializer\Accessor(getter="getProductVariationValueId",setter="setProductVariationValueId")
     */
    protected $productVariationValueId = null;

    /**
     * @var string Optional custom text value for variation 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("freeField")
     * @Serializer\Accessor(getter="getFreeField",setter="setFreeField")
     */
    protected $freeField = '';

    /**
     * @var string Variation name e.g. 'color'
     * @Serializer\Type("string")
     * @Serializer\SerializedName("productVariationName")
     * @Serializer\Accessor(getter="getProductVariationName",setter="setProductVariationName")
     */
    protected $productVariationName = '';

    /**
     * @var double 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("surcharge")
     * @Serializer\Accessor(getter="getSurcharge",setter="setSurcharge")
     */
    protected $surcharge = 0.0;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("valueName")
     * @Serializer\Accessor(getter="getValueName",setter="setValueName")
     */
    protected $valueName = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
        $this->productVariationValueId = new Identity();
        $this->customerOrderItemId = new Identity();
        $this->productVariationId = new Identity();
    }

    /**
     * @param Identity $customerOrderItemId Reference to customerOrderItem
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setCustomerOrderItemId(Identity $customerOrderItemId)
    {
        return $this->setProperty('customerOrderItemId', $customerOrderItemId, 'Identity');
    }

    /**
     * @return Identity Reference to customerOrderItem
     */
    public function getCustomerOrderItemId()
    {
        return $this->customerOrderItemId;
    }

    /**
     * @param Identity $id Unique customerOrderItemVariation id
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique customerOrderItemVariation id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Identity $productVariationId Reference to productVariation
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductVariationId(Identity $productVariationId)
    {
        return $this->setProperty('productVariationId', $productVariationId, 'Identity');
    }

    /**
     * @return Identity Reference to productVariation
     */
    public function getProductVariationId()
    {
        return $this->productVariationId;
    }

    /**
     * @param Identity $productVariationValueId Reference to productVariationValue
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductVariationValueId(Identity $productVariationValueId)
    {
        return $this->setProperty('productVariationValueId', $productVariationValueId, 'Identity');
    }

    /**
     * @return Identity Reference to productVariationValue
     */
    public function getProductVariationValueId()
    {
        return $this->productVariationValueId;
    }

    /**
     * @param string $freeField Optional custom text value for variation 
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     */
    public function setFreeField($freeField)
    {
        return $this->setProperty('freeField', $freeField, 'string');
    }

    /**
     * @return string Optional custom text value for variation 
     */
    public function getFreeField()
    {
        return $this->freeField;
    }

    /**
     * @param string $productVariationName Variation name e.g. 'color'
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     */
    public function setProductVariationName($productVariationName)
    {
        return $this->setProperty('productVariationName', $productVariationName, 'string');
    }

    /**
     * @return string Variation name e.g. 'color'
     */
    public function getProductVariationName()
    {
        return $this->productVariationName;
    }

    /**
     * @param double $surcharge 
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     */
    public function setSurcharge($surcharge)
    {
        return $this->setProperty('surcharge', $surcharge, 'double');
    }

    /**
     * @return double 
     */
    public function getSurcharge()
    {
        return $this->surcharge;
    }

    /**
     * @param string $valueName 
     * @return \jtl\Connector\Model\CustomerOrderItemVariation
     */
    public function setValueName($valueName)
    {
        return $this->setProperty('valueName', $valueName, 'string');
    }

    /**
     * @return string 
     */
    public function getValueName()
    {
        return $this->valueName;
    }
}
