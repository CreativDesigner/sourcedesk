<?php

/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Exception
 */

namespace jtl\Connector\Core\Exception;

/**
 * Config Exception Class
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class ConfigException extends \Exception
{

    public $jtl = true;

    /**
     * Constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message, $code, $previous = null)
    {
        $this->jtl = $code > 0;
        parent::__construct($message, $code, $previous);
    }
}
