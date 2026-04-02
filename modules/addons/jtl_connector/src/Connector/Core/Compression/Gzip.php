<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Compression
 */

namespace jtl\Connector\Core\Compression;

use \jtl\Connector\Core\Exception\CompressionException;

/**
 * Gzip Compression
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Gzip implements ICompression
{
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Compression\ICompression::read()
     * @throws \jtl\Connector\Core\Exception\CompressionException
     */
    public function read($file)
    {
        if (file_exists($file)) {
            $fp = fopen($file, "rb");
            
            if ($fp) {
                fseek($fp, -4, SEEK_END);
                $buf = fread($fp, 4);
                $arr = unpack("V", $buf);
                $gzsize = end($arr);
                fclose($fp);
                $gz = gzopen($file, "rb");
                $content = gzread($gz, $gzsize);
                /*
                while (!gzeof($gz)) {
                    $content += gzgets($gz, 4096);
                    flush();
                    ob_flush();
                }
                */
                gzclose($gz);
                
                return $content;
            } else {
                throw new CompressionException("Could not open file");
            }
        } else {
            throw new CompressionException("File not exists");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Compression\ICompression::write()
     */
    public function write($filename, $content)
    {
    }
}
