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
class ProductSpecialPrice extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('productId', 'Identity', null, false, true, false),
            new PropertyInfo('activeFromDate', 'DateTime', null, false, false, false),
            new PropertyInfo('activeUntilDate', 'DateTime', null, false, false, false),
            new PropertyInfo('considerDateLimit', 'boolean', false, false, false, false),
            new PropertyInfo('considerStockLimit', 'boolean', false, false, false, false),
            new PropertyInfo('isActive', 'boolean', false, false, false, false),
            new PropertyInfo('stockLimit', 'integer', 0, false, false, false),
            new PropertyInfo('items', '\jtl\Connector\Model\ProductSpecialPriceItem', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
