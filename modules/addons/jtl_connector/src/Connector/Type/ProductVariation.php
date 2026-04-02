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
class ProductVariation extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('productId', 'Identity', null, false, true, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('type', 'string', '', false, false, false),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\ProductVariationI18n', null, false, false, true),
            new PropertyInfo('invisibilities', '\jtl\Connector\Model\ProductVariationInvisibility', null, false, false, true),
            new PropertyInfo('values', '\jtl\Connector\Model\ProductVariationValue', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
