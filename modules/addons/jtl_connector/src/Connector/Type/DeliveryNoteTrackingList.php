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
class DeliveryNoteTrackingList extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('name', 'string', '', false, false, false),
            new PropertyInfo('codes', 'string', null, false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
