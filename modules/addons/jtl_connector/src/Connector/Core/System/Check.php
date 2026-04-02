<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\System
 */

namespace jtl\Connector\Core\System;

use jtl\Connector\Core\Exception\MissingRequirementException;

class Check
{
    /**
     * @throws MissingRequirementException
     */
    public static function run()
    {
        // PHP
        if (!version_compare(PHP_VERSION, '7.1', '>=')) {
            throw new MissingRequirementException(sprintf('The connector needs at least PHP version 7.1, %s given', PHP_VERSION));
        }

        // Sqlite 3
        if (!extension_loaded('sqlite3') || !class_exists('Sqlite3')) {
            throw new MissingRequirementException('The connector needs the sqlite3 PHP extension');
        }

        // Zip
        if (!class_exists('ZipArchive')) {
            throw new MissingRequirementException('Class ZipArchive not found. PHP 5 >= 5.2.0, PECL zip >= 1.1.0 installed?');
        }
    }
}