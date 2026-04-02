<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Application
 */

namespace jtl\Connector\Authentication;

interface ITokenLoader
{
    /**
     * Loads the connector token
     *
     * @return string
     */
    public function load();
}