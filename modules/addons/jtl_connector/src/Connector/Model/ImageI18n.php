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
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 *
 * @Serializer\AccessType("public_method")
 */
class ImageI18n extends DataModel
{
    /**
     * @var Identity
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("imageId")
     * @Serializer\Accessor(getter="getImageId",setter="setImageId")
     */
    protected $imageId = null;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("altText")
     * @Serializer\Accessor(getter="getAltText",setter="setAltText")
     */
    protected $altText = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("languageISO")
     * @Serializer\Accessor(getter="getLanguageISO",setter="setLanguageISO")
     */
    protected $languageISO = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = new Identity();
        $this->imageId = new Identity();
    }

    /**
     * @param Identity $id
     * @return \jtl\Connector\Model\ImageI18n
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
     * @param Identity $imageId
     * @return \jtl\Connector\Model\ImageI18n
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setImageId(Identity $imageId)
    {
        return $this->setProperty('imageId', $imageId, 'Identity');
    }

    /**
     * @return Identity
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * @param string $altText
     * @return \jtl\Connector\Model\ImageI18n
     */
    public function setAltText($altText)
    {
        return $this->setProperty('altText', $altText, 'string');
    }

    /**
     * @return string
     */
    public function getAltText()
    {
        return $this->altText;
    }

    /**
     * @param string $languageISO
     * @return \jtl\Connector\Model\ImageI18n
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
}