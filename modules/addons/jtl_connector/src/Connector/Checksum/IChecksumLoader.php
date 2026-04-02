<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Application
 */

namespace jtl\Connector\Checksum;

interface IChecksumLoader
{
    /**
     * Loads the checksum
     *
     * @param string $endpointId
     * @param int $type
     * @return string
     */
    public function read($endpointId, $type);

    /**
     * Loads the checksum
     *
     * @param string $endpointId
     * @param int $type
     * @param string $checksum
     * @return boolean
     */
    public function write($endpointId, $type, $checksum);

    /**
     * Loads the checksum
     *
     * @param string $endpointId
     * @param int $type
     * @return boolean
     */
    public function delete($endpointId, $type);
}