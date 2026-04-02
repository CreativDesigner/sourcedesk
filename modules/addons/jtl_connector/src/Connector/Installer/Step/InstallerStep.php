<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Installer
 */
namespace jtl\Connector\Installer\Step;

use \jtl\Connector\Installer\Installer;

/**
 * Description of InstallerStep
 *
 * @access public
 * @author Christian Spoo <christian.spoo@jtl-software.de>
 */
abstract class InstallerStep
{
    protected $_template;
    
    /**
     * Template data;
     *
     * @var array
     */
    private $_data = array();
    
    /**
     * Installer object
     *
     * @var \jtl\Connector\Installer\Installer
     */
    protected $_installer = null;
    
    /**
     * Setter method for the template parameters
     *
     * @param string $key
     * @param mixed $value
     */
    protected function addParameter($key, $value)
    {
        $this->_data[$key] = $value;
    }
    
    /**
     * Delete method for template parameters
     *
     * @param string $key
     */
    protected function deleteParameter($key)
    {
        unset($this->_data[$key]);
    }
    
    public function __construct(Installer $installer)
    {
        $this->_installer = $installer;
    }
    
    /**
     * Executes the current step
     *
     * @return type
     */
    public function run()
    {
        $templateParams = array(
            'page_first_step_url'   => $this->_installer->stepUrl(1),
            'page_prev_step_url'    => $this->_installer->stepUrl($this->_installer->currentStep() - 1),
            'page_next_step_url'    => $this->_installer->stepUrl($this->_installer->currentStep() + 1)
        );
        
        echo Installer::$twig->render($this->_template . '.html.twig', array_merge($templateParams, $this->_data));
    }
}
