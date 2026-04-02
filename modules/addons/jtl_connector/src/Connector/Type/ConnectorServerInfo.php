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
class ConnectorServerInfo extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('memoryLimit', 'integer', '', false, false, false),
            new PropertyInfo('executionTime', 'integer', '', false, false, false),
            new PropertyInfo('postMaxSize', 'integer', '', false, false, false),
            new PropertyInfo('uploadMaxFilesize', 'integer', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}