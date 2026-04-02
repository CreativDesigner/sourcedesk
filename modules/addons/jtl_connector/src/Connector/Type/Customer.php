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
class Customer extends DataType
{
    protected function loadProperties()
    {
        return array(
            new PropertyInfo('customerGroupId', 'Identity', null, false, true, false),
            new PropertyInfo('id', 'Identity', null, true, true, false),
            new PropertyInfo('accountCredit', 'double', 0.0, false, false, false),
            new PropertyInfo('birthday', 'DateTime', null, false, false, false),
            new PropertyInfo('city', 'string', '', false, false, false),
            new PropertyInfo('company', 'string', '', false, false, false),
            new PropertyInfo('countryIso', 'string', '', false, false, false),
            new PropertyInfo('creationDate', 'DateTime', null, false, false, false),
            new PropertyInfo('customerNumber', 'string', '', false, false, false),
            new PropertyInfo('deliveryInstruction', 'string', '', false, false, false),
            new PropertyInfo('discount', 'double', 0.0, false, false, false),
            new PropertyInfo('eMail', 'string', '', false, false, false),
            new PropertyInfo('extraAddressLine', 'string', '', false, false, false),
            new PropertyInfo('fax', 'string', '', false, false, false),
            new PropertyInfo('firstName', 'string', '', false, false, false),
            new PropertyInfo('hasCustomerAccount', 'boolean', false, false, false, false),
            new PropertyInfo('hasNewsletterSubscription', 'boolean', false, false, false, false),
            new PropertyInfo('isActive', 'boolean', false, false, false, false),
            new PropertyInfo('languageISO', 'string', '', false, false, false),
            new PropertyInfo('lastName', 'string', '', false, false, false),
            new PropertyInfo('mobile', 'string', '', false, false, false),
            new PropertyInfo('note', 'string', '', false, false, false),
            new PropertyInfo('origin', 'string', '', false, false, false),
            new PropertyInfo('phone', 'string', '', false, false, false),
            new PropertyInfo('salutation', 'string', '', false, false, false),
            new PropertyInfo('state', 'string', '', false, false, false),
            new PropertyInfo('street', 'string', '', false, false, false),
            new PropertyInfo('title', 'string', '', false, false, false),
            new PropertyInfo('vatNumber', 'string', '', false, false, false),
            new PropertyInfo('websiteUrl', 'string', '', false, false, false),
            new PropertyInfo('zipCode', 'string', '', false, false, false),
            new PropertyInfo('attributes', '\jtl\Connector\Model\CustomerAttr', null, false, false, true),
        );
    }

    public function isMain()
    {
        return true;
    }
}
