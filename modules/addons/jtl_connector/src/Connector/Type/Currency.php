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
class Currency extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('delimiterCent', 'string', '', false, false, false),
            new PropertyInfo('delimiterThousand', 'string', '', false, false, false),
            new PropertyInfo('factor', 'double', 0.0, false, false, false),
            new PropertyInfo('hasCurrencySignBeforeValue', 'boolean', false, false, false, false),
            new PropertyInfo('isDefault', 'boolean', false, false, false, false),
            new PropertyInfo('iso', 'string', '', false, false, false),
            new PropertyInfo('name', 'string', '', false, false, false),
            new PropertyInfo('nameHtml', 'string', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
