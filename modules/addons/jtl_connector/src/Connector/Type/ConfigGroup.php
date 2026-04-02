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
class ConfigGroup extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('comment', 'string', '', false, false, false),
            new PropertyInfo('imagePath', 'string', '', false, false, false),
            new PropertyInfo('maximumSelection', 'integer', 0, false, false, false),
            new PropertyInfo('minimumSelection', 'integer', 0, false, false, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('type', 'integer', 0, false, false, false),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\ConfigGroupI18n', null, false, false, true),
        );
    }

    public function isMain()
    {
        return false;
    }
}
