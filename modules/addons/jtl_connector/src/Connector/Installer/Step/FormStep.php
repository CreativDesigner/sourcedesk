<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Installer
 */
namespace jtl\Connector\Installer\Step;

/**
 * Description of FormStep
 *
 * @access public
 * @author Christian Spoo <christian.spoo@jtl-software.de>
 */
abstract class FormStep extends InstallerStep
{
    abstract protected function validateFormData();
    abstract protected function processFormData();
    
    public function run()
    {
        if (count($_POST) > 0) {
            // Form has been submitted
            if ($this->validateFormData()) {
                $this->processFormData();
            } else {
                // Throw error
            }
        }

        parent::run();
    }
}
