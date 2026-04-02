<?php

/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Method;

use \jtl\Connector\Feature\Base\Base as BaseClass;
use \jtl\Connector\Feature\Method\IMethod;

/**
 * Base method class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class Base extends BaseClass implements IMethod
{

    /**
     * @var boolean
     */
    public $supported;

    /**
     * @var string
     */
    public $comment;

    /**
     * Create the instance of your extended method.
     *
     * @param string $name The name of your method.
     * @param boolean $supported If the method fulfill itself, it will be
     * true. Otherwise the supported flag will be false.
     * @param string $comment A comment about the extended method.
     */
    public function __construct($name, $supported = false, $comment = '')
    {
        $this->name = $name;
        $this->supported = $supported;
        $this->comment = $comment;
    }

    /**
     * Returns if the method is supported.
     *
     * @return boolean
     */
    public function isSupported()
    {
        return $this->supported;
    }
}
