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
class MeasurementUnit extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('code', 'string', '', false, false, false),
            new PropertyInfo('displayCode', 'string', '', false, false, false),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\MeasurementUnitI18n', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
