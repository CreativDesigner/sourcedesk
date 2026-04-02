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
class Payment extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('customerOrderId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('billingInfo', 'string', '', false, false, false),
            new PropertyInfo('creationDate', 'DateTime', null, false, false, false),
            new PropertyInfo('paymentModuleCode', 'string', '', false, false, false),
            new PropertyInfo('totalSum', 'double', 0.0, false, false, false),
            new PropertyInfo('transactionId', 'string', '', false, false, false),
        );
    }

    public function isMain()
    {
        return true;
    }
}
