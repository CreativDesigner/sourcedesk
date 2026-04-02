<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Mapper
 */
namespace jtl\Connector\Mapper;

interface IPrimaryKeyMapper
{
    /**
     * Host ID getter
     *
     * @param string $endpointId
     * @param integer $type
     * @return integer|null
     */
    public function getHostId($endpointId, $type);

    /**
     * Endpoint ID getter
     *
     * @param integer $hostId
     * @param integer $type
     * @param string $relationType
     * @return string|null
     */
    public function getEndpointId($hostId, $type, $relationType = null);

    /**
     * Save link to database
     *
     * @param string $endpointId
     * @param integer $hostId
     * @param integer $type
     * @return boolean
     */
    public function save($endpointId, $hostId, $type);
    
    //public function update($endpointId = null, $hostId = null, $type);

    /**
     * Delete link from database
     *
     * @param string $endpointId
     * @param integer $hostId
     * @param integer $type
     * @return boolean
     */
    public function delete($endpointId = null, $hostId = null, $type);

    /**
     * Clears the entire link table
     *
     * @return boolean
     */
    public function clear();

    /**
     * Garbage Collect the entire link table
     *
     * @return boolean
     */
    public function gc();
}
