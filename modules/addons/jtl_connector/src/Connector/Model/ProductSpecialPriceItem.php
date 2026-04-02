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
 * special price properties to define a net price for a customerGroup.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class ProductSpecialPriceItem extends DataModel
{
    /**
     * @var Identity Reference to customerGroup
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("customerGroupId")
     * @Serializer\Accessor(getter="getCustomerGroupId",setter="setCustomerGroupId")
     */
    protected $customerGroupId = null;

    /**
     * @var Identity Reference to productSpecialPrice
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productSpecialPriceId")
     * @Serializer\Accessor(getter="getProductSpecialPriceId",setter="setProductSpecialPriceId")
     */
    protected $productSpecialPriceId = null;

    /**
     * @var double net price value
     * @Serializer\Type("double")
     * @Serializer\SerializedName("priceNet")
     * @Serializer\Accessor(getter="getPriceNet",setter="setPriceNet")
     */
    protected $priceNet = 0.0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->customerGroupId = new Identity();
        $this->productSpecialPriceId = new Identity();
    }

    /**
     * @param Identity $customerGroupId Reference to customerGroup
     * @return \jtl\Connector\Model\ProductSpecialPriceItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setCustomerGroupId(Identity $customerGroupId)
    {
        return $this->setProperty('customerGroupId', $customerGroupId, 'Identity');
    }

    /**
     * @return Identity Reference to customerGroup
     */
    public function getCustomerGroupId()
    {
        return $this->customerGroupId;
    }

    /**
     * @param Identity $productSpecialPriceId Reference to productSpecialPrice
     * @return \jtl\Connector\Model\ProductSpecialPriceItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductSpecialPriceId(Identity $productSpecialPriceId)
    {
        return $this->setProperty('productSpecialPriceId', $productSpecialPriceId, 'Identity');
    }

    /**
     * @return Identity Reference to productSpecialPrice
     */
    public function getProductSpecialPriceId()
    {
        return $this->productSpecialPriceId;
    }

    /**
     * @param double $priceNet net price value
     * @return \jtl\Connector\Model\ProductSpecialPriceItem
     */
    public function setPriceNet($priceNet)
    {
        return $this->setProperty('priceNet', $priceNet, 'double');
    }

    /**
     * @return double net price value
     */
    public function getPriceNet()
    {
        return $this->priceNet;
    }
}
