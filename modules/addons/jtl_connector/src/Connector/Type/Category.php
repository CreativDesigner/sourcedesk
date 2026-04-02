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
class Category extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('parentCategoryId', 'Identity', null, false, true, false),
            new PropertyInfo('isActive', 'boolean', false, false, false, false),
            new PropertyInfo('level', 'integer', 0, false, false, false),
            new PropertyInfo('sort', 'integer', 0, false, false, false),
            new PropertyInfo('attributes', '\jtl\Connector\Model\CategoryAttr', null, false, false, true),
            new PropertyInfo('customerGroups', '\jtl\Connector\Model\CategoryCustomerGroup', null, false, false, true),
            new PropertyInfo('i18ns', '\jtl\Connector\Model\CategoryI18n', null, false, false, true),
            new PropertyInfo('invisibilities', '\jtl\Connector\Model\CategoryInvisibility', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
