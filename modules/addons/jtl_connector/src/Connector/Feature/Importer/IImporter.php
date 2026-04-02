<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Importer;

/**
 * Importer interface
 *
 * @author David Spickers <david.spickers@jtl-software.de>
 */
interface IImporter
{

    /**
     * Imports the features array with the given importer.
     *
     * @return array The features array, in the following format:
     * array(
     *  'features' => array(
     *      'group' => array(
     *          'feature1_name' => array(
     *              'method1' => array(
     *                  'param1' => 'param1_value',
     *                  'param2' => 'param2_value'
     *              ),
     *              'methodN' => array(
     *                  'param1' => 'param1_value',
     *                  'param2' => 'param2_value'
     *              )
     *          ),
     *          'featureN_name' => array() ...
     *      )
     *  )
     * );
     */
    public function load();
}
