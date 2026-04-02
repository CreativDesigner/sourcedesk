<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Formatter
 */

namespace jtl\Connector\Formatter;

use jtl\Connector\Application\Error\ErrorHandler;

class ExceptionFormatter
{
    /**
     * @param \Exception|\Throwable $exc
     * @param string $message
     * @return string
     */
    public static function format($e, $message = '')
    {
        return ErrorHandler::isThrowable($e) ? sprintf(
            "Exception '%s' (Code: %s) with message '%s' in %s:%s%s",
            get_class($e),
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            (strlen($message) > 0 ? " - additional message: {$message}" : '')
        ) : '';
    }
}
