<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Application
 */

namespace jtl\Connector\Application\Error;

use Symfony\Component\EventDispatcher\EventDispatcher;

interface IErrorHandler
{
    public function setExceptionHandler(callable $func);
    public function setErrorHandler(callable $func);
    public function setShutdownHandler(callable $func);
    public function setEventDispatcher(EventDispatcher $dispatcher);
}