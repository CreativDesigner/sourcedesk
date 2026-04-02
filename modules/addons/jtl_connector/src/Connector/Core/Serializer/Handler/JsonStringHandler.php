<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Serializer\Handler
 */
namespace jtl\Connector\Core\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use jtl\Connector\Core\Rpc\JsonString;
use jtl\Connector\Core\Serializer\Json;

/**
 * JMS JsonString Subscribing Handler
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class JsonStringHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'jtl\Connector\Core\Rpc\JsonString',
                'method' => 'deserializeJsonString',
            ),
        );
    }

    public function deserializeJsonString(JsonDeserializationVisitor $visitor, $params, array $type, Context $context)
    {
        return Json::encode($params);
    }
}
