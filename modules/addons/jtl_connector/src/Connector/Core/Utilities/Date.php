<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

class Date
{
    // ISO-8601
    public static function map($date, $sourceformat, $targetformat = \DateTime::ISO8601)
    {
        $datetime = \DateTime::createFromFormat($sourceformat, $date);

        if ($datetime !== false) {
            return $datetime->format($targetformat);
        } else {
            return $date;
        }
    }
}
