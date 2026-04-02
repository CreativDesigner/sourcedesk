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
 * Group cross-sold products belonging to different crossSellingGroups together.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class CrossSelling extends DataModel
{
    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity Source product
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productId")
     * @Serializer\Accessor(getter="getProductId",setter="setProductId")
     */
    protected $productId = null;

    /**
     * @var \jtl\Connector\Model\CrossSellingItem[] Referenced cross-sold products grouped by their crossSellingGroup
     * @Serializer\Type("array<jtl\Connector\Model\CrossSellingItem>")
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
     * @param Identity $id 
     * @return \jtl\Connector\Model\CrossSelling
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
     * @param Identity $productId Source product
     * @return \jtl\Connector\Model\CrossSelling
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductId(Identity $productId)
    {
        return $this->setProperty('productId', $productId, 'Identity');
    }

    /**
     * @return Identity Source product
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param \jtl\Connector\Model\CrossSellingItem $item
     * @return \jtl\Connector\Model\CrossSelling
     */
    public function addItem(\jtl\Connector\Model\CrossSellingItem $item)
    {
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * @param array $items
     * @return \jtl\Connector\Model\CrossSelling
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\CrossSellingItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return \jtl\Connector\Model\CrossSelling
     */
    public function clearItems()
    {
        $this->items = array();
        return $this;
    }
}
