<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Base
 */
namespace jtl\Connector\Base;

use jtl\Connector\Authentication\ITokenValidator;
use \jtl\Connector\Core\Rpc\RequestPacket;
use \jtl\Connector\Application\IEndpointConnector;
use \jtl\Connector\Core\Utilities\Singleton;
use \jtl\Connector\Core\Utilities\RpcMethod;
use \jtl\Connector\Core\Rpc\Method;
use \jtl\Connector\Mapper\IPrimaryKeyMapper;
use \jtl\Connector\Authentication\ITokenLoader;
use \jtl\Connector\Checksum\IChecksumLoader;
use \Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Base Connector
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Connector extends Singleton implements IEndpointConnector
{
    protected $controller;
    protected $keyMapper;
    protected $tokenLoader;
    protected $tokenValidator;
    protected $checksumLoader;
    protected $eventDispatcher;
    protected $method;
    protected $useSuperGlobals = true;
    protected $modelNamespace = 'jtl\Connector\Model';

    public function initialize()
    {
        
    }

    /**
     * Setter primary key mapper
     *
     * @param \jtl\Connector\Mapper\IPrimaryKeyMapper $mapper
     * @return \jtl\Connector\Base\Connector
     */
    public function setPrimaryKeyMapper(IPrimaryKeyMapper $mapper)
    {
        $this->keyMapper = $mapper;
        return $this;
    }

    /**
     * Returns primary key mapper
     *
     * @return \jtl\Connector\Authentication\ITokenLoader
     */
    public function getPrimaryKeyMapper()
    {
        return $this->keyMapper;
    }

    /**
     * Setter token loader
     *
     * @param \jtl\Connector\Authentication\ITokenLoader $tokenLoader
     * @return Connector
     */
    public function setTokenLoader(ITokenLoader $tokenLoader)
    {
        $this->tokenLoader = $tokenLoader;
        return $this;
    }

    /**
     * Returns token loader
     *
     * @return \jtl\Connector\Authentication\ITokenLoader
     */
    public function getTokenLoader()
    {
        return $this->tokenLoader;
    }

    /**
     * Setter token validator
     *
     * @param \jtl\Connector\Authentication\ITokenValidator $tokenValidator
     * @return Connector
     */
    protected function setTokenValidator(ITokenValidator $tokenValidator)
    {
        $this->tokenValidator = $tokenValidator;
        return $this;
    }

    /**
     * @return \jtl\Connector\Authentication\ITokenValidator
     */
    public function getTokenValidator()
    {
        return $this->tokenValidator;
    }

    /**
     * Setter checksum loader
     *
     * @param \jtl\Connector\Checksum\IChecksumLoader $checksumLoader
     * @return Connector
     */
    public function setChecksumLoader(IChecksumLoader $checksumLoader)
    {
        $this->checksumLoader = $checksumLoader;
        return $this;
    }

    /**
     * Returns checksum loader
     *
     * @return \jtl\Connector\Checksum\IChecksumLoader
     */
    public function getChecksumLoader()
    {
        return $this->checksumLoader;
    }

    /**
     * Setter checksum loader
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher
     * @return Connector
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Returns checksum loader
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
    
    /**
     * Method Setter
     *
     * @param \jtl\Connector\Core\Rpc\Method $method
     * @return Connector
     */
    public function setMethod(Method $method)
    {
        $this->method = $method;
        return $this;
    }
    
    /**
     * Method Getter
     *
     * @return \jtl\Connector\Core\Rpc\Method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return boolean
     */
    public function getUseSuperGlobals()
    {
        return $this->useSuperGlobals;
    }

    /**
     * Method Setter
     *
     * @param string $modelNamespace
     * @return Connector
     */
    public function setModelNamespace($modelNamespace)
    {
        if (!is_string($modelNamespace) || $modelNamespace[strlen($modelNamespace) - 1] == '\\' || $modelNamespace[0] == '\\') {
            throw new \InvalidArgumentException(sprintf('Wrong Namespace (%s) syntax. Example: jtl\Connector\Model', $modelNamespace));
        }

        $this->modelNamespace = $modelNamespace;
        return $this;
    }
    
    /**
     * Method Getter
     *
     * @return string
     */
    public function getModelNamespace()
    {
        return $this->modelNamespace;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Application\IEndpointConnector::canHandle()
     */
    public function canHandle()
    {
        $controller = RpcMethod::buildController($this->getMethod()->getController());
        
        $class = "\\jtl\\Connector\\Controller\\{$controller}";
        if (class_exists($class)) {
            $this->controller = $class::getInstance();
            $this->action = $this->getMethod()->getAction();
        
            return method_exists($this->controller, $this->action);
        }
        
        return false;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Application\IEndpointConnector::handle()
     */
    public function handle(RequestPacket $requestpacket)
    {
        $this->controller->setMethod($this->getMethod());
        
        return $this->controller->{$this->action}($requestpacket->getParams());
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Application\IEndpointConnector::getController()
     */
    public function getController()
    {
        return $this->controller;
    }
}
