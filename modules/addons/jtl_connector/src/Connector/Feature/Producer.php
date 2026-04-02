<?php

namespace jtl\Connector\Feature;

use \jtl\Connector\Feature\Base\Producer as BaseProducer;
use \jtl\Connector\Feature\Exception\Producer as ExceptionProducer;

class Producer extends BaseProducer
{

    /**
     * Will start the load process inside of the importer and validates the datas
     * that will be returned by this method.
     *
     * @throws ExceptionProducer If the datas param contains no array, is empty,
     * or doesn't contain the feature key we need to inform the caller about
     * the inconsistency.
     */
    protected function loadAndValidate()
    {
        if (empty($this->_importer)) {
            throw new ExceptionProducer('Importer missing!');
        }
        $datas = $this->_importer->load();
        if (empty($datas)) {
            throw new ExceptionProducer(sprintf('The importer "%s" is unable to serve your request', $this->_importer->getName()));
        }
        if (!is_array($datas)) {
            throw new ExceptionProducer(sprintf('The importer "%s" served invalid datas: %s ', $this->_importer->getName(), var_export($datas, true)));
        }
        if (!isset($datas[self::FEATURES_KEY])) {
            throw new ExceptionProducer(sprintf('Can\'t find "%s" as a key for your datas', self::FEATURES_KEY));
        }
        $this->_importer_data = $datas[self::FEATURES_KEY];
    }

    /**
     * Loads the importer, validation and extracting of the datas.
     */
    public function parse()
    {
        $this->loadAndValidate();
        $this->extractLayers();
    }

    /**
     * Extracts the different layers, methods and parameters.
     *
     * @throws ExceptionProducer If there are no methods or parameters, we need
     * to notify the caller about the inconsistency.
     */
    protected function extractLayers()
    {
        $methods = $this->_manager->getMethods();
        if (empty($methods)) {
            throw new ExceptionProducer('Unable to extract layers, methods missing!');
        }
        $this->_methods = array_keys($methods);
        asort($this->_methods);
        $this->_methods_cmp_str = implode(':', $this->_methods);

        $parameters = $this->_manager->getParameters();
        if (empty($parameters)) {
            throw new ExceptionProducer('Unable to extract layers, parameters missing!');
        }
        $this->_parameters = $parameters;
        asort($this->_parameters);
        $this->_parameters_cmp_str = implode(':', $this->_parameters);

        asort($this->_importer_data);
        foreach ($this->_importer_data as $key => &$value) {
            $this->extractLayer($key, $value);
        }
    }

    /**
     * Extracts the actual layer with a given key, manage the parent and adds
     * (if found) features, groups and childrens to the producer.
     *
     * @param string $key The actual key inside of the feature array.
     * @param mixed $layer The data.
     * @param type $parent If there is a active group, all children will be added
     * to the parent.
     */
    protected function extractLayer($key, &$layer, $parent = null)
    {
        if (is_array($layer)) {
            $n_keys = array_keys($layer);
            asort($n_keys);
            if (implode(':', $n_keys) == $this->_methods_cmp_str) { //Feature
                $child = $this->addFeature($key, $layer);
            } else { //Group
                $child = $this->addGroup($key, $layer);
                foreach ($layer as $fkey => $value) {
                    $this->extractLayer($fkey, $value, $child);
                }
            }
        }
        if (isset($child) && !empty($parent)) {
            $parent->addChildren($child);
        }
    }
}
