<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Compression
 */

namespace jtl\Connector\Core\IO;

class Temp
{
    /**
     * @return null|string
     */
    public static function generateDirectory()
    {
        $dir = Path::combine(self::getDirectory(), 'con-' . uniqid());
        if (mkdir($dir)) {
            return $dir;
        }

        return null;
    }
    
    /**
     * @return string
     * @throws \Exception
     */
    public static function getDirectory()
    {
        $dir = sys_get_temp_dir();
        if (!is_writeable($dir)) {
            if (!defined('CONNECTOR_DIR')) {
                throw new \Exception('Constant CONNECTOR_DIR is not defined');
            }
            
            $dir = Path::combine(CONNECTOR_DIR, 'tmp');
        }
    
        if (!is_writeable($dir)) {
            throw new \Exception(sprintf(
                'Default temp directory and fallback directory \'%s\' is not writeable',
                $dir
            ));
        }
        
        return $dir;
    }
}