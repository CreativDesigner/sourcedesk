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
class CustomerOrderPaymentInfo extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('customerOrderId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('accountHolder', 'string', '', false, false, false),
            new PropertyInfo('accountNumber', 'string', '', false, false, false),
            new PropertyInfo('bankCode', 'string', '', false, false, false),
            new PropertyInfo('bankName', 'string', '', false, false, false),
            new PropertyInfo('bic', 'string', '', false, false, false),
            new PropertyInfo('creditCardExpiration', 'string', '', false, false, false),
            new PropertyInfo('creditCardHolder', 'string', '', false, false, false),
            new PropertyInfo('creditCardNumber', 'string', '', false, false, false),
            new PropertyInfo('creditCardType', 'string', '', false, false, false),
            new PropertyInfo('creditCardVerificationNumber', 'string', '', false, false, false),
            new PropertyInfo('iban', 'string', '', false, false, false),
        );
    }

    public function isMain()
    {
        return false;
    }
}
