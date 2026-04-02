<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Result
 */

namespace jtl\Connector\Core\Result;

use \jtl\Connector\Core\Model\Model;

/**
 * Mysql Result Class
 *
 * @access public
 */
class Mysql extends Model
{
    const TYPE_SELECT = "SELECT";
    const TYPE_UPDATE = "UPDATE";
    const TYPE_INSERT = "INSERT";
    const TYPE_DELETE = "DELETE";
    const TYPE_SHOW = "SHOW";
    const TYPE_ALTER = "ALTER";
    
    /**
     * Mysql Query
     *
     * @var string
     */
    protected $_query = "";
    
    /**
     * The error code for the most recent function call
     *
     * @var integer
     */
    protected $_errno = 0;
    
    /**
     * A string description of the last error
     *
     * @var string
     */
    protected $_error = "";
    
    /**
     * Primary Key
     *
     * @var integer|array
     */
    protected $_key = 0;
    
    /**
     * Mysql Command
     *
     * @var string
     */
    protected $_type = "";
    
    /**
     * Gets the number of affected rows in a previous MySQL operation
     *
     * @var integer
     */
    protected $_affected = 0;
    
    /**
     * Query Getter
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }
    
    /**
     * Query Setter
     *
     * @param string $query
     * @return \jtl\Connector\Result\Mysql
     */
    public function setQuery($query)
    {
        $this->_query = $query;
        return $this;
    }
    
    /**
     * Errno Getter
     *
     * @return integer
     */
    public function getErrno()
    {
        return $this->_errno;
    }

    /**
     * Errno Setter
     *
     * @param integer $errno
     * @return \jtl\Connector\Result\Mysql
     */
    public function setErrno($errno)
    {
        $this->_errno = (int)$errno;
        return $this;
    }

    /**
     * Error Getter
     *
     * @return integer
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Error Setter
     *
     * @param integer $error
     * @return \jtl\Connector\Result\Mysql
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * Key Getter
     *
     * @return integer
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Key Setter
     *
     * @param integer $key
     * @return \jtl\Connector\Result\Mysql
     */
    public function setKey($key)
    {
        $this->_key = (int)$key;
        return $this;
    }

    /**
     * Type Getter
     *
     * @return integer
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Errno Setter
     *
     * @param string $type
     * @return \jtl\Connector\Result\Mysql
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * Affected Getter
     *
     * @return integer
     */
    public function getAffected()
    {
        return $this->_affected;
    }

    /**
     * Affected Setter
     *
     * @param integer $affected
     * @return \jtl\Connector\Result\Mysql
     */
    public function setAffected($affected)
    {
        $this->_affected = (int)$affected;
        return $this;
    }
}
