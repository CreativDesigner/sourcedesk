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
class DeliveryNoteItemInfo extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('batch', 'string', '', false, false, false),
            new PropertyInfo('bestBefore', 'DateTime', null, false, false, false),
            new PropertyInfo('quantity', 'double', 0.0, false, false, false),
            new PropertyInfo('warehouseId', 'integer', 0, false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
