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
class TaxZoneCountry extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('taxZoneId', 'Identity', null, false, true, false),
            new PropertyInfo('countryIso', 'string', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
