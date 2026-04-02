<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Database
 */
namespace jtl\Connector\Core\Database;

use \jtl\Connector\Core\Exception\DatabaseException;
use \jtl\Connector\Core\Model\Model;
use \jtl\Connector\Core\Result\Mysql as MysqlResult;
use \jtl\Connector\Core\Logger\Logger;

/**
 * MySql Database Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Mysql implements IDatabase
{
    /**
     * Database connection state
     *
     * @var bool
     */
    protected $_isConnected = false;
    
    /**
     * MySql Database object
     *
     * @var Mysqli | NULL
     */
    protected $_db;
    
    /**
     * Database Singleton
     *
     * @var \jtl\Connector\Core\Database\Mysql
     */
    protected static $_instance;
    
    /**
     * Mysql Result
     *
     * @var \jtl\Connector\Core\Result\Mysql
     */
    public $mysqlResult;
    
    /**
     * Can be either a host name or an IP address.
     * Passing the NULL value or the string "localhost" to this parameter, the
     * local host is assumed.
     *
     * @var string
     */
    public $host;
    
    /**
     * The MySQL user name.
     *
     * @var string
     */
    public $user;
    
    /**
     * If not provided or NULL, the MySQL server will attempt to authenticate
     * the user against those user records which have no password only.
     *
     * @var string
     */
    public $password;
    
    /**
     * If provided will specify the default database to be used when performing
     * queries.
     *
     * @var string
     */
    public $name;

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->mysqlResult = new MysqlResult();
    }
    
    /**
     * Singleton
     *
     * @return \jtl\Connector\Core\Database\Mysql
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
    public function __wakeup()
    {
        $this->connect($this->host, $this->user, $this->password, $this->name);
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::connect()
     */
    public function connect(array $options = null)
    {
        $this->setOptions($options);
        
        // Allow sockets
        if (strpos($this->host, '/') === false) {
            $this->_db = @new \mysqli($this->host, $this->user, $this->password, $this->name);
        } else {
            $this->_db = @new \mysqli('', $this->user, $this->password, $this->name, null, $this->host);
        }

        if ($this->_db->connect_error !== null) {
            throw new DatabaseException($this->_db->connect_error);
        }
        
        $this->_isConnected = true;
    }
    
    /**
     * Destruct
     */
    public function __destruct()
    {
        if ($this->_isConnected) {
            $this->close();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::close()
     */
    public function close()
    {
        return $this->_db->close();
    }
    
    /**
     * Turns on or off auto-committing database modifications
     *
     * @param boolean $mode
     * @return boolean
     */
    public function autocommit($mode)
    {
        return $this->_db->autocommit($mode);
    }
    
    /**
     * Performs a query on the database
     *
     * @param string $query
     * @return mixed
     */
    public function tquery($query)
    {
        return $this->_db->query($query);
    }
    
    /**
     * Commits the current transaction
     *
     * @return boolean
     */
    public function commit()
    {
        return $this->_db->commit();
    }
    
    /**
     * Rolls back current transaction
     *
     * @return boolean
     */
    public function rollback()
    {
        return $this->_db->rollback();
    }
    
    /**
     * Prepare an SQL statement for execution
     *
     * @return \mysqli_stmt|boolean
     */
    public function prepare()
    {
        return $this->_db->prepare();
    }

    /**
     * Returns the mysqli object
     *
     * @return \mysqli
     */
    public function DB()
    {
        return $this->_db;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::query()
     */
    public function query($query, array $options = null, $isTransaction = false)
    {
        $this->mysqlResult = new MysqlResult();
        $this->mysqlResult->setQuery($query);

        Logger::write($query, Logger::DEBUG, 'database');
     
        $query = trim($query);
        $command = substr($query, 0, strpos($query, " "));
        
        switch (strtoupper($command)) {
            case "SELECT":
                $this->mysqlResult->setType(MysqlResult::TYPE_SELECT);
                $return = "assoc";
                if (is_array($options) && isset($options["return"])) {
                    $return = $options["return"];
                }

                $return = $this->_fetch($query, $return);
                $this->mysqlResult->setErrno($this->_db->errno)->setError($this->_db->error);
                return $return;
            case "UPDATE":
                $this->mysqlResult->setType(MysqlResult::TYPE_UPDATE);
                $return = $this->_exec($query);
                $this->mysqlResult->setErrno($this->_db->errno)->setError($this->_db->error);
                return $return;
            case "INSERT":
                $this->mysqlResult->setType(MysqlResult::TYPE_INSERT);
                $return = $this->_insert($query);
                $this->mysqlResult->setErrno($this->_db->errno)->setError($this->_db->error);
                return $return;
            case "DELETE":
                $this->mysqlResult->setType(MysqlResult::TYPE_DELETE);
                $return = $this->_exec($query);
                $this->mysqlResult->setErrno($this->_db->errno)->setError($this->_db->error);
                return $return;
            case "SHOW":
                $this->mysqlResult->setType(MysqlResult::TYPE_SHOW);
                $return = "assoc";
                if (is_array($options) && isset($options["return"])) {
                    $return = $options["return"];
                }
                
                $return = $this->_fetch($query, $return);
                $this->mysqlResult->setErrno($this->_db->errno)->setError($this->_db->error);
                return $return;
            case "ALTER":
                $this->mysqlResult->setType(MysqlResult::TYPE_ALTER);
                $return = $this->_exec($query);
                $this->mysqlResult->setErrno($this->_db->errno)->setError($this->_db->error);
                return $return;
            default:
                return $this->_exec($query);
        }
        
        return null;
    }
    
    /**
     * Fetch
     *
     * @param string $query
     * @param string $return
     * @return multitype:array |NULL
     */
    protected function _fetch($query, $return = "assoc")
    {
        $result = $this->_db->query($query);
        if ($result) {
            $rows = array();
            
            switch ($return) {
                case "assoc":
                    while ($row = $result->fetch_assoc()) {
                        $rows[] = $row;
                    }
                    break;
                case "object":
                    while ($row = $result->fetch_object()) {
                        $rows[] = $row;
                    }
                    break;
            }
            
            $result->free();
            
            return $rows;
        }
    
        return null;
    }
    
    /**
     * Mysql Update or Delete
     *
     * @param string $query
     * @throws \jtl\Connector\Core\Exception\DatabaseException
     * @return number|boolean
     */
    protected function _exec($query)
    {
        if ($this->_db->query($query)) {
            if (intval($this->_db->affected_rows) > 0) {
                return $this->_db->affected_rows;
            }
            
            return true;
        } else {
            throw new DatabaseException($this->_db->error, $this->_db->errno);
        }
    }
    
    /**
     * Sqlite Insert
     *
     * @param string $query
     * @throws \jtl\Connector\Core\Exception\DatabaseException
     * @return number|boolean
     */
    protected function _insert($query)
    {
        if ($this->_db->query($query) === true) {
            if (intval($this->_db->insert_id) > 0) {
                return $this->_db->insert_id;
            }
            
            return true;
        } else {
            throw new DatabaseException($this->_db->error, $this->_db->errno);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::isConnected()
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    /**
     * Set Options
     *
     * @param array $options
     */
    public function setOptions(array $options = null)
    {
        if ($options !== null && is_array($options)) {
            // Host
            if (isset($options["host"]) && is_string($options["host"]) && strlen($options["host"]) > 0) {
                $this->host = $options["host"];
            }
            
            // User
            if (isset($options["user"]) && is_string($options["user"]) && strlen($options["user"]) > 0) {
                $this->user = $options["user"];
            }
            
            // Password
            if (isset($options["password"]) && is_string($options["password"]) && strlen($options["password"]) > 0) {
                $this->password = $options["password"];
            }
            
            // Name
            if (isset($options["name"]) && is_string($options["name"]) && strlen($options["name"]) > 0) {
                $this->name = $options["name"];
            }
        }
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::escapeString()
     */
    public function escapeString($query)
    {
        return $this->_db->real_escape_string($query);
    }
    
    /**
     * Insert Row
     *
     * @param object $obj
     * @param string $table
     * @param array $ignores
     * @return \jtl\Connector\Core\Result\Mysql
     */
    public function insertRow($obj, $table, array $ignores = null)
    {
        $result = new MysqlResult();
        $result->setType(MysqlResult::TYPE_INSERT);
        
        if (is_object($obj) && strlen($table) > 0) {
            $query = "INSERT INTO " . $this->escapeString($table) . " SET ";
    
            $sets = array();
            $value = "";
    
            $members = array_keys(get_object_vars($obj));
            if (is_array($members) && count($members) > 0) {
                foreach ($members as $member) {
                    if ($ignores !== null && is_array($ignores) && count($ignores) > 0) {
                        if (in_array($member, $ignores)) {
                            continue;
                        }
                    }
                    
                    if (!is_array($obj->$member) && !is_object($obj->$member)) {
                        $value = "'" . $this->escapeString($obj->$member) . "'";
                        if ($obj->$member === null) {
                            $value = "NULL";
                        }
                        
                        $sets[] = "{$member} = {$value}";
                    }
                }
            }
    
            $query .= implode(', ', $sets);
            
            try {
                $result->setQuery($query);
                $return = $this->query($query);
                if ($return !== true) {
                    $result->setKey($return);
                }
            } catch (DatabaseException $exc) {
                $result->setErrno($exc->getCode())
                    ->setError($exc->getMessage());
            }
        } else {
            $result->setError("No object or no table was given")
                ->setErrno(900);
        }
        
        $this->mysqlResult = $result;
        
        return $result;
    }
    
    /**
     * Update Row
     *
     * @param object $obj
     * @param string $table
     * @param string|array $key
     * @param string|array $value
     * @param array $ignores
     * @return \jtl\Connector\Core\Result\Mysql
     */
    public function updateRow($obj, $table, $key, $value, array $ignores = null)
    {
        $result = new MysqlResult();
        $result->setType(MysqlResult::TYPE_UPDATE)
            ->setKey($value);
        
        if (is_object($obj) && strlen($table) > 0) {
            if ((is_array($key) && is_array($value)) || (strlen($key) > 0 && strlen($value) > 0)) {
                $query = "UPDATE " . $this->escapeString($table) . " SET ";
                    
                $sets = array();
                $membervalue = "";
                
                $members = array_keys(get_object_vars($obj));
                if (is_array($members) && count($members) > 0) {
                    foreach ($members as $member) {
                        if ($ignores !== null && is_array($ignores) && count($ignores) > 0) {
                            if (in_array($member, $ignores)) {
                                continue;
                            }
                        }
    
                        if (!is_array($obj->$member) && !is_object($obj->$member)) {
                            $membervalue = "'" . $this->escapeString($obj->$member) . "'";
                            if ($obj->$member === null) {
                                $membervalue = "NULL";
                            }
                            
                            $sets[] = "{$member} = {$membervalue}";
                        }
                    }
    
                    $query .= implode(', ', $sets);
                        
                    if (is_array($key) && is_array($value)) {
                        if (count($key) == count($value)) {
                            foreach ($key as $i => $keyStr) {
                                if ($i > 0) {
                                    $query .= " AND {$keyStr} = '" . $this->escapeString($value[$i]) . "'";
                                } else {
                                    $query .= " WHERE {$keyStr} = '" . $this->escapeString($value[$i]) . "'";
                                }
                            }
                        } else {
                            $result->setError("The Parameters Key and Value have difference sizes")
                                ->setErrno(902);
                        }
                    } elseif (strlen($key) > 0 && strlen($value) > 0) {
                        $query .= " WHERE {$key} = '" . $this->escapeString($value) . "'";
                    }
                    
                    try {
                        $result->setQuery($query);
                        $return = $this->query($query);
                        if ($return !== true) {
                            $result->setAffected($return);
                        }
                    } catch (DatabaseException $exc) {
                        $result->setErrno($exc->getCode())
                            ->setError($exc->getMessage());
                    }
                } else {
                    $result->setError("Object has no members")
                        ->setErrno(903);
                }
            } else {
                $result->setError("The Parameters Key and Value are in a wrong format")
                    ->setErrno(901);
            }
        } else {
            $result->setError("No object or no table was given")
                ->setErrno(900);
        }
        
        $this->mysqlResult = $result;
        
        return $result;
    }
    
    /**
     * Delete Row
     *
     * @param object $obj
     * @param string $table
     * @param string|array $key
     * @param string|array $value
     * @return \jtl\Connector\Core\Result\Mysql
     */
    public function deleteRow($obj, $table, $key, $value)
    {
        $result = new MysqlResult();
        $result->setType(MysqlResult::TYPE_DELETE)
            ->setKey($value);
        
        if (is_object($obj) && strlen($table) > 0) {
            if ((is_array($key) && is_array($value)) || (strlen($key) > 0 && strlen($value) > 0)) {
                $query = "DELETE FROM " . $this->escapeString($table);
    
                if (is_array($key) && is_array($value)) {
                    if (count($key) == count($value)) {
                        foreach ($key as $i => $keyStr) {
                            if ($i > 0) {
                                $query .= " AND {$keyStr} = '" . $this->escapeString($value[$i]) . "'";
                            } else {
                                $query .= " WHERE {$keyStr} = '" . $this->escapeString($value[$i]) . "'";
                            }
                        }
                    } else {
                        $result->setError("The Parameters Key and Value have difference sizes")
                            ->setErrno(902);
                    }
                } elseif (strlen($key) > 0 && strlen($value) > 0) {
                    $query .= " WHERE {$key} = '" . $this->escapeString($value) . "'";
                }
    
                try {
                    $result->setQuery($query);
                    $return = $this->query($query);
                    if ($return !== true) {
                        $result->setAffected($return);
                    }
                } catch (DatabaseException $exc) {
                    $result->setErrno($exc->getCode())
                        ->setError($exc->getMessage());
                }
            } else {
                $result->setError("The Parameters Key and Value are in a wrong format")
                    ->setErrno(901);
            }
        } else {
            $result->setError("No object or no table was given")
                ->setErrno(900);
        }
        
        $this->mysqlResult = $result;
        
        return $result;
    }
    
    /**
     * Delete Insert Row
     *
     * @param object $obj
     * @param string $table
     * @param string|array $key
     * @param string|array $value
     * @param array $ignores
     * @return \jtl\Connector\Core\Result\Mysql
     */
    public function deleteInsertRow($obj, $table, $key, $value, array $ignores = null)
    {
        $this->deleteRow($obj, $table, $key, $value);
        
        $result = $this->insertRow($obj, $table);
        
        return $result;
    }

    /**
     * Name Set Support
     *
     * @param string $encoding
     */
    public function setCharset($encoding = 'utf8')
    {
        return $this->_db->set_charset($encoding);
    }

    /**
     * Character Set Support
     *
     * @param string $encoding
     */
    public function setNames($encoding = 'utf8')
    {
        return $this->_db->query("SET NAMES '{$encoding}'");
    }
}
