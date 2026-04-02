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
 * Specific is defined as a characteristic product attribute Like "color". Specifics can be used for after-search-filtering. 
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class Specific extends DataModel
{
    /**
     * @var Identity Unique specific id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var boolean Optional: Global specific means the specific can be used like a category (e.g. show all red products in shop)
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isGlobal")
     * @Serializer\Accessor(getter="getIsGlobal",setter="setIsGlobal")
     */
    protected $isGlobal = false;

    /**
     * @var integer Optional sort number
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("sort")
     * @Serializer\Accessor(getter="getSort",setter="setSort")
     */
    protected $sort = 0;

    /**
     * @var string Specific type (radio, dropdown, image...)
     * @Serializer\Type("string")
     * @Serializer\SerializedName("type")
     * @Serializer\Accessor(getter="getType",setter="setType")
     */
    protected $type = '';

    /**
     * @var \jtl\Connector\Model\SpecificI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\SpecificI18n>")
     * @Serializer\SerializedName("i18ns")
     * @Serializer\AccessType("reflection")
     */
    protected $i18ns = array();

    /**
     * @var \jtl\Connector\Model\SpecificValue[]
     * @Serializer\Type("array<jtl\Connector\Model\SpecificValue>")
     * @Serializer\SerializedName("values")
     * @Serializer\AccessType("reflection")
     */
    protected $values = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
    }

    /**
     * @param Identity $id Unique specific id
     * @return \jtl\Connector\Model\Specific
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique specific id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $isGlobal Optional: Global specific means the specific can be used like a category (e.g. show all red products in shop)
     * @return \jtl\Connector\Model\Specific
     */
    public function setIsGlobal($isGlobal)
    {
        return $this->setProperty('isGlobal', $isGlobal, 'boolean');
    }

    /**
     * @return boolean Optional: Global specific means the specific can be used like a category (e.g. show all red products in shop)
     */
    public function getIsGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * @param integer $sort Optional sort number
     * @return \jtl\Connector\Model\Specific
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
     * @param string $type Specific type (radio, dropdown, image...)
     * @return \jtl\Connector\Model\Specific
     */
    public function setType($type)
    {
        return $this->setProperty('type', $type, 'string');
    }

    /**
     * @return string Specific type (radio, dropdown, image...)
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \jtl\Connector\Model\SpecificI18n $i18n
     * @return \jtl\Connector\Model\Specific
     */
    public function addI18n(\jtl\Connector\Model\SpecificI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\Specific
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\SpecificI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\Specific
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\SpecificValue $value
     * @return \jtl\Connector\Model\Specific
     */
    public function addValue(\jtl\Connector\Model\SpecificValue $value)
    {
        $this->values[] = $value;
        return $this;
    }
    
    /**
     * @param array $values
     * @return \jtl\Connector\Model\Specific
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\SpecificValue[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return \jtl\Connector\Model\Specific
     */
    public function clearValues()
    {
        $this->values = array();
        return $this;
    }
}
