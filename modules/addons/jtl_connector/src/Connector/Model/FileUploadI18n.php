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
class FileUploadI18n extends DataModel
{
    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("description")
     * @Serializer\Accessor(getter="getDescription",setter="setDescription")
     */
    protected $description = '';

    /**
     * @var integer 
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("fileUploadId")
     * @Serializer\Accessor(getter="getFileUploadId",setter="setFileUploadId")
     */
    protected $fileUploadId = 0;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("languageISO")
     * @Serializer\Accessor(getter="getLanguageISO",setter="setLanguageISO")
     */
    protected $languageISO = '';

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     * @Serializer\Accessor(getter="getName",setter="setName")
     */
    protected $name = '';


    /**
     * @param string $description 
     * @return \jtl\Connector\Model\FileUploadI18n
     */
    public function setDescription($description)
    {
        return $this->setProperty('description', $description, 'string');
    }

    /**
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param integer $fileUploadId 
     * @return \jtl\Connector\Model\FileUploadI18n
     */
    public function setFileUploadId($fileUploadId)
    {
        return $this->setProperty('fileUploadId', $fileUploadId, 'integer');
    }

    /**
     * @return integer 
     */
    public function getFileUploadId()
    {
        return $this->fileUploadId;
    }

    /**
     * @param string $languageISO 
     * @return \jtl\Connector\Model\FileUploadI18n
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
     * @param string $name 
     * @return \jtl\Connector\Model\FileUploadI18n
     */
    public function setName($name)
    {
        return $this->setProperty('name', $name, 'string');
    }

    /**
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
}
