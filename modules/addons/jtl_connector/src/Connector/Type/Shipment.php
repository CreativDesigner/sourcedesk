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
class Shipment extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('deliveryNoteId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('carrierName', 'string', '', false, false, false),
            new PropertyInfo('creationDate', 'DateTime', null, false, false, false),
            new PropertyInfo('identCode', 'string', '', false, false, false),
            new PropertyInfo('note', 'string', '', false, false, false),
            new PropertyInfo('trackingUrl', 'string', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
