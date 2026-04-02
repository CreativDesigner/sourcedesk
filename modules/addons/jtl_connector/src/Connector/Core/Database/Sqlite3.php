<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Database
 */
namespace jtl\Connector\Core\Database;

use \jtl\Connector\Core\Exception\DatabaseException;

/**
 * Sqlite 3 Database Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Sqlite3 implements IDatabase
{
    /**
     * Sqlite 3 sharedcache value
     *
     * @var integer
     */
    const SQLITE3_OPEN_SHAREDCACHE = 0x00020000;

    /**
     * Database connection state
     *
     * @var bool
     */
    protected $isConnected = false;
    
    /**
     * Sqlite 3 Database object
     *
     * @var Sqlite3 | NULL
     */
    protected $db;
    
    /**
     * Path to the SQLite database, or :memory: to use in-memory database.
     *
     * @var string
     */
    public $location;
    
    /**
     * Optional flags used to determine how to open the SQLite database.
     *
     * @var integer
     */
    public $mode;
    
    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::connect()
     * @throws \jtl\Connector\Core\Exception\DatabaseException
     */
    public function connect(array $options = null)
    {
        $this->setOptions($options);
        if (!is_string($this->location) || strlen($this->location) == 0) {
            throw new DatabaseException('Wrong type or empty location');
        }
        
        if ($this->mode === null) {
            $this->mode = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE | self::SQLITE3_OPEN_SHAREDCACHE;
        }
        
        try {
            $this->db = new \Sqlite3($this->location, $this->mode);
            $this->db->busyTimeout(2000);
            
            $this->isConnected = true;
        } catch (\Exception $exc) {
            throw new DatabaseException($exc->getMessage() . ' "' . $this->location . '"');
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->isConnected) {
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
        return $this->db->close();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::query()
     */
    public function query($query)
    {
        $command = substr($query, 0, strpos($query, ' '));
        
        switch (strtoupper($command)) {
            case "SELECT":
                return $this->fetch($query);
            case "UPDATE":
                return $this->_exec($query);
            case "INSERT":
                return $this->insert($query);
            case "DELETE":
                return $this->_exec($query);
        }
        
        return null;
    }

    public function fetchSingle($query)
    {
        return $this->db->querySingle($query);
    }
    
    /**
     * Prepares an SQL statement for execution
     *
     * @param string $query
     * @return SQLite3Stmt|boolean Returns an SQLite3Stmt object on success or FALSE on failure.
     */
    public function prepare($query)
    {
        return $this->db->prepare($query);
    }
    
    /**
     * Sqlite Select
     *
     * @param string $query
     * @return multitype:array |NULL
     */
    public function fetch($query)
    {
        while (true) {
            $result = @$this->db->query($query);
            if ($result instanceof \SQLite3Result) {
                $rows = array();
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $rows[] = $row;
                }
            
                return $rows;
            } else {
                // Check if DB is locked
                switch ($this->db->lastErrorCode()) {
                    case 5: // SQLITE_BUSY
                        continue;
                    default:
                        return null;
                }
            }
        };
        
        return null;
    }
    
    /**
     * Sqlite Update or Delete
     *
     * @param string $query
     * @return boolean
     */
    protected function _exec($query)
    {
        return $this->db->exec($query);
    }
    
    /**
     * Sqlite Insert
     *
     * @param string $query
     * @return number|boolean
     */
    public function insert($query)
    {
        if ($this->db->exec($query)) {
            return $this->db->lastInsertRowID();
        }
        
        return false;
    }

    /**
     * Executes a result-less query against a given database
     *
     * @param string $query
     */
    public function exec($query)
    {
        return $this->db->exec($query);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Database\IDatabase::isConnected()
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * Set Options
     *
     * @param array $options
     */
    public function setOptions(array $options = null)
    {
        if ($options !== null && is_array($options)) {
            // Location
            if (isset($options["location"]) && is_string($options["location"]) && strlen($options["location"]) > 0) {
                $this->location = $options["location"];
            }
            
            // Mode
            if (isset($options["mode"]) && is_int($options["mode"])) {
                $this->mode = $options["mode"];
            }
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \jtl\Connector\Core\Database\IDatabase::escapeString()
     */
    public function escapeString($query)
    {
        return \Sqlite3::escapeString($query);
    }

    public function getLastInsertRowId()
    {
        return $this->db->lastInsertRowID();
    }
}
