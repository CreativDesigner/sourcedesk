<?php
namespace jtl\Connector\Core\IO;

class Path
{
    public static function combine()
    {
        $paths = func_get_args();

        if (!is_array($paths) || count($paths) == 0) {
            throw new \InvalidArgumentException('empty or invalid paths');
        }

        $path = str_replace('//', '/', implode(DIRECTORY_SEPARATOR, $paths));

        //return file_exists($path) ? realpath($path) : $path;
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function getDirectoryName($path, $real = true)
    {
        return ($real && is_dir($path)) ? realpath(dirname($path)) : dirname($path);
    }

    public static function getFileName($path)
    {
        return self::hasExtension($path) ? self::getFileNameWithoutExtension($path) . '.' 
            . self::getExtension($path) : self::getFileNameWithoutExtension($path);
    }

    public static function getFileNameWithoutExtension($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public static function getExtension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function hasExtension($path)
    {
        return (strlen(self::getExtension($path)) > 0);
    }
}