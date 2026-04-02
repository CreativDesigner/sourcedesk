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
 * Product to warehouse info association.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class ProductWarehouseInfo extends DataModel
{
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
     * @Serializer\SerializedName("warehouseId")
     * @Serializer\Accessor(getter="getwarehouseId",setter="setwarehouseId")
     */
    protected $warehouseId = null;

    /**
     * @var double Optional product inflow quantity for specified warehouse
     * @Serializer\Type("double")
     * @Serializer\SerializedName("inflowQuantity")
     * @Serializer\Accessor(getter="getInflowQuantity",setter="setInflowQuantity")
     */
    protected $inflowQuantity = 0.0;

    /**
     * @var double 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("stockLevel")
     * @Serializer\Accessor(getter="getstockLevel",setter="setstockLevel")
     */
    protected $stockLevel = 0.0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productId = new Identity();
        $this->warehouseId = new Identity();
    }

    /**
     * @param Identity $productId Reference to product
     * @return \jtl\Connector\Model\ProductWarehouseInfo
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
     * @param Identity $warehouseId 
     * @return \jtl\Connector\Model\ProductWarehouseInfo
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setwarehouseId(Identity $warehouseId)
    {
        return $this->setProperty('warehouseId', $warehouseId, 'Identity');
    }

    /**
     * @return Identity 
     */
    public function getwarehouseId()
    {
        return $this->warehouseId;
    }

    /**
     * @param double $inflowQuantity Optional product inflow quantity for specified warehouse
     * @return \jtl\Connector\Model\ProductWarehouseInfo
     */
    public function setInflowQuantity($inflowQuantity)
    {
        return $this->setProperty('inflowQuantity', $inflowQuantity, 'double');
    }

    /**
     * @return double Optional product inflow quantity for specified warehouse
     */
    public function getInflowQuantity()
    {
        return $this->inflowQuantity;
    }

    /**
     * @param double $stockLevel 
     * @return \jtl\Connector\Model\ProductWarehouseInfo
     */
    public function setstockLevel($stockLevel)
    {
        return $this->setProperty('stockLevel', $stockLevel, 'double');
    }

    /**
     * @return double 
     */
    public function getstockLevel()
    {
        return $this->stockLevel;
    }
}
