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
class ProductPriceItem extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('productPriceId', 'Identity', null, true, true, false),
            new PropertyInfo('netPrice', 'double', 0.0, false, false, false),
            new PropertyInfo('quantity', 'integer', 0, false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
