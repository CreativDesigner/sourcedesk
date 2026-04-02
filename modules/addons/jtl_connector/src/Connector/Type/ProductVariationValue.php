<?php
/**
 * @copyright 2010-2014 JTL-Software GmbH
 * @package jtl\Connector\Type
 */

namespace jtl\Connector\Type;

use \jtl\Connector\Type\PropertyInfo;

/**
 * @access public
 * @package jtl\Connector\Type
 */
class ProductVariationValue extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('productVariationId', 'Identity', null, false, true, false),
            new PropertyInfo('ean', 'string', '', false, false, false),
            new PropertyInfo('extraWeight', 'double', 0.0, false, false, false),
            new PropertyInfo('sku', 'string', '', false, false, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('stockLevel', 'double', 0.0, false, false, false),
            new PropertyInfo('extraCharges', '\jtl\Connector\Model\ProductVariationValueExtraCharge', null, false, false, true),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\ProductVariationValueI18n', null, false, false, true),
            new PropertyInfo('invisibilities', '\jtl\Connector\Model\ProductVariationValueInvisibility', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
