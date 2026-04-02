<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Mapper
 */

namespace jtl\Connector\Core\Mapper;

use \jtl\Connector\Core\Model\DataModel;
use \jtl\Connector\Core\Model\QueryFilter;

/**
 * Generic mapper interface
 * @author Christian Spoo <christian.spoo@jtl-software.de>
 */
interface IMapper
{
    /**
     * Save
     *
     * @param \jtl\Connector\Core\Model\DataModel $model
     * @return boolean
     */
    public function save(DataModel $model);
    
    /**
     * Delete
     *
     * @param \jtl\Connector\Core\Model\DataModel $model
     * @return boolean
     */
    public function delete(DataModel $model);
    
    /**
     * Fetching a single Row
     *
     * @param array $kvs
     * @return \stdClass
     */
    public function fetchRow(array $kvs = null);

    /**
     * Fetching multiple Rows
     *
     * @param array $kvs
     * @return multiple: \stdClass
     */
    public function fetchRows(array $kvs = null);
    
    /**
     * Fetching multiple Rows
     *
     * @param \jtl\Connector\Model\QueryFilter $filter
     * @return multiple: \stdClass
     */
    public function fetchAll(QueryFilter $filter = null);

    /**
     * Fetching a single count
     *
     * @param \jtl\Connector\Model\QueryFilter $filter
     * @return integer
     */
    public function fetchCount(QueryFilter $filter = null);
}
