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
 * Global language model
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class Language extends DataModel
{
    /**
     * @var Identity Unique language id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var boolean Flag default language for frontend. Exact 1 language must be marked as default.
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isDefault")
     * @Serializer\Accessor(getter="getIsDefault",setter="setIsDefault")
     */
    protected $isDefault = false;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("languageISO")
     * @Serializer\Accessor(getter="getLanguageISO",setter="setLanguageISO")
     */
    protected $languageISO = '';

    /**
     * @var string English term
     * @Serializer\Type("string")
     * @Serializer\SerializedName("nameEnglish")
     * @Serializer\Accessor(getter="getNameEnglish",setter="setNameEnglish")
     */
    protected $nameEnglish = '';

    /**
     * @var string German term
     * @Serializer\Type("string")
     * @Serializer\SerializedName("nameGerman")
     * @Serializer\Accessor(getter="getNameGerman",setter="setNameGerman")
     */
    protected $nameGerman = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
    }

    /**
     * @param Identity $id Unique language id
     * @return \jtl\Connector\Model\Language
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique language id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $isDefault Flag default language for frontend. Exact 1 language must be marked as default.
     * @return \jtl\Connector\Model\Language
     */
    public function setIsDefault($isDefault)
    {
        return $this->setProperty('isDefault', $isDefault, 'boolean');
    }

    /**
     * @return boolean Flag default language for frontend. Exact 1 language must be marked as default.
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param string $languageISO 
     * @return \jtl\Connector\Model\Language
     */
    public function setLanguageISO($languageISO)
    {
        return $this->setProperty('languageISO', $languageISO, 'string');
    }

    /**
     * @return string 
     */
    public function getLanguageISO()
    {
        return $this->languageISO;
    }

    /**
     * @param string $nameEnglish English term
     * @return \jtl\Connector\Model\Language
     */
    public function setNameEnglish($nameEnglish)
    {
        return $this->setProperty('nameEnglish', $nameEnglish, 'string');
    }

    /**
     * @return string English term
     */
    public function getNameEnglish()
    {
        return $this->nameEnglish;
    }

    /**
     * @param string $nameGerman German term
     * @return \jtl\Connector\Model\Language
     */
    public function setNameGerman($nameGerman)
    {
        return $this->setProperty('nameGerman', $nameGerman, 'string');
    }

    /**
     * @return string German term
     */
    public function getNameGerman()
    {
        return $this->nameGerman;
    }
}
