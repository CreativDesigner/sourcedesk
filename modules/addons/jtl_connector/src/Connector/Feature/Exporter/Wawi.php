<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Exporter;

use \jtl\Connector\Feature\Exporter\Base as BaseExporter;

/**
 * Wawi exporter class
 *
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Wawi extends BaseExporter
{

    /**
     * Dummy exporter that will forward the array as export to be conform with
     * the standards.
     *
     * @param array $array The default feature array.
     * @return array
     */
    public function export($array)
    {
        return $array;
    }
}
