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
 * Tax class model (set in JTL-Wawi ERP)
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class TaxClass extends DataModel
{
    /**
     * @var Identity Unique taxClass id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var boolean Optional: Flag default tax class. Default is false. Exact 1 taxClass has to be marked as default. 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isDefault")
     * @Serializer\Accessor(getter="getIsDefault",setter="setIsDefault")
     */
    protected $isDefault = false;

    /**
     * @var string Optional tax class name
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     * @Serializer\Accessor(getter="getName",setter="setName")
     */
    protected $name = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
    }

    /**
     * @param Identity $id Unique taxClass id
     * @return \jtl\Connector\Model\TaxClass
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique taxClass id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $isDefault Optional: Flag default tax class. Default is false. Exact 1 taxClass has to be marked as default. 
     * @return \jtl\Connector\Model\TaxClass
     * @throws \InvalidArgumentException if the provided argument is not of type 'boolean'.
     */
    public function setIsDefault(boolean $isDefault)
    {
        return $this->setProperty('isDefault', $isDefault, 'boolean');
    }

    /**
     * @return boolean Optional: Flag default tax class. Default is false. Exact 1 taxClass has to be marked as default. 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param string $name Optional tax class name
     * @return \jtl\Connector\Model\TaxClass
     */
    public function setName($name)
    {
        return $this->setProperty('name', $name, 'string');
    }

    /**
     * @return string Optional tax class name
     */
    public function getName()
    {
        return $this->name;
    }
}
