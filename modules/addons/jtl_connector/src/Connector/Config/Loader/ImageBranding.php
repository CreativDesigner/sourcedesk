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
 * Shop3 Imagebranding class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class ImageBranding extends BaseLoader
{

    const GROUP_ALL = 0;

    /**
     * @var jtl\Connector\Core\Database\Mysql
     */
    protected $db;

    /**
     * @var array
     */
    protected $_brandinggroups = array();

    /**
     * @var array
     */
    protected $_brandingsettings = array();

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
        $this->db = Mysql::getInstance();
        if (empty($this->db)) {
            throw new ConfigException('Unable to retrieve database instance', 100);
        }
    }
}
