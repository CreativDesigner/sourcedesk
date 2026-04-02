<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Validator
 */
namespace jtl\Connector\Core\Validator;

use \jtl\Connector\Core\Utilities\Singleton;
use \jtl\Connector\Core\Model\Model as MainModel;

/**
 * Global Model
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Model extends Singleton
{
    /**
     *
     * @param mixed $data
     */
    public function validate($data)
    {
        if ($data instanceof MainModel) {
            return $data->validate();
        }
    }
}
