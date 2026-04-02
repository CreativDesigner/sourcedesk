<?php
/**
 *
 * @copyright JTL-Software GmbH
 * @package jtl\Connector\Core\Serializer
 */
namespace jtl\Connector\Core\Serializer;

/**
 * Serializer Interface
 *
 * @access public
 * @author Andreas Jütten <andy@jtl-software.de>
 */
interface ISerializer
{
    /**
     *
     * @param type $object
     */
    public static function encode($object);

    public static function decode($string);
}
