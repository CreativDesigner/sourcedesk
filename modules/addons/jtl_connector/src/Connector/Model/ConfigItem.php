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
 * A config Item that is displayed in a config Group. Config item can reference to a specific productId to inherit price, name and description. 
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class ConfigItem extends DataModel
{
    /**
     * @var Identity Reference to configGroup
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("configGroupId")
     * @Serializer\Accessor(getter="getConfigGroupId",setter="setConfigGroupId")
     */
    protected $configGroupId = null;

    /**
     * @var Identity Unique configItem id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity Optional reference to product
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productId")
     * @Serializer\Accessor(getter="getProductId",setter="setProductId")
     */
    protected $productId = null;

    /**
     * @var integer Optional:Ignore multiplier. If true, quantity of config item will not be increased if product quantity is increased
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("ignoreMultiplier")
     * @Serializer\Accessor(getter="getIgnoreMultiplier",setter="setIgnoreMultiplier")
     */
    protected $ignoreMultiplier = 0;

    /**
     * @var boolean Optional: Inherit product name and description  if productId is set. If true, configItem name will be received from referenced product and configItemI18n name will be ignored. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("inheritProductName")
     * @Serializer\Accessor(getter="getInheritProductName",setter="setInheritProductName")
     */
    protected $inheritProductName = false;

    /**
     * @var boolean Optional: Inherit product price of referenced productId. If true, configItem price will be the same as referenced product price. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("inheritProductPrice")
     * @Serializer\Accessor(getter="getInheritProductPrice",setter="setInheritProductPrice")
     */
    protected $inheritProductPrice = false;

    /**
     * @var double Optional initial / predefined quantity. Default is one (1) quantity piece. 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("initialQuantity")
     * @Serializer\Accessor(getter="getInitialQuantity",setter="setInitialQuantity")
     */
    protected $initialQuantity = 0.0;

    /**
     * @var boolean Optional: Preselect configItem. If true, configItem will be preselected or prechecked.
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isPreSelected")
     * @Serializer\Accessor(getter="getIsPreSelected",setter="setIsPreSelected")
     */
    protected $isPreSelected = false;

    /**
     * @var boolean Optional: Highlight or recommend config item. If true, configItem will be recommended/highlighted. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isRecommended")
     * @Serializer\Accessor(getter="getIsRecommended",setter="setIsRecommended")
     */
    protected $isRecommended = false;

    /**
     * @var double Maximum allowed quantity. Default 0 for no maximum limit. 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("maxQuantity")
     * @Serializer\Accessor(getter="getMaxQuantity",setter="setMaxQuantity")
     */
    protected $maxQuantity = 0.0;

    /**
     * @var double Optional minimum quantity required to add configItem. Default 0 for no minimum quantity. 
     * @Serializer\Type("double")
     * @Serializer\SerializedName("minQuantity")
     * @Serializer\Accessor(getter="getMinQuantity",setter="setMinQuantity")
     */
    protected $minQuantity = 0.0;

    /**
     * @var boolean Optional: Show discount compared to productId price. If true, the discount compared to referenct product price will be shown.
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("showDiscount")
     * @Serializer\Accessor(getter="getShowDiscount",setter="setShowDiscount")
     */
    protected $showDiscount = false;

    /**
     * @var boolean Optional: Show surcharge compared to productId price.
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("showSurcharge")
     * @Serializer\Accessor(getter="getShowSurcharge",setter="setShowSurcharge")
     */
    protected $showSurcharge = false;

    /**
     * @var integer Optional sort order number
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("sort")
     * @Serializer\Accessor(getter="getSort",setter="setSort")
     */
    protected $sort = 0;

    /**
     * @var integer Config item type. 0: Product, 1: Special
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("type")
     * @Serializer\Accessor(getter="getType",setter="setType")
     */
    protected $type = 0;

    /**
     * @var \jtl\Connector\Model\ConfigItemI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\ConfigItemI18n>")
     * @Serializer\SerializedName("i18ns")
     * @Serializer\AccessType("reflection")
     */
    protected $i18ns = array();

    /**
     * @var \jtl\Connector\Model\ConfigItemPrice[]
     * @Serializer\Type("array<jtl\Connector\Model\ConfigItemPrice>")
     * @Serializer\SerializedName("prices")
     * @Serializer\AccessType("reflection")
     */
    protected $prices = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
        $this->configGroupId = new Identity();
        $this->productId = new Identity();
    }

    /**
     * @param Identity $configGroupId Reference to configGroup
     * @return \jtl\Connector\Model\ConfigItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setConfigGroupId(Identity $configGroupId)
    {
        return $this->setProperty('configGroupId', $configGroupId, 'Identity');
    }

    /**
     * @return Identity Reference to configGroup
     */
    public function getConfigGroupId()
    {
        return $this->configGroupId;
    }

    /**
     * @param Identity $id Unique configItem id
     * @return \jtl\Connector\Model\ConfigItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique configItem id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Identity $productId Optional reference to product
     * @return \jtl\Connector\Model\ConfigItem
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductId(Identity $productId)
    {
        return $this->setProperty('productId', $productId, 'Identity');
    }

    /**
     * @return Identity Optional reference to product
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param integer $ignoreMultiplier Optional:Ignore multiplier. If true, quantity of config item will not be increased if product quantity is increased
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setIgnoreMultiplier($ignoreMultiplier)
    {
        return $this->setProperty('ignoreMultiplier', $ignoreMultiplier, 'integer');
    }

    /**
     * @return integer Optional:Ignore multiplier. If true, quantity of config item will not be increased if product quantity is increased
     */
    public function getIgnoreMultiplier()
    {
        return $this->ignoreMultiplier;
    }

    /**
     * @param boolean $inheritProductName Optional: Inherit product name and description  if productId is set. If true, configItem name will be received from referenced product and configItemI18n name will be ignored. 
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setInheritProductName($inheritProductName)
    {
        return $this->setProperty('inheritProductName', $inheritProductName, 'boolean');
    }

    /**
     * @return boolean Optional: Inherit product name and description  if productId is set. If true, configItem name will be received from referenced product and configItemI18n name will be ignored. 
     */
    public function getInheritProductName()
    {
        return $this->inheritProductName;
    }

    /**
     * @param boolean $inheritProductPrice Optional: Inherit product price of referenced productId. If true, configItem price will be the same as referenced product price. 
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setInheritProductPrice($inheritProductPrice)
    {
        return $this->setProperty('inheritProductPrice', $inheritProductPrice, 'boolean');
    }

    /**
     * @return boolean Optional: Inherit product price of referenced productId. If true, configItem price will be the same as referenced product price. 
     */
    public function getInheritProductPrice()
    {
        return $this->inheritProductPrice;
    }

    /**
     * @param double $initialQuantity Optional initial / predefined quantity. Default is one (1) quantity piece. 
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setInitialQuantity($initialQuantity)
    {
        return $this->setProperty('initialQuantity', $initialQuantity, 'double');
    }

    /**
     * @return double Optional initial / predefined quantity. Default is one (1) quantity piece. 
     */
    public function getInitialQuantity()
    {
        return $this->initialQuantity;
    }

    /**
     * @param boolean $isPreSelected Optional: Preselect configItem. If true, configItem will be preselected or prechecked.
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setIsPreSelected($isPreSelected)
    {
        return $this->setProperty('isPreSelected', $isPreSelected, 'boolean');
    }

    /**
     * @return boolean Optional: Preselect configItem. If true, configItem will be preselected or prechecked.
     */
    public function getIsPreSelected()
    {
        return $this->isPreSelected;
    }

    /**
     * @param boolean $isRecommended Optional: Highlight or recommend config item. If true, configItem will be recommended/highlighted. 
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setIsRecommended($isRecommended)
    {
        return $this->setProperty('isRecommended', $isRecommended, 'boolean');
    }

    /**
     * @return boolean Optional: Highlight or recommend config item. If true, configItem will be recommended/highlighted. 
     */
    public function getIsRecommended()
    {
        return $this->isRecommended;
    }

    /**
     * @param double $maxQuantity Maximum allowed quantity. Default 0 for no maximum limit. 
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setMaxQuantity($maxQuantity)
    {
        return $this->setProperty('maxQuantity', $maxQuantity, 'double');
    }

    /**
     * @return double Maximum allowed quantity. Default 0 for no maximum limit. 
     */
    public function getMaxQuantity()
    {
        return $this->maxQuantity;
    }

    /**
     * @param double $minQuantity Optional minimum quantity required to add configItem. Default 0 for no minimum quantity. 
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setMinQuantity($minQuantity)
    {
        return $this->setProperty('minQuantity', $minQuantity, 'double');
    }

    /**
     * @return double Optional minimum quantity required to add configItem. Default 0 for no minimum quantity. 
     */
    public function getMinQuantity()
    {
        return $this->minQuantity;
    }

    /**
     * @param boolean $showDiscount Optional: Show discount compared to productId price. If true, the discount compared to referenct product price will be shown.
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setShowDiscount($showDiscount)
    {
        return $this->setProperty('showDiscount', $showDiscount, 'boolean');
    }

    /**
     * @return boolean Optional: Show discount compared to productId price. If true, the discount compared to referenct product price will be shown.
     */
    public function getShowDiscount()
    {
        return $this->showDiscount;
    }

    /**
     * @param boolean $showSurcharge Optional: Show surcharge compared to productId price.
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setShowSurcharge($showSurcharge)
    {
        return $this->setProperty('showSurcharge', $showSurcharge, 'boolean');
    }

    /**
     * @return boolean Optional: Show surcharge compared to productId price.
     */
    public function getShowSurcharge()
    {
        return $this->showSurcharge;
    }

    /**
     * @param integer $sort Optional sort order number
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setSort($sort)
    {
        return $this->setProperty('sort', $sort, 'integer');
    }

    /**
     * @return integer Optional sort order number
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param integer $type Config item type. 0: Product, 1: Special
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setType($type)
    {
        return $this->setProperty('type', $type, 'integer');
    }

    /**
     * @return integer Config item type. 0: Product, 1: Special
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \jtl\Connector\Model\ConfigItemI18n $i18n
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function addI18n(\jtl\Connector\Model\ConfigItemI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ConfigItemI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ConfigItemPrice $price
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function addPrice(\jtl\Connector\Model\ConfigItemPrice $price)
    {
        $this->prices[] = $price;
        return $this;
    }
    
    /**
     * @param array $prices
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function setPrices(array $prices)
    {
        $this->prices = $prices;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ConfigItemPrice[]
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @return \jtl\Connector\Model\ConfigItem
     */
    public function clearPrices()
    {
        $this->prices = array();
        return $this;
    }
}
