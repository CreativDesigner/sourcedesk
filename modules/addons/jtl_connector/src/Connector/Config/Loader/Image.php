<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Shop3
 */

namespace jtl\Connector\Config\Loader;

use \jtl\Connector\Core\Exception\ConfigException;
use \jtl\Connector\Core\Config\Loader\Base as BaseLoader;

/**
 * Shop3 Imageproperty class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class Image extends BaseLoader
{

    const KEY_TYPE = 'type';
    const KEY_WIDTH = 'width';
    const KEY_HEIGHT = 'height';
    const KEY_CONTAINER = 'container';
    const KEY_FORMAT = 'format';
    const KEY_QUALITY = 'quality';
    const KEY_BACKGROUNDCOLOR = 'backgroundcolor';
    const KEY_GLOBALS = 'globals';
    const KEY_NAME = 'name';
    const KEY_VALUE = 'value';

    //Required keys for images
    protected $_required = array(
      self::KEY_TYPE,
      self::KEY_WIDTH,
      self::KEY_HEIGHT,
      self::KEY_CONTAINER,
      self::KEY_QUALITY,
      self::KEY_BACKGROUNDCOLOR
    );
    //Required keys for names
    protected $_required_globals = array(
      self::KEY_NAME,
      self::KEY_VALUE
    );

    /**
     * Contructor.
     *
     * @param array $images
     */
    public function __construct(array $images = array())
    {
        if (!empty($images)) {
            $this->addImages($images);
        }
        $this->data = array(
          'platform' => array()
        );
    }
    
    /**
     * Adds a image to the data array.
     *
     * @param string $type The name of our image type.
     * @param integer $width The width of this image types.
     * @param integer $height The height of this image types.
     * @param boolean $container Defines if the image should be rendered with container or not.
     * @param string $format The image format like JPG or PNG
     * @param integer $quality The quality in percent from 1-100
     * @param string $backgroundcolor The background color of this image.
     * @throws ConfigException If the image already exists
     */
    public function addImage($type, $width, $height, $container, $format, $quality, $backgroundcolor)
    {
        if ($this->existsImage($type)) {
            throw new ConfigException(sprintf('The image with type "%s" already exist', $type), 100);
        }
        if (!isset($this->data['platform']['image'])) {
            $this->data['platform']['image'] = array();
        }
        $this->data['platform']['image'][$type] = array(
          self::KEY_TYPE => $type,
          self::KEY_WIDTH => (int) $width,
          self::KEY_HEIGHT => (int) $height,
          self::KEY_CONTAINER => (bool) $container,
          self::KEY_FORMAT => $format,
          self::KEY_QUALITY => (int) $quality,
          self::KEY_BACKGROUNDCOLOR => $backgroundcolor
        );
    }

    /**
     * Adding multiple images at once.
     *
     * @param array $images Array of images, with the same parameters as in addImage.
     */
    public function addImages(array $images)
    {
        foreach ($images as $image) {
            $this->validateImageArray($image);
            $this->addImage(
              $image[self::KEY_TYPE], $image[self::KEY_WIDTH], $image[self::KEY_HEIGHT], $image[self::KEY_CONTAINER], $image[self::KEY_FORMAT], $image[self::KEY_QUALITY], $image[self::KEY_BACKGROUNDCOLOR]
            );
        }
    }

    /**
     * Removes a image from the data array by type.
     *
     * @param string $type
     * @throws ConfigException
     */
    public function delImage($type)
    {
        if (!$this->existsImage($type)) {
            throw new ConfigException(sprintf('Can\'t delete the image with type "%s", because it doesn\'t exist', $type));
        }
    }

    /**
     * Validates a image array and throws a exception with the failure.
     *
     * @param array $image Array with all needed parameters for an image (see: addImage)
     * @return boolean
     * @throws ConfigException
     */
    public function validateImageArray(array $image)
    {
        if (!is_array($image) || empty($image)) {
            throw new ConfigException(sprintf('The image "%s" is not valid', var_export($image, true)), 100);
        }
        foreach ($this->_required as $key) {
            if (!array_key_exists($key, $image)) {
                throw new ConfigException(sprintf('The image "%s" is not valid, key "%" is missing', var_export($image, true), $key), 100);
            }
        }
        return true;
    }

    /**
     * Checks if a image with that type already exists.
     *
     * @param string $type The image type.
     * @return Boolean
     */
    protected function existsImage($type)
    {
        return !empty($this->data) && is_array($this->data['platform']['image']) && array_key_exists($type, $this->data['platform']['image']);
    }

    /**
     * Returns all Images.
     *
     * @return array
     */
    public function getImages()
    {
        return $this->data['platform']['image'];
    }

    /**
     * Adds a global image property to the class.
     *
     * @param string $name
     * @param mixed $value
     */
    public function addGlobal($name, $value)
    {
        if (!isset($this->data['platform']['global'])) {
            $this->data['platform']['global'] = array();
        }
        if ($this->existsGlobal($name)) {
            throw new ConfigException(sprintf('The global with name "%s" already exist', $name), 100);
        }
        $this->data['platform']['global'][$name] = $value;
    }

    /**
     * Adding multiple globals at once.
     *
     * @param array $globals
     */
    public function addGlobals(array $globals)
    {
        foreach ($globals as $global) {
            $this->validateGlobalArray($global);
            $this->addGlobal($global[self::KEY_NAMETYPE], $global[self::KEY_NAME]);
        }
    }

    /**
     * Removes a global from the data array by type.
     *
     * @param string $global
     * @throws ConfigException
     */
    public function delGlobal($global)
    {
        if (!$this->existsGlobal($global)) {
            throw new ConfigException(sprintf('Can\'t delete the global "%s", because it doesn\'t exist', $global));
        }
    }

    /**
     * Validates a name array and throws a exception with the failure.
     *
     * @param array $global Array with all needed parameters for an name (see: addGlobal)
     * @return boolean
     * @throws ConfigException
     */
    public function validateGlobalArray(array $global)
    {
        if (!is_array($global) || empty($global)) {
            throw new ConfigException(sprintf('The global" is not valid', var_export($global, true)), 100);
        }
        foreach ($this->_required as $key) {
            if (!array_key_exists($key, $global)) {
                throw new ConfigException(sprintf('The name "%s" is not valid, key "%" is missing', var_export($global, true), $key), 100);
            }
        }
        return true;
    }

    /**
     * Checks if a image with that type already exists.
     *
     * @param string $type The image type.
     * @return Boolean
     */
    protected function existsGlobal($type)
    {
        return !empty($this->data) && is_array($this->data['platform']['global']) && array_key_exists($type, $this->data['platform']['global']);
    }
}
