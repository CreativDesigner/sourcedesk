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
use \jtl\Connector\Linker\IdentityLinker;

/**
 * Base Linker Controller
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.com>
 */
class Linker extends CoreController
{
    public function clear()
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $identityLinker = IdentityLinker::getInstance();

            $action->setResult($identityLinker->clear());
        } catch (\Exception $e) {
            $err = new Error();
            $err->setCode($e->getCode());
            $err->setMessage($e->getMessage());
            $action->setError($err);
        }

        return $action;
    }
}
