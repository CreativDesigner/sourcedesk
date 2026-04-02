<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Drawing
 */

namespace jtl\Connector\Core\Drawing;

use \jtl\Connector\Core\Exception\DrawingException;

abstract class Image implements IDrawing
{
    // Branding Position
    const BRANDING_TOP = 1;
    const BRANDING_TOP_RIGHT = 2;
    const BRANDING_RIGHT = 3;
    const BRANDING_BOTTOM_RIGHT = 4;
    const BRANDING_BOTTOM = 5;
    const BRANDING_BOTTOM_LEFT = 6;
    const BRANDING_LEFT = 7;
    const BRANDING_TOP_LEFT = 8;
    const BRANDING_CENTER = 9;
    
    protected $_image;
    protected $_isResized;
    protected $_isBranded;
    public $name;
    public $path;
    public $extension;
    public $filepath;
    public $imageinfos;
    public $newWidth;
    public $newHeight;
    public $mainWidth;
    public $mainHeight;
    public $quality = 80;
    
    public function __construct($filepath)
    {
        if (!file_exists($filepath)) {
            throw new DrawingException("File does not exists");
        }
    
        if ($filepath !== null && strlen($filepath) > 0) {
            $pathinfo = pathinfo($filepath);
            if (!isset($pathinfo["extension"])) {
                throw new DrawingException("Could not get any extension");
            }
    
            $this->_isResized = false;
            $this->_isBranded = false;
            $this->filepath = $filepath;
            $this->name = $pathinfo["filename"];
            $this->path = $pathinfo["dirname"] . "/";
            $this->extension = $pathinfo["extension"];
            $this->imageinfos = getimagesize($filepath);
            $this->mainWidth = $this->imageinfos[0];
            $this->mainHeight = $this->imageinfos[1];
        }
    }
    
    /**
     * Calculates the Watermark position
     *
     * @param int $image_width
     * @param int $image_height
     * @param int $watermark_width
     * @param int $watermark_height
     * @param int $position
     * @param int $padding
     *
     * @return multitype:number
     */
    protected function _calcWatermarkPosition($image_width, $image_height, $watermark_width, $watermark_height, $position, $padding = 0)
    {
        $padding /= 100;
        $posX = 0;
        $posY = 0;
        switch ($position) {
            case Image::BRANDING_TOP:
                $posX = $image_width / 2 - $watermark_width / 2;
                $posY = $image_height * $padding;
                break;
            case Image::BRANDING_TOP_RIGHT:
                $posX = $image_width - $watermark_width - $image_width * $padding;
                $posY = $image_height * $padding;
                break;
            case Image::BRANDING_RIGHT:
                $posX = $image_width - $watermark_width - $image_width * $padding;
                $posY = $image_height / 2 - $watermark_height / 2;
                break;
            case Image::BRANDING_BOTTOM_RIGHT:
                $posX = $image_width - $watermark_width - $image_width * $padding;
                $posY = $image_height - $watermark_height - $image_height * $padding;
                break;
            case Image::BRANDING_BOTTOM:
                $posX = $image_width / 2 - $watermark_width / 2;
                $posY = $image_height - $watermark_height - $image_height * $padding;
                break;
            case Image::BRANDING_BOTTOM_LEFT:
                $posX = $image_width * $padding;
                $posY = $image_height - $watermark_height - $image_height * $padding;
                break;
            case Image::BRANDING_LEFT:
                $posX = $image_width * $padding;
                $posY = $image_height / 2 - $watermark_height / 2;
                break;
            case Image::BRANDING_TOP_LEFT:
                $posX = $image_width * $padding;
                $posY = $image_height * $padding;
                break;
            case Image::BRANDING_CENTER:
                $posX = $image_width / 2 - $watermark_width / 2;
                $posY = $image_height / 2 - $watermark_height / 2;
                break;
        }
        
        $posX = (int)round($posX);
        $posY = (int)round($posY);
        
        return array($posX, $posY);
    }
    
    /**
     * Calculates the Watermark offset
     *
     * @param int $image_width
     * @param int $image_height
     * @param int $watermark_width
     * @param int $watermark_height
     * @param int $percent
     *
     * @return multitype:number
     */
    protected function _calcWatermarkOffset($image_width, $image_height, $watermark_width, $watermark_height, $percent = 30)
    {
        $off = $percent / 100;
         
        $watermark_width = (int)round($image_width * $off);
        $watermark_height = (int)round($image_height * $off);
        
        return array($watermark_width, $watermark_height);
    }
}
