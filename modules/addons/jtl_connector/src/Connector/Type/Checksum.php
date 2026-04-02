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
class Checksum extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('foreignKey', 'Identity', null, false, true, false),
            new PropertyInfo('endpoint', 'string', '', false, false, false),
            new PropertyInfo('hasChanged', 'boolean', false, false, false, false),
            new PropertyInfo('host', 'string', '', false, false, false),
            new PropertyInfo('type', 'integer', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
