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
 * Specify cross-sold products that are in a common crossSellingGroup.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class CrossSellingItem extends DataModel
{
    /**
     * @var Identity Reference to crossSellingGroup
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("crossSellingGroupId")
     * @Serializer\Accessor(getter="getCrossSellingGroupId",setter="setCrossSellingGroupId")
     */
    protected $crossSellingGroupId = null;

    /**
     * @var \jtl\Connector\Model\Identity[] Referenced target product ID
     * @Serializer\Type("array<jtl\Connector\Model\Identity>")
     * @Serializer\SerializedName("productIds")
     * @Serializer\AccessType("reflection")
     */
    protected $productIds = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->crossSellingGroupId = new Identity();
    }

    /**
     * @param Identity $crossSellingGroupId Reference to crossSellingGroup
     * @return \jtl\Connector\Model\CrossSellingItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setCrossSellingGroupId(Identity $crossSellingGroupId)
    {
        return $this->setProperty('crossSellingGroupId', $crossSellingGroupId, 'Identity');
    }

    /**
     * @return Identity Reference to crossSellingGroup
     */
    public function getCrossSellingGroupId()
    {
        return $this->crossSellingGroupId;
    }

    /**
     * @param \jtl\Connector\Model\Identity $productId
     * @return \jtl\Connector\Model\CrossSellingItem
     */
    public function addProductId(\jtl\Connector\Model\Identity $productId)
    {
        $this->productIds[] = $productId;
        return $this;
    }
    
    /**
     * @param array $productIds
     * @return \jtl\Connector\Model\CrossSellingItem
     */
    public function setProductIds(array $productIds)
    {
        $this->productIds = $productIds;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\Identity[]
     */
    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * @return \jtl\Connector\Model\CrossSellingItem
     */
    public function clearProductIds()
    {
        $this->productIds = array();
        return $this;
    }
}
