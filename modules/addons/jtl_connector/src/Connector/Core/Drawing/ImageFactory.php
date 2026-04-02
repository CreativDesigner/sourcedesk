<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Drawing
 */

namespace jtl\Connector\Core\Drawing;

use \jtl\Connector\Core\Exception\DrawingException;

class ImageFactory
{
    /**
     * Starts a new Image object
     *
     * @param string $filepath
     * @throws \jtl\Connector\Core\Exception\DrawingException
     * @return \jtl\Connector\Core\Drawing\ImageIm|\jtl\Connector\Core\Drawing\ImageGd
     */
    public static function startNew($filepath)
    {
        if ($filepath === null || strlen($filepath) == 0) {
            throw new DrawingException("Invalid filepath '{$filepath}'");
        }
        
        if (class_exists("\\Imagick")) {
            return new ImageIm($filepath);
        } else {
            return new ImageGd($filepath);
        }
    }
}
