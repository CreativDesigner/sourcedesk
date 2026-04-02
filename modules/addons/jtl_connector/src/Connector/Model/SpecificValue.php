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
 * Specific value properties to define a new specificValue with a sort number. 
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class SpecificValue extends DataModel
{
    /**
     * @var Identity Unique specificValue id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity Reference to specificId
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("specificId")
     * @Serializer\Accessor(getter="getSpecificId",setter="setSpecificId")
     */
    protected $specificId = null;

    /**
     * @var integer Optional sort number
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("sort")
     * @Serializer\Accessor(getter="getSort",setter="setSort")
     */
    protected $sort = 0;

    /**
     * @var \jtl\Connector\Model\SpecificValueI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\SpecificValueI18n>")
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
        $this->specificId = new Identity();
    }

    /**
     * @param Identity $id Unique specificValue id
     * @return \jtl\Connector\Model\SpecificValue
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique specificValue id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Identity $specificId Reference to specificId
     * @return \jtl\Connector\Model\SpecificValue
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setSpecificId(Identity $specificId)
    {
        return $this->setProperty('specificId', $specificId, 'Identity');
    }

    /**
     * @return Identity Reference to specificId
     */
    public function getSpecificId()
    {
        return $this->specificId;
    }

    /**
     * @param integer $sort Optional sort number
     * @return \jtl\Connector\Model\SpecificValue
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
     * @param \jtl\Connector\Model\SpecificValueI18n $i18n
     * @return \jtl\Connector\Model\SpecificValue
     */
    public function addI18n(\jtl\Connector\Model\SpecificValueI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\SpecificValue
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\SpecificValueI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\SpecificValue
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
