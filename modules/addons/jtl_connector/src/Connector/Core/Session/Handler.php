<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Session
 */
namespace jtl\Connector\Core\Session;

use \jtl\Connector\Core\Database\IDatabase;
use \jtl\Connector\Core\Database\Mapper;
use \jtl\Connector\Core\Exception\SessionException;
use \jtl\Connector\Core\Logger\Logger;

/**
 * Session Handler
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
abstract class Handler
{
    /**
     * Session Database Mapper
     *
     * @var \jtl\Connector\Core\Database\IDatabase
     */
    protected $_db;
    
    /**
     * Session GC Lifetime
     *
     * @var integer
     */
    protected $_lifetime;
    
    /**
     * Session Id
     *
     * @var string
     */
    protected $_sessionId;

    /**
     * Constructor
     *
     * @param  \jtl\Connector\Core\Database\IDatabase $db
     * @param  $sessionId Connector session ID
     * @param  $sessionName PHP session Name
     * @throws \jtl\Connector\Core\Exception\SessionException
     */
    public function __construct(IDatabase $db, $sessionId = null, $sessionName = "jtlConnector")
    {
        $this->_db = $db;
        
        ini_set("session.gc_probability", 25);
        
        session_name($sessionName);
        if ($sessionId !== null) {
            if ($this->check($sessionId)) {
                session_id($sessionId);
            } else {
                throw new SessionException("Session is invalid", -32000);
            }
        }
        
        session_set_save_handler(array(
            &$this,
            'open'
        ), array(
            &$this,
            'close'
        ), array(
            &$this,
            'read'
        ), array(
            &$this,
            'write'
        ), array(
            &$this,
            'destroy'
        ), array(
            &$this,
            'gc'
        ));

        register_shutdown_function('session_write_close');

        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            session_regenerate_id(true);
        }
        
        session_start();

        $this->_lifetime = ((int) ini_get('session.gc_maxlifetime') > 0) ? (int) ini_get('session.gc_maxlifetime') : 7200;
        $this->_sessionId = session_id();

        Logger::write(sprintf('Session started with id (%s)', session_id()), Logger::DEBUG, 'session');
    }
    
    /**
     * Checks if Session is Valid
     *
     * @param string $sessionId
     * @return boolean
     */
    public function check($sessionId)
    {
        $sessionId = $this->_db->escapeString($sessionId);
        
        $rows = $this->_db->query("SELECT sessionData
                                        FROM session
                                        WHERE sessionId = '{$sessionId}'
                                            AND sessionExpires >= " . time());

        Logger::write(sprintf('Check session with id (%s) and time (%s) ...', $sessionId, time()), Logger::DEBUG, 'session');
        
        if ($rows !== null && isset($rows[0])) {
            Logger::write('Session is valid', Logger::DEBUG, 'session');

            return true;
        }
        
        Logger::write('Session is invalid', Logger::DEBUG, 'session');

        return false;
    }

    /**
     * Open Session
     */
    public function open($savePath, $sessionName)
    {
        //$this->_lifetime = get_cfg_var("session.gc_maxlifetime");

        Logger::write(sprintf('Open session with savePath (%s) and sessionName (%s)', $savePath, $sessionName), Logger::DEBUG, 'session');
        
        return $this->_db->isConnected();
    }

    /**
     * Close Sesssion
     */
    public function close()
    {
        Logger::write('Close session', Logger::DEBUG, 'session');

        return true;
    }

    /**
     * Read Session
     */
    public function read($sessionId)
    {
        $sessionId = $this->_db->escapeString($sessionId);
        
        $rows = $this->_db->query("SELECT sessionData
					        			FROM session
					        			WHERE sessionId = '{$sessionId}'
                                            AND sessionExpires >= " . time());

        Logger::write(sprintf('Read session with id (%s)', $sessionId), Logger::DEBUG, 'session');
        
        if ($rows !== null && isset($rows[0])) {
            $row = $rows[0];
            if (isset($row["sessionData"]) && strlen($row["sessionData"]) > 0) {
                return base64_decode($row["sessionData"]);
            }
        }
        
        return "";
    }

    /**
     * Write Session
     */
    public function write($sessionId, $sessionData)
    {
        $sessionId = $this->_db->escapeString($sessionId);
        $sessionData = base64_encode($sessionData);
        $newExpire = time() + $this->_lifetime;
        
        $rows = $this->_db->query("SELECT sessionData
									FROM session
								    WHERE sessionId = '{$sessionId}'");

        Logger::write(sprintf('Write session with id (%s)', $sessionId), Logger::DEBUG, 'session');
        
        if ($rows !== null && isset($rows[0])) {
            $stmt = $this->_db->prepare("UPDATE session SET sessionData=:data WHERE sessionId=:sessionid");
            $stmt->bindValue(":data", $sessionData, SQLITE3_TEXT);
            $stmt->bindValue(":sessionid", $sessionId, SQLITE3_TEXT);
           
            $result = $stmt->execute();
            if ($result) {
                return true;
            }
        } else {
            $stmt = $this->_db->prepare("INSERT INTO session (sessionId, sessionExpires, sessionData) VALUES(:sessionid, :expire, :data)");
            $stmt->bindValue(":sessionid", $sessionId, SQLITE3_TEXT);
            $stmt->bindValue(":expire", $newExpire, SQLITE3_INTEGER);
            $stmt->bindValue(":data", $sessionData, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            if ($result) {
                return true;
            }
        }
        
        return true;
    }

    /**
     * Destroy Session
     */
    public function destroy($sessionId)
    {
        $sessionId = $this->_db->escapeString($sessionId);

        Logger::write(sprintf('Destroy session with id (%s)', $sessionId), Logger::DEBUG, 'session');
        
        return $this->_db->query("DELETE FROM session WHERE sessionId = '{$sessionId}'");
    }

    /**
     * Garbage Collector
     */
    public function gc($maxLifetime)
    {
        Logger::write(sprintf('GC session with maxLifetime (%s)', $maxLifetime), Logger::DEBUG, 'session');

        return $this->_db->query("DELETE FROM session WHERE sessionExpires < " . time());
    }
    
    /**
     * SessionId Getter
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->_sessionId;
    }
    
    /**
     * Lifetime Getter
     *
     * @return number
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }
}
