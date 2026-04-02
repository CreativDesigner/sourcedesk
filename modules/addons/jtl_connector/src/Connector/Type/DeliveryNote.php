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
class DeliveryNote extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('customerOrderId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('creationDate', 'DateTime', null, false, false, false),
            new PropertyInfo('isFulfillment', 'boolean', false, false, false, false),
            new PropertyInfo('note', 'string', '', false, false, false),
            new PropertyInfo('items', '\jtl\Connector\Model\DeliveryNoteItem', null, false, false, true),
            new PropertyInfo('trackingLists', '\jtl\Connector\Model\DeliveryNoteTrackingList', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
