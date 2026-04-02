<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Formatter
 */

namespace jtl\Connector\Formatter;

use \jtl\Connector\Core\Result\Mysql;

class MysqlResultFormatter
{
    public static function format(Mysql $result)
    {
        return sprintf("Mysql Error with message '%s' and code '%s'. Query: '%s'", $result->getError(), $result->getErrno(), $result->getQuery());
    }
}
