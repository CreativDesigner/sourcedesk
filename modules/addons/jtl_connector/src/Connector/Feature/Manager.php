<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature;

use \jtl\Connector\Feature\Base\Base as BaseClass;
use \jtl\Connector\Feature\Exception\Manager as ExceptionManager;
use \jtl\Connector\Feature\Producer;
use \jtl\Connector\Feature\Importer\IImporter;
use \jtl\Connector\Feature\Exporter\IExporter;

/**
 * Manager class for the feature parser of the famous JTL connector.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
final class Manager extends BaseClass
{

    /**
     * @var array
     */
    protected $_methods = array();

    /**
     * @var array
     */
    protected $_parameters = array();

    /**
     * @var jtl\Connector\Feature\Producer
     */
    protected $_producer;

    /**
     * Constructor.
     *
     * @param \jtl\Connector\Feature\Producer $producer The base class instance
     * that is required to import and export our feature.
     */
    public function __construct(Producer $producer = null)
    {
        $this->name = 'Manager';
        if (!empty($producer)) {
            $this->_producer = $producer;
            $this->_producer->setManager($this);
        }
    }

    /**
     * Sets the producer, the base class of this converting manager.
     *
     * @param \jtl\Connector\Feature\Producer $producer
     */
    public function setProducer(Producer $producer)
    {
        $producer->setManager($this);
        $this->_producer = $producer;
    }

    /**
     * Returns the Producer.
     *
     * @return jtl\Connector\Feature\Producer
     */
    public function getProducer()
    {
        return $this->_producer;
    }

    /**
     * Returns the parameters that will be extraced during the import/export.
     *
     * @return array Standard key value array.
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Registers a parameter where the producer will looking for during the
     * import/export phase.
     *
     * @param string $name The name of the needed parameter.
     * @throws ExceptionManager If the parameter is already added, we need to
     * notify the caller of his inconsistency.
     */
    public function registerParameter($name)
    {
        if ($this->existsParameter($name)) {
            throw new ExceptionManager(sprintf('The parameter "%s" is already registered', $name));
        }
        $this->_parameters[$name] = true;
    }

    /**
     * Registers multiple parameters.
     *
     * @param array $parameters Standard key value array.
     * @see self::registerParameter()
     */
    public function registerParameters(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->registerParameter($value);
        }
    }

    /**
     * Checks if a parameter already exists and returns the result as boolean.
     *
     * @param string $name The parameter name.
     * @return bool If the parameter already exists TRUE, otherwhise FALSE.
     */
    protected function existsParameter($name)
    {
        return (isset($this->_parameters) && array_key_exists($name, $this->_parameters));
    }

    /**
     * Returns the methods that will be extraced during the import/export.
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->_methods;
    }

    /**
     * Registers a method by name where the producer will look for during the
     * import/export phase.
     *
     * @param string $name The name of the method.
     * @throws ExceptionManager If the method is already added we need to notify
     * the caller about his inconsistency.
     */
    public function registerMethod($name)
    {
        if ($this->existsMethod($name)) {
            throw new ExceptionManager(sprintf('The method "%s" is already registered', $name));
        }
        $this->_methods[$name] = true;
    }

    /**
     * Registers multiple methods at once.
     *
     * @param array $methods Standard key/value array.
     * @see self::registerMethod()
     */
    public function registerMethods(array $methods)
    {
        foreach ($methods as $key => $value) {
            $this->registerMethod($value);
        }
    }

    /**
     * Checks if a method already exists and returns the result as boolean.
     *
     * @param string $name The parameter name.
     * @return bool If the parameter already exists TRUE, otherwhise FALSE.
     */
    protected function existsMethod($name)
    {
        return (isset($this->_methods) && array_key_exists($name, $this->_methods));
    }

    /**
     * Will check if the producer exists.
     * If there is no producer, we need to inform the caller about his
     * inconsistency.
     *
     * @param string $type The type of process, this should usually be 'Import'
     * or 'Export'.
     * @throws ExceptionManager If there is no producer, we need to inform the
     * caller about his inconsistency.
     */
    protected function checkProducer($type = 'Import')
    {
        if (empty($this->_producer)) {
            throw new ExceptionManager(sprintf('%s not possible, producer missing!', $type));
        }
    }

    /**
     * Starts the importing process in the producer.
     *
     * @param \jtl\Connector\Feature\Importer\IImporter $importer The importer
     * object, that implements the IImporter interface to be conform to this
     * manager/producer.
     *
     * @return array The imported feature array.
     */
    public function import(IImporter $importer)
    {
        $this->checkProducer('Import');
        return $this->_producer->import($importer);
    }

    /**
     * Starts the exporting process in the producer.
     *
     * @param \jtl\Connector\Feature\Exporter\IExporter $export The exporter
     * object, that implements the IExporter interface to be conform to this
     * manager/producer.
     *
     * @return mixed The result of the export call may be various.
     */
    public function export(IExporter $export)
    {
        $this->checkProducer('Export');
        return $this->_producer->export($export);
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
     * @return mixed The result of the transform call may be various.
     */
    public function transform(IImporter $from, IExporter $to)
    {
        $this->checkProducer('Transform');
        return $this->_producer->transform($from, $to);
    }
}
