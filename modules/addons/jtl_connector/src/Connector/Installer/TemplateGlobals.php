<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Installer
 */
namespace jtl\Connector\Installer;

/**
 * Description of TemplateGlobals
 *
 * @access public
 * @author Christian Spoo <christian.spoo@jtl-software.de>
 */
class TemplateGlobals extends \Twig_Extension
{
    public function getName()
    {
        return 'jtl_template_globals';
    }
    
    public function getGlobals()
    {
        return array(
            'app_base' => defined('INSTALLER_BASE_URI') ? INSTALLER_BASE_URI . '/' : __DIR__ . '/../../../../install'
        );
    }
}
