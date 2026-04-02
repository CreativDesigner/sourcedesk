<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Rpc
 */

namespace jtl\Connector\Core\Rpc;

use \jtl\Connector\Core\Utilities\RpcMethod;

class Method
{
    const ACTION_PULL = 'pull';
    const ACTION_PUSH = 'push';
    const ACTION_DELETE = 'delete';
    const ACTION_STATISTIC = 'statistic';

    /**
     * Rcp Method
     *
     * @var string
     */
    protected $rpcmethod;
    
    /**
     * Connector Controller
     *
     * @var string
     */
    protected $controller;
    
    /**
     * Connector Action
     *
     * @var string
     */
    protected $action;
    
    /**
     * Constructor
     *
     * @param string $rpcmethod
     * @param string $controller
     * @param string $action
     */
    public function __construct($rpcmethod = null, $controller = null, $action = null)
    {
        $this->rpcmethod = $rpcmethod;
        $this->controller = $controller;
        $this->action = $action;
    }
    
    /**
     * Rpc Method Getter
     *
     * @return string
     */
    public function getRpcMethod()
    {
        return $this->rpcmethod;
    }

    /**
     * Controller Getter
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Action Getter
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Commit check
     *
     * @return boolean
     */
    public function isCommit()
    {
        return ($this->action !== null && $this->action == "commit");
    }
    
    /**
     * Core check
     *
     * @return boolean
     */
    public function isCore()
    {
        if ($this->getRpcMethod() !== false && strpos($this->getRpcMethod(), "core.") !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Method Setter
     *
     * @param string $rpcmethod
     * @return \jtl\Connector\Core\Rpc\Method
     */
    public function setRpcMethod($rpcmethod)
    {
        $this->rpcmethod = $rpcmethod;
        return $this;
    }

    /**
     * Controller Setter
     *
     * @param string $controller
     * @return \jtl\Connector\Core\Rpc\Method
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Action Setter
     *
     * @param string $action
     * @return \jtl\Connector\Core\Rpc\Method
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
}
