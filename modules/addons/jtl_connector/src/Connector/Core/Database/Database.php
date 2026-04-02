<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Database
 */
namespace jtl\Connector\Core\Database;

use \jtl\Connector\Core\Exception\DatabaseException;

/**
 * Base Database Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Database
{
    /**
     * Creates a Database Object
     *
     * @param string $type
     * @throws \jtl\Connector\Core\Exception\DatabaseException
     */
    public static function create($type)
    {
        if (class_exists($type)) {
            return $type::getInstance();
        } else {
            throw new DatabaseException("Could not find type {$type}");
        }
    }
}
