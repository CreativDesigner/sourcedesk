<?php

/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Filesystem
 */

namespace jtl\Connector\Core\Filesystem;

/**
 * Filesystem tool class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Tool
{

    /**
     * @var boolean
     */
    public static $test = false;

    /**
     * @var boolean
     */
    public static $ret_is_file = false;

    /**
     * @var boolean
     */
    public static $ret_is_writable = false;

    /**
     * Return value of file_get_content (only for unit testing)
     * @var mixed
     */
    public static $ret_file_get_content = null;

    /**
     * Return value of file_put_content (only for unit testing)
     * @var mixed
     */
    public static $ret_file_put_content = null;

    /**
     * This method is a wrapper for the native file_get_contents function.
     *
     * @see file_get_contents()
     *
     * @param string $filename
     * @param boolean $use_include_path
     * @param resource $context
     * @param integer $offset
     * @param integer $maxlen
     * @return mixed
     */
    public static function file_get_contents($filename, $use_include_path = false, $context = null, $offset = -1, $maxlen = null)
    {
        if (self::$test) {
            return self::$ret_file_get_content;
        }
        if (is_integer($maxlen) && $maxlen > 0) {
            return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
        } else {
            return file_get_contents($filename, $use_include_path, $context, $offset);
        }
    }

    /**
     * This method is a wrapper for the native file_put_content function.
     *
     * @see file_put_contents()
     *
     * @param string $filename
     * @param mixed $data
     * @param integer $flags
     * @param resource $context
     * @return mixed
     */
    public static function file_put_contents($filename, $data = null, $flags = 0, $context = null)
    {
        if (self::$test) {
            return self::$ret_file_put_content;
        }
        return @file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * This method is a wrapper for the native is_file function.
     *
     * @param string $filename
     * @return bool
     */
    public static function is_file($filename)
    {
        if (self::$test) {
            return self::$ret_is_file;
        }
        return is_file($filename);
    }

    /**
     * This method is a wrapper for the native is_writable function.
     *
     * @param string $file
     * @return bool
     */
    public static function is_writable($file)
    {
        if (self::$test) {
            return self::$ret_is_writable;
        }
        return is_writable($file);
    }
}
