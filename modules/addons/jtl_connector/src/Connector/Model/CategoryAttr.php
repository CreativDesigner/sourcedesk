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
 * Localized category attribute
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class CategoryAttr extends DataModel
{
    /**
     * @var Identity Reference to category
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("categoryId")
     * @Serializer\Accessor(getter="getCategoryId",setter="setCategoryId")
     */
    protected $categoryId = null;

    /**
     * @var Identity 
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var boolean 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isCustomProperty")
     * @Serializer\Accessor(getter="getIsCustomProperty",setter="setIsCustomProperty")
     */
    protected $isCustomProperty = false;

    /**
     * @var boolean 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isTranslated")
     * @Serializer\Accessor(getter="getIsTranslated",setter="setIsTranslated")
     */
    protected $isTranslated = false;

    /**
     * @var \jtl\Connector\Model\CategoryAttrI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\CategoryAttrI18n>")
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
        $this->categoryId = new Identity();
    }

    /**
     * @param Identity $categoryId Reference to category
     * @return \jtl\Connector\Model\CategoryAttr
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setCategoryId(Identity $categoryId)
    {
        return $this->setProperty('categoryId', $categoryId, 'Identity');
    }

    /**
     * @return Identity Reference to category
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param Identity $id 
     * @return \jtl\Connector\Model\CategoryAttr
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
     * @param boolean $isCustomProperty 
     * @return \jtl\Connector\Model\CategoryAttr
     */
    public function setIsCustomProperty($isCustomProperty)
    {
        return $this->setProperty('isCustomProperty', $isCustomProperty, 'boolean');
    }

    /**
     * @return boolean 
     */
    public function getIsCustomProperty()
    {
        return $this->isCustomProperty;
    }

    /**
     * @param boolean $isTranslated 
     * @return \jtl\Connector\Model\CategoryAttr
     */
    public function setIsTranslated($isTranslated)
    {
        return $this->setProperty('isTranslated', $isTranslated, 'boolean');
    }

    /**
     * @return boolean 
     */
    public function getIsTranslated()
    {
        return $this->isTranslated;
    }

    /**
     * @param \jtl\Connector\Model\CategoryAttrI18n $i18n
     * @return \jtl\Connector\Model\CategoryAttr
     */
    public function addI18n(\jtl\Connector\Model\CategoryAttrI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\CategoryAttr
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\CategoryAttrI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\CategoryAttr
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
