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
class GlobalData extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('configGroups', '\jtl\Connector\Model\ConfigGroup', null, false, false, true),
            new PropertyInfo('configItems', '\jtl\Connector\Model\ConfigItem', null, false, false, true),
            new PropertyInfo('crossSellingGroups', '\jtl\Connector\Model\CrossSellingGroup', null, false, false, true),
            new PropertyInfo('currencies', '\jtl\Connector\Model\Currency', null, false, false, true),
            new PropertyInfo('customerGroups', '\jtl\Connector\Model\CustomerGroup', null, false, false, true),
            new PropertyInfo('languages', '\jtl\Connector\Model\Language', null, false, false, true),
            new PropertyInfo('measurementUnits', '\jtl\Connector\Model\MeasurementUnit', null, false, false, true),
            new PropertyInfo('productTypes', '\jtl\Connector\Model\ProductType', null, false, false, true),
            new PropertyInfo('shippingClasses', '\jtl\Connector\Model\ShippingClass', null, false, false, true),
            new PropertyInfo('shippingMethods', '\jtl\Connector\Model\ShippingMethod', null, false, false, true),
            new PropertyInfo('taxRates', '\jtl\Connector\Model\TaxRate', null, false, false, true),
            new PropertyInfo('units', '\jtl\Connector\Model\Unit', null, false, false, true),
            new PropertyInfo('warehouses', '\jtl\Connector\Model\Warehouse', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
