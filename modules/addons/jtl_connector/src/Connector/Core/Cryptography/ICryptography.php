<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Cryptography
 */

namespace jtl\Connector\Core\Cryptography;

/**
 * Cryptography Interface
 */
interface ICryptography
{
    /**
     * Encrypt
     *
     * @param string $text
     */
    public function encrypt($text);
    
    /**
     * Decrypt
     *
     * @param string $text
     */
    public function decrypt($text);
}
