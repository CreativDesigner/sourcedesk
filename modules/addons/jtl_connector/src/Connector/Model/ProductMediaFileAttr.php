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
 * Monolingual mediafile attribute.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class ProductMediaFileAttr extends DataModel
{
    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productMediaFileId")
     * @Serializer\Accessor(getter="getProductMediaFileId",setter="setProductMediaFileId")
     */
    protected $productMediaFileId = null;

    /**
     * @var \jtl\Connector\Model\ProductMediaFileAttrI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\ProductMediaFileAttrI18n>")
     * @Serializer\SerializedName("i18ns")
     * @Serializer\AccessType("reflection")
     */
    protected $i18ns = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productMediaFileId = new Identity();
    }

    /**
     * @param Identity $productMediaFileId 
     * @return \jtl\Connector\Model\ProductMediaFileAttr
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductMediaFileId(Identity $productMediaFileId)
    {
        return $this->setProperty('productMediaFileId', $productMediaFileId, 'Identity');
    }

    /**
     * @return Identity 
     */
    public function getProductMediaFileId()
    {
        return $this->productMediaFileId;
    }

    /**
     * @param \jtl\Connector\Model\ProductMediaFileAttrI18n $i18n
     * @return \jtl\Connector\Model\ProductMediaFileAttr
     */
    public function addI18n(\jtl\Connector\Model\ProductMediaFileAttrI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\ProductMediaFileAttr
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ProductMediaFileAttrI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\ProductMediaFileAttr
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
