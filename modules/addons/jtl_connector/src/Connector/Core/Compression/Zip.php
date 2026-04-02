<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Compression
 */

namespace jtl\Connector\Core\Compression;

use jtl\Connector\Core\Exception\CompressionException;
use jtl\Connector\Core\Exception\NotImplementedException;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * Zip
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Zip
{
    /**
     * @param $sourceFile
     * @param $targetFolder
     * @return bool
     * @throws CompressionException
     * @throws \FileNotFoundException
     * @throws \InvalidArgumentException
     */
    public function extract($sourceFile, $targetFolder)
    {
        if (!class_exists('ZipArchive')) {
            throw new CompressionException('Class ZipArchive not found. PHP 5 >= 5.2.0, PECL zip >= 1.1.0 installed?');
        }

        if (!file_exists($sourceFile)) {
            throw new \FileNotFoundException(sprintf('File (%s) not found', $sourceFile));
        }

        if (!is_dir($targetFolder) || !is_writeable($targetFolder)) {
            throw new \InvalidArgumentException(sprintf('Folder (%s) does not exists or is not writeable', $targetFolder));
        }

        $archive = new \ZipArchive();
        if ($archive->open($sourceFile) === true) {
            $archive->extractTo($targetFolder);
            $archive->close();

            return true;
        }

        return false;
    }

    public function zip($sourceFolder, $targetFile)
    {
        throw new NotImplementedException();
    }
}