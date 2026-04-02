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
class ConfigItem extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('configGroupId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('productId', 'Identity', null, false, true, false),
            new PropertyInfo('ignoreMultiplier', 'integer', 0, false, false, false),
            new PropertyInfo('inheritProductName', 'boolean', false, false, false, false),
            new PropertyInfo('inheritProductPrice', 'boolean', false, false, false, false),
            new PropertyInfo('initialQuantity', 'double', 0.0, false, false, false),
            new PropertyInfo('isPreSelected', 'boolean', false, false, false, false),
            new PropertyInfo('isRecommended', 'boolean', false, false, false, false),
            new PropertyInfo('maxQuantity', 'double', 0.0, false, false, false),
            new PropertyInfo('minQuantity', 'double', 0.0, false, false, false),
            new PropertyInfo('showDiscount', 'boolean', false, false, false, false),
            new PropertyInfo('showSurcharge', 'boolean', false, false, false, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('type', 'integer', 0, false, false, false),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\ConfigItemI18n', null, false, false, true),
            new PropertyInfo('prices', '\jtl\Connector\Model\ConfigItemPrice', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
