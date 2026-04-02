<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

class Money
{
    private function __construct()
    {
    }

    public static function AsGross($net, $vat)
    {
        $vat = doubleval($vat);
        if ($vat <= 0) {
            return $net;
        }
        
        return $net * ($vat / 100 + 1);
    }

    public static function AsNet($gross, $vat)
    {
        $vat = doubleval($vat);
        if ($vat <= 0) {
            return $gross;
        }

        return $gross / ($vat / 100 + 1);
    }
}
