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
 * File upload properties. 
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 * 
 * @Serializer\AccessType("public_method")
 */
class FileUpload extends DataModel
{
    /**
     * @var Identity Unique fileUpload id
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("id")
     * @Serializer\Accessor(getter="getId",setter="setId")
     */
    protected $id = null;

    /**
     * @var Identity Reference to product
     * @Serializer\Type("jtl\Connector\Model\Identity")
     * @Serializer\SerializedName("productId")
     * @Serializer\Accessor(getter="getProductId",setter="setProductId")
     */
    protected $productId = null;

    /**
     * @var string 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("fileType")
     * @Serializer\Accessor(getter="getFileType",setter="setFileType")
     */
    protected $fileType = '';

    /**
     * @var boolean 
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isRequired")
     * @Serializer\Accessor(getter="getIsRequired",setter="setIsRequired")
     */
    protected $isRequired = false;

    /**
     * @var \jtl\Connector\Model\FileUploadI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\FileUploadI18n>")
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
        $this->productId = new Identity();
    }

    /**
     * @param Identity $id Unique fileUpload id
     * @return \jtl\Connector\Model\FileUpload
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique fileUpload id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Identity $productId Reference to product
     * @return \jtl\Connector\Model\FileUpload
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setProductId(Identity $productId)
    {
        return $this->setProperty('productId', $productId, 'Identity');
    }

    /**
     * @return Identity Reference to product
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $fileType 
     * @return \jtl\Connector\Model\FileUpload
     */
    public function setFileType($fileType)
    {
        return $this->setProperty('fileType', $fileType, 'string');
    }

    /**
     * @return string 
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param boolean $isRequired 
     * @return \jtl\Connector\Model\FileUpload
     */
    public function setIsRequired($isRequired)
    {
        return $this->setProperty('isRequired', $isRequired, 'boolean');
    }

    /**
     * @return boolean 
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * @param \jtl\Connector\Model\FileUploadI18n $i18n
     * @return \jtl\Connector\Model\FileUpload
     */
    public function addI18n(\jtl\Connector\Model\FileUploadI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }
    
    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\FileUpload
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }
    
    /**
     * @return \jtl\Connector\Model\FileUploadI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\FileUpload
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }
}
