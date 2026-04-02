<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Importer;

use \jtl\Connector\Feature\Importer\Base as BaseImporter;
use \jtl\Connector\Feature\Exception\Importer as ExceptionImporter;
use \jtl\Connector\Core\Serializer\Json as SerializerJson;

/**
 * Json importer class
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Json extends BaseImporter
{

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $_name = 'Json';

    /**
     * Creates the instance.
     *
     * @param string $json The full filename of a JSON file that will be read
     * inside of the load method.
     */
    public function __construct($json)
    {
        $this->file = $json;
    }

    /**
     * Reads the feature file defined in the constructor and uses the JSON
     * serializer to return all datas as associative array.
     *
     * @return array
     * @throws ExceptionImporter
     */
    public function load()
    {
        try {
            $content = file_get_contents($this->file);
        } catch (\Exception $e) {
            throw new ExceptionImporter(sprintf('Unable to load your file "%s", message "%s"', $this->file, $e->getMessage()));
        }
        return SerializerJson::decode($content, true);
    }
}
