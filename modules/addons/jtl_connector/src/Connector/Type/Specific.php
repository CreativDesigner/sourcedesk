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
class Specific extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('isGlobal', 'boolean', false, false, false, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('type', 'string', '', false, false, false),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\SpecificI18n', null, false, false, true),
            new PropertyInfo('values', '\jtl\Connector\Model\SpecificValue', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
