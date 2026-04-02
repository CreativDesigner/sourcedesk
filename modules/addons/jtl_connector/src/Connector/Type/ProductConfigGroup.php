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
class ProductConfigGroup extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('configGroupId', 'Identity', null, false, true, false),
            new PropertyInfo('productId', 'Identity', null, false, true, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
