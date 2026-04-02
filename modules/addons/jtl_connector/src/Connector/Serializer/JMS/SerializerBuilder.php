<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Serializer\Handler
 */
namespace jtl\Connector\Serializer\JMS;

class SerializerBuilder
{
    public static function create()
    {
        return \JMS\Serializer\SerializerBuilder::create()
                ->addDefaultHandlers()
                ->configureHandlers(function (\JMS\Serializer\Handler\HandlerRegistry $registry) {
                    $registry->registerSubscribingHandler(new \jtl\Connector\Serializer\Handler\IdentityHandler());
                })
                ->build();
    }
}
