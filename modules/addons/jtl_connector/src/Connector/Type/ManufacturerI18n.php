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
class ManufacturerI18n extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('manufacturerId', 'Identity', null, true, true, false),
            new PropertyInfo('description', 'string', '', false, false, false),
            new PropertyInfo('languageISO', 'string', '', false, false, false),
            new PropertyInfo('metaDescription', 'string', '', false, false, false),
            new PropertyInfo('metaKeywords', 'string', '', false, false, false),
            new PropertyInfo('titleTag', 'string', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
