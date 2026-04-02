<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Shop3
 */

namespace jtl\Connector\Config\Loader;

use \jtl\Connector\Core\Exception\ConfigException;
use \jtl\Connector\Core\Config\Loader\Base as BaseLoader;
use \jtl\Connector\Core\Database\Mysql;

/**
 * Email Config class.
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
abstract class Email extends BaseLoader
{
    /**
     * @var jtl\Connector\Core\Database\Mysql
     */
    protected $_db;
    
    /**
     * Constructor.
     *
     * This loader needs database access.
     */
    public function __construct()
    {
        $this->checkDb();
    }
    
    /**
     * Checks if the database connection is empty.
     *
     * @throws ConfigException
     */
    public function checkDb()
    {
        $this->_db = Mysql::getInstance();
        if (empty($this->_db)) {
            throw new ConfigException('Unable to retrieve database instance', 100);
        }
    }
}
