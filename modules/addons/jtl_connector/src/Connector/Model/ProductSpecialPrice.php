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
 * Special product price properties to specify date and stock limits.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class ProductSpecialPrice extends DataModel
{
    /**
     * @var Identity Unique productSpecialPrice id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity Reference to product
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productId")
     * @Serializer\Accessor(getter="getProductId",setter="setProductId")
     */
    protected $productId = null;

    /**
     * @var DateTime Optional: Activate special price from date
     * @Serializer\Type("DateTime")
     * @Serializer\SerializedName("activeFromDate")
     * @Serializer\Accessor(getter="getActiveFromDate",setter="setActiveFromDate")
     */
    protected $activeFromDate = null;

    /**
     * @var DateTime Optional: Special price active until date
     * @Serializer\Type("DateTime")
     * @Serializer\SerializedName("activeUntilDate")
     * @Serializer\Accessor(getter="getActiveUntilDate",setter="setActiveUntilDate")
     */
    protected $activeUntilDate = null;

    /**
     * @var boolean Optional: Consider activeFrom/activeUntil date range. If true, specialPrice will get active from activeFrom-date and will stop after activeUntil-date.
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("considerDateLimit")
     * @Serializer\Accessor(getter="getConsiderDateLimit",setter="setConsiderDateLimit")
     */
    protected $considerDateLimit = false;

    /**
     * @var boolean Optional: Consider stockLimit value. If true, specialPrice will be only active until product stockLevel is greater or equal stockLimit.
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("considerStockLimit")
     * @Serializer\Accessor(getter="getConsiderStockLimit",setter="setConsiderStockLimit")
     */
    protected $considerStockLimit = false;

    /**
     * @var boolean Special price is active? Default true, to activate specialPrice. Special price can still be inactivated, if date or stock Limitations do not match. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isActive")
     * @Serializer\Accessor(getter="getIsActive",setter="setIsActive")
     */
    protected $isActive = false;

    /**
     * @var integer Optional: SpecialPrice active until stock level quantity
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("stockLimit")
     * @Serializer\Accessor(getter="getStockLimit",setter="setStockLimit")
     */
    protected $stockLimit = 0;

    /**
     * @var \jtl\Connector\Model\ProductSpecialPriceItem[]
     * @Serializer\Type("array<jtl\Connector\Model\ProductSpecialPriceItem>")
     * @Serializer\SerializedName("items")
     * @Serializer\AccessType("reflection")
     */
    protected $items = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
        $this->productId = new Identity();
    }

    /**
     * @param Identity $id Unique productSpecialPrice id
     * @return \jtl\Connector\Model\ProductSpecialPrice
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique productSpecialPrice id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Identity $productId Reference to product
     * @return \jtl\Connector\Model\ProductSpecialPrice
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
     * @param DateTime $activeFromDate Optional: Activate special price from date
     * @return \jtl\Connector\Model\ProductSpecialPrice
     * @throws \InvalidArgumentException if the provided argument is not of type 'DateTime'.
     */
    public function setActiveFromDate(DateTime $activeFromDate = null)
    {
        return $this->setProperty('activeFromDate', $activeFromDate, 'DateTime');
    }

    /**
     * @return DateTime Optional: Activate special price from date
     */
    public function getActiveFromDate()
    {
        return $this->activeFromDate;
    }

    /**
     * @param DateTime $activeUntilDate Optional: Special price active until date
     * @return \jtl\Connector\Model\ProductSpecialPrice
     * @throws \InvalidArgumentException if the provided argument is not of type 'DateTime'.
     */
    public function setActiveUntilDate(DateTime $activeUntilDate = null)
    {
        return $this->setProperty('activeUntilDate', $activeUntilDate, 'DateTime');
    }

    /**
     * @return DateTime Optional: Special price active until date
     */
    public function getActiveUntilDate()
    {
        return $this->activeUntilDate;
    }

    /**
     * @param boolean $considerDateLimit Optional: Consider activeFrom/activeUntil date range. If true, specialPrice will get active from activeFrom-date and will stop after activeUntil-date.
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function setConsiderDateLimit($considerDateLimit)
    {
        return $this->setProperty('considerDateLimit', $considerDateLimit, 'boolean');
    }

    /**
     * @return boolean Optional: Consider activeFrom/activeUntil date range. If true, specialPrice will get active from activeFrom-date and will stop after activeUntil-date.
     */
    public function getConsiderDateLimit()
    {
        return $this->considerDateLimit;
    }

    /**
     * @param boolean $considerStockLimit Optional: Consider stockLimit value. If true, specialPrice will be only active until product stockLevel is greater or equal stockLimit.
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function setConsiderStockLimit($considerStockLimit)
    {
        return $this->setProperty('considerStockLimit', $considerStockLimit, 'boolean');
    }

    /**
     * @return boolean Optional: Consider stockLimit value. If true, specialPrice will be only active until product stockLevel is greater or equal stockLimit.
     */
    public function getConsiderStockLimit()
    {
        return $this->considerStockLimit;
    }

    /**
     * @param boolean $isActive Special price is active? Default true, to activate specialPrice. Special price can still be inactivated, if date or stock Limitations do not match. 
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function setIsActive($isActive)
    {
        return $this->setProperty('isActive', $isActive, 'boolean');
    }

    /**
     * @return boolean Special price is active? Default true, to activate specialPrice. Special price can still be inactivated, if date or stock Limitations do not match. 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param integer $stockLimit Optional: SpecialPrice active until stock level quantity
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function setStockLimit($stockLimit)
    {
        return $this->setProperty('stockLimit', $stockLimit, 'integer');
    }

    /**
     * @return integer Optional: SpecialPrice active until stock level quantity
     */
    public function getStockLimit()
    {
        return $this->stockLimit;
    }

    /**
     * @param \jtl\Connector\Model\ProductSpecialPriceItem $item
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function addItem(\jtl\Connector\Model\ProductSpecialPriceItem $item)
    {
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * @param array $items
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ProductSpecialPriceItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return \jtl\Connector\Model\ProductSpecialPrice
     */
    public function clearItems()
    {
        $this->items = array();
        return $this;
    }
}
