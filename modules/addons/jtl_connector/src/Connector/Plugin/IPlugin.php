<?php
namespace jtl\Connector\Plugin;

use \Symfony\Component\EventDispatcher\EventDispatcher;

interface IPlugin
{
    public function registerListener(EventDispatcher $dispatcher);
}