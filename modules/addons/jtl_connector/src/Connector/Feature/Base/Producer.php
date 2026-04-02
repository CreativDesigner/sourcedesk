<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Base;

use \jtl\Connector\Feature\Base\Base as Baseclass;
use \jtl\Connector\Feature\Exporter\IExporter;
use \jtl\Connector\Feature\Importer\IImporter;
use \jtl\Connector\Feature\Manager;
use \jtl\Connector\Feature\Feature\Feature;
use \jtl\Connector\Feature\Group\Standard as GroupStandard;
use \jtl\Connector\Feature\Base\IProducer;

/**
 * Base class for the producer class.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
abstract class Producer extends Baseclass implements IProducer
{

    const FEATURES_KEY = 'features';

    /**
     * @var string
     */
    protected $_name = 'Base/Producer';

    /**
     * @var \jtl\Connector\Feature\Importer\IImporter
     */
    protected $_importer;

    /**
     * @var array
     */
    protected $_importer_data;

    /**
     * @var array of \jtl\Connector\Feature\Feature\IFeature;
     */
    protected $_features;

    /**
     * @var \jtl\Connector\Feature\Group\IGroup
     */
    protected $_active_group;

    /**
     * @var array of \jtl\Connector\Feature\Group\IGroup
     */
    protected $_groups;

    /**
     * @var array of \jtl\Connector\Feature\Method\IMethod
     */
    protected $_methods;

    /**
     * @var array
     */
    protected $_parameters;

    /**
     * @var string
     */
    protected $_methods_cmp_str;

    /**
     * @var string
     */
    protected $_parameters_cmp_str;

    /**
     * @var \jtl\Connector\Feature\Manager
     */
    protected $_manager;

    /**
     * Creates the producer instance.
     *
     * @param \jtl\Connector\Feature\Manager $manager Our manager as reference
     * back.
     */
    public function __construct(Manager $manager = null)
    {
        $this->name = 'Manager';
        $this->_manager = $manager;
    }

    abstract protected function parse();

    /**
     * Sets the manager.
     *
     * @param \jtl\Connector\Feature\Manager $manager
     */
    public function setManager(Manager $manager)
    {
        $this->_manager = $manager;
    }

    /**
     * Returns the manager.
     *
     * @return \jtl\Connector\Feature\Manager
     */
    public function getManager()
    {
        return $this->_manager;
    }

    /**
     * Returns the groups.
     *
     * @return array of \jtl\Connector\Feature\Group\IGroup
     */
    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * Returns the features.
     *
     * @return array of \jtl\Connector\Feature\Feature\IFeature
     */
    public function getFeatures()
    {
        return $this->_features;
    }

    /**
     * Returns the methods that the producer should look for.
     *
     * @return array of string
     */
    public function getMethods()
    {
        return $this->_methods;
    }

    /**
     * Starts the importing process.
     *
     * @param \jtl\Connector\Feature\Importer\IImporter $importer The importer
     * object, that implements the IImporter interface to be conform to this
     * manager/producer.
     *
     * @return mixed  The result of the parse call may be various.
     * @see self::parse()
     */
    public function import(IImporter $importer)
    {
        $this->_importer = $importer;
        return $this->parse();
    }

    /**
     * Starts the exporting process.
     *
     * @param \jtl\Connector\Feature\Exporter\IExporter $exporter The exporter
     * object, that implements the IExporter interface to be conform to this
     * manager/producer.
     *
     * @return mixed The result of the load call may be various.
     * @see $exporter->load()
     */
    public function export(IExporter $exporter)
    {
        return $exporter->export(array(
            self::FEATURES_KEY => $this->_importer_data
        ));
    }

    /**
     * Will do the import and export in one call.
     *
     * @param \jtl\Connector\Feature\Importer\IImporter $from The Importer
     * object, that implements the IImporter interface to be conform to this
     * manager/producer.
     * @param \jtl\Connector\Feature\Exporter\IExporter $to The Exporter
     * object, that implements the IExporter interface to be conform to this
     * manager/producer.
     * @return mixed The result of the export call may be various.
     */
    public function transform(IImporter $from, IExporter $to)
    {
        $this->import($from);
        return $this->export($to);
    }

    /**
     * Adds a feature to the producer.
     *
     * @param string $name The name of the new feature.
     * @param array $methods An array with the
     * @return \jtl\Connector\Feature\Base\Feature
     */
    protected function addFeature($name, array $methods)
    {
        $feature = new Feature($name);
        foreach ($methods as $method => $parameters) {
            $feature->addMethod($this->createMethod($method, $parameters));
        }
        $this->_features[] = $feature;
        return $feature;
    }

    /**
     * Adds a group to the producer.
     *
     * @param string $name The name of th new group.
     * @param array $value The params for the new group.
     * @return \jtl\Connector\Feature\Group\IGroup
     */
    protected function addGroup($name, &$value)
    {
        $class = 'jtl\\Connector\\Feature\\Group\\' . ucfirst($name);
        if (in_array($class, $this->getClasses())) {
            $group = new $class($value);
        } else {
            $group = new GroupStandard($name);
        }
        $this->_active_group = $group;
        $this->_groups[] = $group;
        return $group;
    }

    /**
     * Adds a method to the producer that will be assigned to a feature.
     *
     * @param string $method The name of the new method.
     * @param array $params The params array for the new method.
     * @return \jtl\Connector\Feature\Method\IMethod
     * @throws ExceptionProducer If the setter for a parameter doesn't exists in
     * the method class, we need to inform the caller about the inconsistency.
     */
    protected function createMethod($method, array $params)
    {
        $class = '\\jtl\\Connector\\Feature\\Method\\' . ucfirst($method);
        try {
            $obj = new $class($method);
            foreach ($params as $name => $value) {
                $obj->{$name} = $value;
            }
        } catch (\Exception $e) {
            // @codeCoverageIgnoreStart
            throw new ExceptionProducer(sprintf('The method "%s" doesn\'t exist, message "%s"', $method, $e->getMessage()));
            // @codeCoverageIgnoreEnd
        }
        return $obj;
    }
}
