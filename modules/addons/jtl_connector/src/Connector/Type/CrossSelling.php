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
class CrossSelling extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, false, true, false),
            new PropertyInfo('productId', 'Identity', null, false, true, false),
            new PropertyInfo('items', '\jtl\Connector\Model\CrossSellingItem', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
