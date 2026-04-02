<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Drawing
 */

namespace jtl\Connector\Core\Drawing;

use \jtl\Connector\Core\Exception\DrawingException;

class ImageIm extends Image
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);
        
        if (!class_exists("\\Imagick")) {
            throw new DrawingException("Class 'Imagick' does not exists");
        }
        
        try {
            $this->_image = new \Imagick($this->filepath);
            $this->_image->stripimage();
        } catch (\Exception $exc) {
            throw new DrawingException($exc);
        }
    }
    
    public function __destruct()
    {
        $this->_image->destroy();
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::resize()
     */
    public function resize($width, $height, $useContainer = false, $backgroundColor = null, $quality = 80)
    {
        $this->quality = (int)$quality;
        $this->newWidth = (int)$width;
        $this->newHeight = (int)$height;
        
        try {
            //$this->_image->thumbnailImage($width, $height, true);
            $this->_image->resizeimage($width, $height, \Imagick::FILTER_POINT, 0.9, true);
            
            $geometry = $this->_image->getImageGeometry();
            if (!$useContainer && $geometry['width'] == $this->mainWidth && $geometry['height'] == $this->mainHeight) {
                return null;
            }
            
            $this->_image->setCompression(\Imagick::COMPRESSION_LZW);
            $this->_image->setCompressionQuality(90);
            
            if ($useContainer) {
                $this->mainWidth = (int)$geometry['width'];
                $this->mainHeight = (int)$geometry['height'];
                
                $x = (int)(($width - $geometry['width']) / 2);
                $y = (int)(($height - $geometry['height']) / 2);
                
                if (strtolower($this->extension) == 'png') {
                    $backgroundColor = 'transparent';
                }
                
                if ($backgroundColor === null) {
                    $backgroundColor = "#FFFFFF";
                }
                
                $color = new \ImagickPixel();
                $color->setColor($backgroundColor);
                
                $this->_image->borderImage($color, $x, $y);
            }
            
            $this->_isResized = true;
        } catch (\Exception $exc) {
            throw new DrawingException($exc);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::watermark()
     */
    public function watermark($filepath, $position, $percent = 30, $opacity = 1.0, $padding = 0)
    {
        try {
            $newImg = new \Imagick($filepath);
            
            $image_width = $this->_image->getImageWidth();
            $image_height = $this->_image->getImageHeight();
            $watermark_width = $newImg->getImageWidth();
            $watermark_height = $newImg->getImageHeight();
            
            list($watermark_width, $watermark_height) = $this->_calcWatermarkOffset($image_width, $image_height, $watermark_width, $watermark_height, $percent);
            
            if ($watermark_width > $watermark_height) {
                $newImg->thumbnailImage($watermark_width, 0, false);
            } else {
                $newImg->thumbnailImage(0, $watermark_height, false);
            }
            
            $watermark_width = $newImg->getimagewidth();
            $watermark_height = $newImg->getimageheight();
            
            list($posX, $posY) = $this->_calcWatermarkPosition($image_width, $image_height, $watermark_width, $watermark_height, $position, $padding);
                         
            $newImg->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $opacity, \Imagick::CHANNEL_ALPHA);
            $this->_image->compositeImage($newImg, \Imagick::COMPOSITE_OVER, $posX, $posY, \Imagick::CHANNEL_ALPHA);
            $newImg->destroy();
            
            $this->_isBranded = true;
        } catch (\Exception $exc) {
            throw new DrawingException($exc);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::save()
     */
    public function save($newFilepath)
    {
        if (!$this->_isResized && !$this->_isBranded) {
            return copy($this->filepath, $newFilepath);
        }
        
        try {
            return $this->_image->writeImage($newFilepath);
        } catch (\Exception $exc) {
            throw new DrawingException($exc);
        }
    }
   
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::destroy()
     */
    public function destroy()
    {
        $this->__destruct();
    }
}
