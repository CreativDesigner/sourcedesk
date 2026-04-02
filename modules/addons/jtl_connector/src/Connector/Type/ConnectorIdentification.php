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
class ConnectorIdentification extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('endpointVersion', 'string', '', false, false, false),
            new PropertyInfo('platformName', 'string', '', false, false, false),
            new PropertyInfo('platformVersion', 'string', '', false, false, false),
            new PropertyInfo('protocolVersion', 'integer', '', false, false, false),
            new PropertyInfo('serverInfo', '\jtl\Connector\Model\CrossSellingItem', null, false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
