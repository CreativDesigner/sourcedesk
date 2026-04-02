<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Linker
 */
namespace jtl\Connector\Linker;

use \jtl\Connector\Checksum\IChecksumLoader;
use \jtl\Connector\Checksum\IChecksum;
use \jtl\Connector\Core\Model\Model;
use \jtl\Connector\Core\Logger\Logger;

/**
 * Identity Connector Linker
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.com>
 */
class ChecksumLinker
{
    protected static $loader;

    public static function setChecksumLoader(IChecksumLoader $loader)
    {
        self::$loader = $loader;
    }

    /**
     * @param \jtl\Connector\Core\Model\Model $model
     * @param int $type
     */
    public static function link(Model &$model, $type = null)
    {
        if (method_exists($model, 'getChecksums')) {
            $checksums = $model->getChecksums();
            foreach ($checksums as &$checksum) {
                if ($checksum instanceof IChecksum && ($type === null || $checksum->getType() == $type)) {

                    Logger::write(sprintf('Checksum linking type (%s)...', $type), Logger::DEBUG, 'checksum');

                    if ($model->getId()->getEndpoint() !== null && strlen($model->getId()->getEndpoint()) > 0) {
                        $checksum->setEndpoint(self::$loader->read($model->getId()->getEndpoint(), $checksum->getType()));

                        if ($checksum->getEndpoint() !== null && strlen($checksum->getEndpoint()) > 0) {
                            if (($checksum->getEndpoint() !== $checksum->getHost())) {
                                Logger::write(sprintf('Changed Checksum for endpoint (%s) type (%s)', $model->getId()->getEndpoint(), $type), Logger::DEBUG, 'checksum');
                                $checksum->setHasChanged(true);
                                self::$loader->delete($model->getId()->getEndpoint(), $checksum->getType());
                                self::$loader->write($model->getId()->getEndpoint(), $checksum->getType(), $checksum->getHost());
                            }
                        } else {
                            Logger::write(sprintf('Write new Checksum for endpoint (%s) type (%s)', $model->getId()->getEndpoint(), $type), Logger::DEBUG, 'checksum');
                            $checksum->setHasChanged(true);
                            self::$loader->write($model->getId()->getEndpoint(), $checksum->getType(), $checksum->getHost());
                        }
                    } else {
                        Logger::write(sprintf('New Checksum with empty endpoint type (%s)', $model->getId()->getEndpoint(), $type), Logger::DEBUG, 'checksum');
                        $checksum->setHasChanged(true);
                    }
                }
            }
        }
    }

    /**
     * @param \jtl\Connector\Checksum\IChecksum $checksum
     * @return boolean
     */
    public static function save(IChecksum $checksum)
    {
        if (strlen($checksum->getForeignKey()->getEndpoint()) > 0 && $checksum->getForeignKey()->getHost()) {
            self::$loader->delete($checksum->getForeignKey()->getEndpoint(), $checksum->getType());
            self::$loader->write($checksum->getForeignKey()->getEndpoint(), $checksum->getType(), $checksum->getHost());

            return true;
        }

        return false;
    }

    /**
     * @param \jtl\Connector\Core\Model\Model $model
     * @param int $type
     * @return \jtl\Connector\Checksum\IChecksum
     */
    public static function find(Model $model, $type)
    {
        if (method_exists($model, 'getChecksums')) {
            foreach ($model->getChecksums() as $checksum) {
                if ($checksum instanceof IChecksum && $checksum->getType() == $type) {
                    return $checksum;
                }
            }   
        }

        return null;
    }

    /**
     * @param \jtl\Connector\Core\Model\Model $model
     * @param string $endpoint
     * @param int $type
     * @return \jtl\Connector\Checksum\IChecksum
     */
    public static function findByEndpoint(Model $model, $endpoint, $type)
    {
        if (method_exists($model, 'getChecksums')) {
            foreach ($model->getChecksums() as $checksum) {
                if ($checksum instanceof IChecksum && $checksum->getType() == $type && $checksum->getForeignKey()->getEndpoint() === $endpoint) {
                    return $checksum;
                }
            }
        }

        return null;
    }

    /**
     * @param \jtl\Connector\Core\Model\Model $model
     * @param int $host
     * @param int $type
     * @return \jtl\Connector\Checksum\IChecksum
     */
    public static function findByHost(Model $model, $host, $type)
    {
        if (method_exists($model, 'getChecksums')) {
            foreach ($model->getChecksums() as $checksum) {
                if ($checksum instanceof IChecksum && $checksum->getType() == $type && $checksum->getForeignKey()->getHost() == $host) {
                    return $checksum;
                }
            }
        }

        return null;
    }
}