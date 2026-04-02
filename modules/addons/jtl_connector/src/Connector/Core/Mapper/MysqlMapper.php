<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Mapper
 */

namespace jtl\Connector\Core\Mapper;

use \jtl\Connector\Core\Database\Mysql;
use \jtl\Connector\Core\Utilities\Singleton;
use \jtl\Connector\Core\Exception\DatabaseException;
use \jtl\Connector\Core\Model\Model;
use \jtl\Connector\Core\Model\DataModel;
use \jtl\Connector\Core\Model\QueryFilter;

/**
 * Abstract Mysql Mapper
 *
 * @access public
 */
abstract class MysqlMapper extends Singleton implements IMapper
{
    /**
     * Mysql Database
     *
     * @var \jtl\Connector\Core\Database\Mysql
     */
    protected $_db;
    
    /**
     * Mysql Table
     *
     * @var string
     */
    protected $_table;
    
    /**
     * Mysql Primary Key
     *
     * @var mixed:string|multiple
     */
    protected $_primary;
    
    /**
     * Constructor
     */
    protected function __construct()
    {
        $this->_db = Mysql::getInstance();
        if (!$this->_db->isConnected()) {
            throw new DatabaseException("No Database Connection");
        }
        
        $this->load();
    }
    
    /**
     * Loader for $_table and $_primary
     */
    abstract protected function load();

    /**
     * @return \jtl\Connector\Core\Database\Mysql
     */
    public function Db()
    {
        return $this->_db;
    }
    
    /**
     * Get Table Name
     *
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Character Set Support
     *
     * @param string $encoding
     */
    public function setNames($encoding = 'utf8')
    {
        return $this->_db->setNames($encoding);
    }

    /**
     * Name Set Support
     *
     * @param string $encoding
     */
    public function setCharset($encoding = 'utf8')
    {
        return $this->_db->setCharset($encoding);
    }
    
    /**
     * Checks if a Primary Key is set
     *
     * @param \stdClass $object
     * @return boolean
     */
    public function isPrimarySet(\stdClass $object)
    {
        if (is_array($this->_primary)) {
            $isSet = true;
            foreach ($this->_primary as $primary) {
                if ($object->$primary === null || strlen($object->$primary) == 0) {
                    $isSet = false;
                }
            }
            
            return $isSet;
        } elseif ($this->_primary !== null) {
            $primary = $this->_primary;
            if ($object->$primary !== null || strlen($object->$primary) > 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Gets Primary Values
     *
     * @param DataModel $model
     * @return mutiple|string|null
     */
    public function getPrimaryValues(DataModel $model)
    {
        if (is_array($this->_primary)) {
            $values = array();
            foreach ($this->_primary as $primary) {
                $values[] = $model->$primary;
            }
            
            return $values;
        } elseif ($this->_primary !== null) {
            $primary = $this->_primary;
            return $model->$primary;
        }
        
        return null;
    }
    
    /**
     * Gets Primary Fields
     *
     * @param DataModel $model
     * @param boolean $toWawi
     * @return mutiple|string|null
     */
    public function getPrimaryFields(DataModel $model = null, $toWawi = false)
    {
        if (is_array($this->_primary)) {
            $fields = array();
            
            if ($model === null) {
                foreach ($this->_primary as $primary) {
                    $fields[] = $primary;
                }
                
                return $fields;
            }
                        
            foreach ($this->_primary as $primary) {
                $fields[] = $model->getField($toWawi, $primary);
            }
            
            return $fields;
        } elseif ($this->_primary !== null) {
            if ($model === null) {
                return $this->_primary;
            }
            
            return $model->getField($toWawi, $this->_primary);
        }
        
        return null;
    }
    
    /**
     *
     * @param DataModel $model
     * @param boolean $toWawi
     * @return multitype: mixed
     */
    public function getKeyValues(DataModel $model, $toWawi = false)
    {
        $kvs = array();
        
        $fields = $this->getPrimaryFields($model, $toWawi);
        $values = $this->getPrimaryValues($model);
        
        if ($fields !== null && is_array($fields)) {
            foreach ($fields as $i => $field) {
                $kvs[$field] = $values[$i];
            }
        } elseif ($fields !== null) {
            $kvs[$fields] = $values;
        }
        
        return $kvs;
    }
    
    /**
     * Generation Sql Primary Key Where Statement
     *
     * @param array $kvs
     * @return string
     */
    public function whereSql(array $kvs = null)
    {
        $sql = "";
        if ($kvs !== null) {
            $sql = "WHERE ";
            $i = 0;
            foreach ($kvs as $key => $value) {
                $sep = $i > 0 ? " AND" : "";
                $sql .= "{$sep} " . $this->_db->escapeString($key) . " = '" . $this->_db->escapeString($value) . "'";
                $i++;
            }
        }
        
        return $sql;
    }
    
    /**
     * Generation Sql Limit Statement
     *
     * @param QueryFilter $filter
     * @return string
     */
    public function limitSql(QueryFilter $filter = null)
    {
        $limit = "";
        if ($filter !== null && $filter->isLimit()) {        
            $limit = "LIMIT {$filter->getLimit()}";
        }
        
        return $limit;
    }
    
    /**
     * Generation Sql Order Statement
     *
     * @param QueryFilter $filter
     * @return string
     */
    public function orderSql(QueryFilter $filter = null)
    {
        $order = "";
        if ($filter !== null && $filter->isOrder()) {
            $order = "ORDER BY ";
            foreach ($filter->getOrders() as $i => $order) {
                if ($i > 0) {
                    $order .= ", ";
                }
                
                $order .= "{$order}";
            }
        }
        
        return $order;
    }
}
