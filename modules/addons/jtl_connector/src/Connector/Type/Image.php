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
class Image extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('foreignKey', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('filename', 'string', '', false, false, false),
            new PropertyInfo('relationType', 'ImageRelationType', '', false, false, false),
            new PropertyInfo('remoteUrl', 'string', '', false, false, false),
            new PropertyInfo('name', 'string', '', false, false, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\ImageI18n', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
