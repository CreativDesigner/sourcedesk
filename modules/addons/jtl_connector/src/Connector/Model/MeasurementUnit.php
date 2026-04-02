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
 * Specifies product units like "ml", "l", " cm".
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class MeasurementUnit extends DataModel
{
    /**
     * @var Identity Unit id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var string Optional UCUM-Code, see  http://unitsofmeasure.org/
     * @Serializer\Type("string")
     * @Serializer\SerializedName("code")
     * @Serializer\Accessor(getter="getCode",setter="setCode")
     */
    protected $code = '';

    /**
     * @var string Synonym e.g. 'ml'
     * @Serializer\Type("string")
     * @Serializer\SerializedName("displayCode")
     * @Serializer\Accessor(getter="getDisplayCode",setter="setDisplayCode")
     */
    protected $displayCode = '';

    /**
     * @var \jtl\Connector\Model\MeasurementUnitI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\MeasurementUnitI18n>")
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
     * @param Identity $id Unit id
     * @return \jtl\Connector\Model\MeasurementUnit
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unit id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code Optional UCUM-Code, see  http://unitsofmeasure.org/
     * @return \jtl\Connector\Model\MeasurementUnit
     */
    public function setCode($code)
    {
        return $this->setProperty('code', $code, 'string');
    }

    /**
     * @return string Optional UCUM-Code, see  http://unitsofmeasure.org/
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $displayCode Synonym e.g. 'ml'
     * @return \jtl\Connector\Model\MeasurementUnit
     */
    public function setDisplayCode($displayCode)
    {
        return $this->setProperty('displayCode', $displayCode, 'string');
    }

    /**
     * @return string Synonym e.g. 'ml'
     */
    public function getDisplayCode()
    {
        return $this->displayCode;
    }

    /**
     * @param \jtl\Connector\Model\MeasurementUnitI18n $i18n
     * @return \jtl\Connector\Model\MeasurementUnit
     */
    public function addI18n(\jtl\Connector\Model\MeasurementUnitI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\MeasurementUnit
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\MeasurementUnitI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\MeasurementUnit
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
