<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Compression
 */

namespace jtl\Connector\Core\Compression;

/**
 * Compression Interface
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de> *
 */
interface ICompression
{
    /**
     * Read content from compressed file
     *
     * @param string $file
     */
    public function read($file);
    
    /**
     * Write compressed content to file
     *
     * @param string $file
     * @param string $content
     */
    public function write($file, $content);
}
