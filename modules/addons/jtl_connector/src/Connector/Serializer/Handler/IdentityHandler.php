<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Serializer\Handler
 */
namespace jtl\Connector\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use jtl\Connector\Model\Identity;

/**
 * JMS Identity Subscribing Handler
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class IdentityHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'jtl\Connector\Model\Identity',
                'method' => 'deserializeIdentity',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'jtl\Connector\Model\Identity',
                'method' => 'serializeIdentity',
            )
        );
    }

    public function deserializeIdentity(JsonDeserializationVisitor $visitor, array $identity, array $type, Context $context)
    {
        return new Identity($identity[0], $identity[1]);
    }

    public function serializeIdentity(JsonSerializationVisitor $visitor, Identity $identity, array $type, Context $context)
    {
        return array($identity->getEndpoint(), $identity->getHost());
    }
}
