<?php

/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Application
 */

namespace jtl\Connector\Controller;

use \jtl\Connector\Core\Controller\Controller as CoreController;
use \jtl\Connector\Result\Action;
use \jtl\Connector\Core\Rpc\Error;
use \jtl\Connector\Feature\Manager;
use \jtl\Connector\Feature\Producer;
use \jtl\Connector\Feature\Importer\Json as JsonImporter;
use \jtl\Connector\Feature\Exporter\Wawi as WawiExporter;

/**
 * Base Features Controller
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Feature extends CoreController
{

    /**
     * Returns what this connector is supporting.
     *
     * @param mixed $params An array of parameters to read
     */
    public function pull($params = null)
    {
        unset($params); //We don't need the params parameter
        $man = new Manager(new Producer()); //Create our feature manager instance
        $man->registerMethods(array('pull', 'push')); //Register the methods where we're looking for
        $man->registerParameters(array('supported', 'comment')); //Register the parameters where we're looking for
        $ret = new Action();
        try {
            $datas = $man->transform(
                new JsonImporter(CONNECTOR_DIR . '/config/features.json'), //
                new WawiExporter()
            );
            $ret->setResult($datas);
            unset($man);
            $ret->setHandled(true);
        } catch (\Exception $e) {
            if (isset($e->jtl)) {
                $ret->setHandled($e->jtl);
            } else {
                $ret->setHandled(false);
            }
            $err = new Error();
            $err->setCode($e->getCode());
            $err->setMessage($e->getMessage());
            $ret->setError($err);
        }
        return $ret;
    }
}
