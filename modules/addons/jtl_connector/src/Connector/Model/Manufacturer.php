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
 * Manufacturer / brand properties. 
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class Manufacturer extends DataModel
{
    /**
     * @var Identity Unique manufacturer id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var string Manufacturer (brand) name
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     * @Serializer\Accessor(getter="getName",setter="setName")
     */
    protected $name = '';

    /**
     * @var integer Optional sort number
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("sort")
     * @Serializer\Accessor(getter="getSort",setter="setSort")
     */
    protected $sort = 0;

    /**
     * @var string Optional url path e.g. 'Products-manufactured-by-X'
     * @Serializer\Type("string")
     * @Serializer\SerializedName("urlPath")
     * @Serializer\Accessor(getter="getUrlPath",setter="setUrlPath")
     */
    protected $urlPath = '';

    /**
     * @var string Optional manufacturer website URL
     * @Serializer\Type("string")
     * @Serializer\SerializedName("websiteUrl")
     * @Serializer\Accessor(getter="getWebsiteUrl",setter="setWebsiteUrl")
     */
    protected $websiteUrl = '';

    /**
     * @var \jtl\Connector\Model\ManufacturerI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\ManufacturerI18n>")
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
    }

    /**
     * @param Identity $id Unique manufacturer id
     * @return \jtl\Connector\Model\Manufacturer
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique manufacturer id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name Manufacturer (brand) name
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function setName($name)
    {
        return $this->setProperty('name', $name, 'string');
    }

    /**
     * @return string Manufacturer (brand) name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param integer $sort Optional sort number
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function setSort($sort)
    {
        return $this->setProperty('sort', $sort, 'integer');
    }

    /**
     * @return integer Optional sort number
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $urlPath Optional url path e.g. 'Products-manufactured-by-X'
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function setUrlPath($urlPath)
    {
        return $this->setProperty('urlPath', $urlPath, 'string');
    }

    /**
     * @return string Optional url path e.g. 'Products-manufactured-by-X'
     */
    public function getUrlPath()
    {
        return $this->urlPath;
    }

    /**
     * @param string $websiteUrl Optional manufacturer website URL
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function setWebsiteUrl($websiteUrl)
    {
        return $this->setProperty('websiteUrl', $websiteUrl, 'string');
    }

    /**
     * @return string Optional manufacturer website URL
     */
    public function getWebsiteUrl()
    {
        return $this->websiteUrl;
    }

    /**
     * @param \jtl\Connector\Model\ManufacturerI18n $i18n
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function addI18n(\jtl\Connector\Model\ManufacturerI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\ManufacturerI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\Manufacturer
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
