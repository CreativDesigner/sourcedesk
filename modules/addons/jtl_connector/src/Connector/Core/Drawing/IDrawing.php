<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Drawing
 */

namespace jtl\Connector\Core\Drawing;

interface IDrawing
{
    /**
     * Image Resizing
     *
     * @param int $width
     * @param int $height
     * @param boolean $useContainer
     * @param string $backgroundColor
     * @param int $quality
     */
    public function resize($width, $height, $useContainer = false, $backgroundColor = null, $quality = 80);
    
    /**
     * Image Watermarking
     *
     * @param string $filepath
     * @param int $position
     * @param int $percent
     * @param float $opacity
     * @param int $padding
     */
    public function watermark($filepath, $position, $percent = 30, $opacity = 1.0, $padding = 0);
    
    /**
     * Image Saving
     *
     * @param string $newFilepath
     */
    public function save($newFilepath);
    
    /**
     * Image Destroying
     */
    public function destroy();
}
