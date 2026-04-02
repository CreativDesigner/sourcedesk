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
class DeliveryNoteItem extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('customerOrderItemId', 'Identity', null, false, true, false),
            new PropertyInfo('deliveryNoteId', 'Identity', null, false, true, false),
            new PropertyInfo('productId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('quantity', 'double', 0.0, false, false, false),
            new PropertyInfo('info', '\jtl\Connector\Model\DeliveryNoteItemInfo', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
