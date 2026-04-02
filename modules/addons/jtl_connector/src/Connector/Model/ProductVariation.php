<?php
/**
 * @copyright 2010-2015 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Product
 */

namespace jtl\Connector\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * ProductVariation Model. Each product defines its own variations, that means  variations are not global  in contrast to specifics.
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 *
 * @Serializer\AccessType("public_method")
 */
class ProductVariation extends DataModel
{
    /**
     * @var string - Multiple values displayed as radio buttons.
     */
    const TYPE_RADIO = 'radio';
    /**
     * @var string - Multiple values displayed as drop down.
     */
    const TYPE_SELECT = 'select';
    /**
     * @var string - boxes showing a text
     */
    const TYPE_TEXTBOX = 'textbox';
    /**
     * @var string - Optional text input (no values)
     */
    const TYPE_FREE_TEXT = 'freetext';
    /**
     * @var string - Required text input (no values)
     */
    const TYPE_FREE_TEXT_OBLIGATORY = 'obligatory_freetext';
    /**
     * @var string - boxes showing a color
     */
    const TYPE_IMAGE_SWATCHES = 'image_swatches';

    /**
     * @var Identity Unique productVariation id
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
     * @var integer Optional sort number
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("sort")
     * @Serializer\Accessor(getter="getSort",setter="setSort")
     */
    protected $sort = 0;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("type")
     * @Serializer\Accessor(getter="getType",setter="setType")
     */
    protected $type = '';

    /**
     * @var \jtl\Connector\Model\ProductVariationI18n[]
     * @Serializer\Type("array<jtl\Connector\Model\ProductVariationI18n>")
     * @Serializer\SerializedName("i18ns")
     * @Serializer\AccessType("reflection")
     */
    protected $i18ns = array();

    /**
     * @var \jtl\Connector\Model\ProductVariationInvisibility[]
     * @Serializer\Type("array<jtl\Connector\Model\ProductVariationInvisibility>")
     * @Serializer\SerializedName("invisibilities")
     * @Serializer\AccessType("reflection")
     */
    protected $invisibilities = array();

    /**
     * @var \jtl\Connector\Model\ProductVariationValue[]
     * @Serializer\Type("array<jtl\Connector\Model\ProductVariationValue>")
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
        $this->productId = new Identity();
    }

    /**
     * @param Identity $id Unique productVariation id
     * @return \jtl\Connector\Model\ProductVariation
     * @throws \InvalidArgumentException if the provided argument is not of type 'Identity'.
     */
    public function setId(Identity $id)
    {
        return $this->setProperty('id', $id, 'Identity');
    }

    /**
     * @return Identity Unique productVariation id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Identity $productId Reference to product
     * @return \jtl\Connector\Model\ProductVariation
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
     * @param integer $sort Optional sort number
     * @return \jtl\Connector\Model\ProductVariation
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
     * @param string $type
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function setType($type)
    {
        return $this->setProperty('type', $type, 'string');
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \jtl\Connector\Model\ProductVariationI18n $i18n
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function addI18n(\jtl\Connector\Model\ProductVariationI18n $i18n)
    {
        $this->i18ns[] = $i18n;
        return $this;
    }

    /**
     * @param array $i18ns
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function setI18ns(array $i18ns)
    {
        $this->i18ns = $i18ns;
        return $this;
    }

    /**
     * @return \jtl\Connector\Model\ProductVariationI18n[]
     */
    public function getI18ns()
    {
        return $this->i18ns;
    }

    /**
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function clearI18ns()
    {
        $this->i18ns = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ProductVariationInvisibility $invisibility
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function addInvisibility(\jtl\Connector\Model\ProductVariationInvisibility $invisibility)
    {
        $this->invisibilities[] = $invisibility;
        return $this;
    }

    /**
     * @param array $invisibilities
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function setInvisibilities(array $invisibilities)
    {
        $this->invisibilities = $invisibilities;
        return $this;
    }

    /**
     * @return \jtl\Connector\Model\ProductVariationInvisibility[]
     */
    public function getInvisibilities()
    {
        return $this->invisibilities;
    }

    /**
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function clearInvisibilities()
    {
        $this->invisibilities = array();
        return $this;
    }

    /**
     * @param \jtl\Connector\Model\ProductVariationValue $value
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function addValue(\jtl\Connector\Model\ProductVariationValue $value)
    {
        $this->values[] = $value;
        return $this;
    }

    /**
     * @param array $values
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return \jtl\Connector\Model\ProductVariationValue[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return \jtl\Connector\Model\ProductVariation
     */
    public function clearValues()
    {
        $this->values = array();
        return $this;
    }
}
