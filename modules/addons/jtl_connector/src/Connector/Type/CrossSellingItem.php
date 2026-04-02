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
class CrossSellingItem extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('crossSellingGroupId', 'Identity', null, false, true, false),
            new PropertyInfo('productIds', '\jtl\Connector\Model\Identity', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
