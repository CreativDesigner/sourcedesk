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
class DeliveryNoteItem extends DataModel
{
    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("customerOrderItemId")
     * @Serializer\Accessor(getter="getCustomerOrderItemId",setter="setCustomerOrderItemId")
     */
    protected $customerOrderItemId = null;

    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("deliveryNoteId")
     * @Serializer\Accessor(getter="getDeliveryNoteId",setter="setDeliveryNoteId")
     */
    protected $deliveryNoteId = null;

    /**
     * @var Identity Reference to product
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productId")
     * @Serializer\Accessor(getter="getProductId",setter="setProductId")
     */
    protected $productId = null;

    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var double 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("quantity")
     * @Serializer\Accessor(getter="getQuantity",setter="setQuantity")
     */
    protected $quantity = 0.0;

    /**
     * @var \jtl\Connector\Model\DeliveryNoteItemInfo[]
     * @Serializer\Type("array<jtl\Connector\Model\DeliveryNoteItemInfo>")
     * @Serializer\SerializedName("info")
     * @Serializer\AccessType("reflection")
     */
    protected $info = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
        $this->deliveryNoteId = new Identity();
        $this->productId = new Identity();
        $this->customerOrderItemId = new Identity();
    }

    /**
     * @param Identity $customerOrderItemId 
     * @return \jtl\Connector\Model\DeliveryNoteItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setCustomerOrderItemId(Identity $customerOrderItemId)
    {
        return $this->setProperty('customerOrderItemId', $customerOrderItemId, 'Identity');
    }

    /**
     * @return Identity 
     */
    public function getCustomerOrderItemId()
    {
        return $this->customerOrderItemId;
    }

    /**
     * @param Identity $deliveryNoteId 
     * @return \jtl\Connector\Model\DeliveryNoteItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setDeliveryNoteId(Identity $deliveryNoteId)
    {
        return $this->setProperty('deliveryNoteId', $deliveryNoteId, 'Identity');
    }

    /**
     * @return Identity 
     */
    public function getDeliveryNoteId()
    {
        return $this->deliveryNoteId;
    }

    /**
     * @param Identity $productId Reference to product
     * @return \jtl\Connector\Model\CustomerOrderItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductId(Identity $productId)
    {
        return $this->setProperty('productId', $productId, 'Identity');
    }

    /**
     * @return Identity Reference to product
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param Identity $id 
     * @return \jtl\Connector\Model\DeliveryNoteItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param double $quantity 
     * @return \jtl\Connector\Model\DeliveryNoteItem
     */
    public function setQuantity($quantity)
    {
        return $this->setProperty('quantity', $quantity, 'double');
    }

    /**
     * @return double 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param \jtl\Connector\Model\DeliveryNoteItemInfo $info
     * @return \jtl\Connector\Model\DeliveryNoteItem
     */
    public function addInfo(\jtl\Connector\Model\DeliveryNoteItemInfo $info)
    {
        $this->info[] = $info;
        return $this;
    }
    
    /**
     * @param array $info
     * @return \jtl\Connector\Model\DeliveryNoteItem
     */
    public function setInfo(array $info)
    {
        $this->info = $info;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\DeliveryNoteItemInfo[]
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return \jtl\Connector\Model\DeliveryNoteItem
     */
    public function clearInfo()
    {
        $this->info = array();
        return $this;
    }
}
