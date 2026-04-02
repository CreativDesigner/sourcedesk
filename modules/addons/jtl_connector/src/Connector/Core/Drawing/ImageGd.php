<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Drawing
 */

namespace jtl\Connector\Core\Drawing;

use \jtl\Connector\Core\Exception\DrawingException;

class ImageGd extends Image
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);
        
        $this->_createImage();
    }
    
    public function __destruct()
    {
        if (is_resource($this->_image)) {
            imagedestroy($this->_image);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::resize()
     */
    public function resize($width, $height, $useContainer = false, $backgroundColor = null, $quality = 80)
    {
        $this->quality = (int)$quality;
        $this->newWidth = $width;
        $this->newHeight = $height;
                
        $ratio = $this->imageinfos[0] / $this->imageinfos[1];
        
        $new_width = $width;
        $new_height = (int)round($new_width / $ratio);
        if ($new_height > $height) {
            $new_height = $height;
            $new_width = (int)round($new_height * $ratio);
        }
        
        if (!$useContainer && $new_width == $this->mainWidth && $new_height == $this->mainHeight) {
            return null;
        }
        
        if ($useContainer) {
            $this->_imageload_container($new_width, $new_height, $width, $height, $backgroundColor);
        } else {
            $this->_imageload_alpha($new_width, $new_height, $backgroundColor);
        }
        
        $this->_isResized = true;
    }

    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::watermark()
     */
    public function watermark($filepath, $position, $percent = null, $opacity = 1.0, $padding = 0)
    {
        if (!file_exists($filepath)) {
            throw new DrawingException("File '{$filepath}' does not exsits");
        }
        
        $branding = $this->_imageload_alpha(0, 0, "#FFFFFF", $filepath);
        if ($branding) {
            $imageInfo = getimagesize($filepath);
            $width = imagesx($this->_image);
            $height = imagesy($this->_image);
            $brandingWidth = imagesx($branding);
            $brandingHeight = imagesy($branding);
            $brandingWidthNew = $brandingWidth;
            $brandingHeightNew = $brandingHeight;
            $image_branding = $branding;
            
            $ratio = $brandingWidth / $brandingHeight;
            
            if ($percent !== null) {
                list($brandingWidthNew, $brandingHeightNew) = $this->_calcWatermarkOffset($width, $height, $brandingWidth, $brandingHeight, $percent);
                
                $brandingHeightNew = (int)round($brandingWidthNew / $ratio);
                if ($brandingHeightNew > $brandingHeight) {
                    $brandingHeightNew = $brandingHeight;
                    $brandingWidthNew = (int)round($brandingHeightNew * $ratio);
                }
                
                $image_branding = $this->_imageload_alpha($brandingWidthNew, $brandingHeightNew, "#FFFFFF", $filepath);
            }
            
            list($posX, $posY) = $this->_calcWatermarkPosition($width, $height, $brandingWidthNew, $brandingHeightNew, $position, $padding);
            
            imagealphablending($this->_image, true);
            imagesavealpha($this->_image, true);
            
            $this->_imagecopymerge_alpha($this->_image, $image_branding, $posX, $posY, 0, 0, $brandingWidthNew, $brandingHeightNew, $opacity);
            
            $this->_isBranded = true;
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
        
        if ($this->_image === null) {
            throw new DrawingException("Image is not a valid resource. Please resize or watermark the image before saving!");
        }
        
        switch ($this->imageinfos[2]) {
            case IMAGETYPE_GIF:
                if (!function_exists('imagegif')) {
                    throw new DrawingException("Function 'imagegif' does not exists");
                }
        
                return imagegif($this->_image, $newFilepath);
            case IMAGETYPE_JPEG:
                if (!function_exists('imagejpeg')) {
                    throw new DrawingException("Function 'imagejpeg' does not exists");
                }
        
                return imagejpeg($this->_image, $newFilepath, $this->quality);
            case IMAGETYPE_PNG:
                if (!function_exists('imagepng')) {
                    throw new DrawingException("Function 'imagepng' does not exists");
                }
        
                return imagepng($this->_image, $newFilepath);
            case IMAGETYPE_BMP:
                if (!function_exists('imagewbmp')) {
                    throw new DrawingException("Function 'imagewbmp' does not exists");
                }
        
                return imagewbmp($this->_image, $newFilepath);
        }
        
        throw new DrawingException("Format '{$this->extension}' is not supported");
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Drawing\IDrawing::destroy()
     */
    public function destroy()
    {
        $this->__destruct();
    }

    protected function _imageload_alpha($width, $height, $backgroundColor, $filepath = null)
    {
        $image = null;
        $imageinfos = null;
        if ($filepath === null) {
            $imageinfos = $this->imageinfos;
            $image = $this->_image;
        } else {
            $imageinfos = getimagesize($filepath);
            $image = $this->_createImage($filepath);
        }
        
        if ($width == 0 && $height == 0) {
            $width = $imageinfos[0];
            $height = $imageinfos[1];
        }

        $width = (int)round($width);
        $height = (int)round($height);
        $newImg = imagecreatetruecolor($width, $height);

        if ($this->extension == 'jpg') {
            $rgb = $this->_html2rgb($backgroundColor);
            
            if ($rgb) {
                $color = imagecolorallocate($newImg, $rgb[0], $rgb[1], $rgb[2]);
            } else {
                $color = imagecolorallocate($newImg, 255, 255, 255);
            }
        } else {
            $color = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        }
         
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        imagefilledrectangle($newImg, 0, 0, 0, 0, $color);
        
        imagecopyresampled($newImg, $image, 0, 0, 0, 0, $width, $height, $imageinfos[0], $imageinfos[1]);
        
        if ($filepath !== null) {
            return $newImg;
        }
         
        $this->_image = $newImg;
    }

    protected function _imageload_container($width, $height, $containerWidth, $containerHeight, $backgroundColor, $filepath = null)
    {
        $image = null;
        $imageinfos = null;
        if ($filepath === null) {
            $imageinfos = $this->imageinfos;
            $image = $this->_image;
        } else {
            $imageinfos = getimagesize($filepath);
            $image = $this->_createImage($filepath);
        }
        
        if ($width == 0 && $height == 0) {
            $width = $imageinfos[0];
            $height = $imageinfos[1];
        }

        $width = (int)round($width);
        $height = (int)round($height);
        
        $this->mainWidth = $width;
        $this->mainHeight = $height;

        $newImg = imagecreatetruecolor($containerWidth, $containerHeight);

        if ($this->extension == 'jpg') {
            $rgb = $this->_html2rgb($backgroundColor);
            
            if ($rgb) {
                $color = imagecolorallocate($newImg, $rgb[0], $rgb[1], $rgb[2]);
            } else {
                $color = imagecolorallocate($newImg, 255, 255, 255);
            }
        } else {
            $color = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        }
        
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        imagefilledrectangle($newImg, 0, 0, $containerWidth, $containerHeight, $color);

        $posX = (int)(($containerWidth / 2) - ($width / 2));
        $posY = (int)(($containerHeight / 2) - ($height / 2));
        
        imagecopyresampled($newImg, $image, $posX, $posY, 0, 0, $width, $height, $imageinfos[0], $imageinfos[1]);
        
        if ($filepath !== null) {
            return $newImg;
        }
        
        $this->_image = $newImg;
    }
    
    protected function _imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        if (!isset($pct)) {
            return false;
        }
        
        $w = imagesx($src_im);
        $h = imagesy($src_im);
                
        imagealphablending($src_im, false);
        
        $minalpha = 127;
        $minalpha = 0;
    
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $colorxy = imagecolorat($src_im, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $pct;
                }
                
                $alphacolorxy = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                
                if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }
        
        return imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
    }
    
    protected function _createImage($filepath = null)
    {
        $isExt = false;
        if ($filepath === null) {
            $filepath = $this->filepath;
            $imageinfos = $this->imageinfos;
        } else {
            $imageinfos = getimagesize($filepath);
            $isExt = true;
        }
        
        $image = null;
        switch ($imageinfos[2]) {
            case IMAGETYPE_GIF: $image = imagecreatefromgif($filepath); break;
            case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($filepath); break;
            case IMAGETYPE_PNG: $image = imagecreatefrompng($filepath); break;
            case IMAGETYPE_BMP: $image = imagecreatefromwbmp($filepath); break;
            default: throw new DrawingException("Format not supported"); break;
        }
        
        if (!$isExt) {
            $this->_image = $image;
        }
        
        return $image;
    }

    protected function _html2rgb($color)
    {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 6) {
            list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return false;
        }

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return array($r, $g, $b);
    }
}
