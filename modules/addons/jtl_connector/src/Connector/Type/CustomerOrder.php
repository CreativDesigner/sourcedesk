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
class CustomerOrder extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('customerId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('billingAddress', '\jtl\Connector\Model\CustomerOrderBillingAddress', null, false, false, true),
            new PropertyInfo('carrierName', 'string', '', false, false, false),
            new PropertyInfo('creationDate', 'DateTime', null, false, false, false),
            new PropertyInfo('currencyIso', 'string', '', false, false, false),
            new PropertyInfo('estimatedDeliveryDate', 'DateTime', null, false, false, false),
            new PropertyInfo('languageISO', 'string', '', false, false, false),
            new PropertyInfo('note', 'string', '', false, false, false),
            new PropertyInfo('orderNumber', 'string', '', false, false, false),
            new PropertyInfo('paymentDate', 'DateTime', null, false, false, false),
            new PropertyInfo('paymentInfo', '\jtl\Connector\Model\CustomerOrderPaymentInfo', null, false, false, true),
            new PropertyInfo('paymentModuleCode', 'string', '', false, false, false),
            new PropertyInfo('paymentStatus', 'string', '', false, false, false),
            new PropertyInfo('pui', 'string', '', false, false, false),
            new PropertyInfo('shippingAddress', '\jtl\Connector\Model\CustomerOrderShippingAddress', null, false, false, true),
            new PropertyInfo('shippingDate', 'DateTime', null, false, false, false),
            new PropertyInfo('shippingInfo', 'string', '', false, false, false),
            new PropertyInfo('shippingMethodId', 'Identity', null, false, true, false),
            new PropertyInfo('shippingMethodName', 'string', '', false, false, false),
            new PropertyInfo('status', 'string', '', false, false, false),
            new PropertyInfo('totalSum', 'double', 0.0, false, false, false),
            new PropertyInfo('totalSumGross', 'double', 0.0, false, false, false),
            new PropertyInfo('attributes', '\jtl\Connector\Model\CustomerOrderAttr', null, false, false, true),
            new PropertyInfo('items', '\jtl\Connector\Model\CustomerOrderItem', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
