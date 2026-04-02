<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Application
 */

namespace jtl\Connector\Application;

use jtl\Connector\Application\Error\ErrorHandler;
use jtl\Connector\Application\Error\IErrorHandler;
use jtl\Connector\Authentication\ITokenValidator;
use jtl\Connector\Core\Compression\Zip;
use jtl\Connector\Core\IO\Temp;
use jtl\Connector\Core\Serializer\Json;
use jtl\Connector\Core\Application\Application as CoreApplication;
use jtl\Connector\Core\Exception\RpcException;
use jtl\Connector\Core\Exception\SessionException;
use jtl\Connector\Core\Exception\ApplicationException;
use jtl\Connector\Core\Rpc\Packet;
use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Rpc\ResponsePacket;
use jtl\Connector\Core\Rpc\Error;
use jtl\Connector\Core\Http\Request;
use jtl\Connector\Core\Http\Response;
use jtl\Connector\Core\Config\Config;
use jtl\Connector\Model\BoolResult;
use jtl\Connector\Result\Action;
use jtl\Connector\Core\Validator\Schema;
use jtl\Connector\Core\Exception\SchemaException;
use jtl\Connector\Core\Validator\ValidationException;
use jtl\Connector\Database\Sqlite3;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Session\Session;
use jtl\Connector\Base\Connector;
use jtl\Connector\Core\Logger\Logger;
use Doctrine\Common\Annotations\AnnotationRegistry;
use jtl\Connector\Core\Rpc\Method;
use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\Model\DataModel;
use Doctrine\Common\Collections\ArrayCollection;
use jtl\Connector\Serializer\JMS\SerializerBuilder;
use jtl\Connector\Linker\ChecksumLinker;
use Symfony\Component\EventDispatcher\EventDispatcher;
use jtl\Connector\Core\IO\Path;
use jtl\Connector\Event\EventHandler;
use Symfony\Component\Finder\Finder;

/**
 * Application Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Application extends CoreApplication
{
    const PROTOCOL_VERSION = 7;

    /**
     * Connected EndpointConnectors
     *
     * @var IEndpointConnector
     */
    protected $connector = null;

    /**
     * @var Config;
     */
    protected $config;
    
    /**
     * @var string
     */
    protected $featurePath;

    /**
     * Global Session
     *
     * @var \jtl\Connector\Session\Session
     */
    protected $session;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var \jtl\Connector\Application\Error\IErrorHandler
     */
    protected $errorHandler;

    protected function __construct()
    {
        require_once(dirname(__FILE__) . '/../bootstrap.php');

        // Error Handler
        $this->setErrorHandler(new ErrorHandler());
    }

    /**
     * (non-PHPdoc)
     *
     * @see \jtl\Connector\Core\Application\Application::run()
     */
    public function run()
    {
        AnnotationRegistry::registerLoader('class_exists');

        if ($this->connector === null) {
            throw new ApplicationException('No connector registed');
        }

        // Event Dispatcher
        $this->eventDispatcher = new EventDispatcher();

        $this->getErrorHandler()->setEventDispatcher($this->eventDispatcher);

        $jtlrpc = Request::handle($this->connector->getUseSuperGlobals());
        $sessionId = Request::getSession();
        $requestpackets = RequestPacket::build($jtlrpc);

        $rpcmode = is_object($requestpackets) ? Packet::SINGLE_MODE : Packet::BATCH_MODE;

        $method = null;
        if ($rpcmode == Packet::SINGLE_MODE) {
            $method = $requestpackets->getMethod();
        }

        // Start Session
        $this->startSession($sessionId, $method);

        // Start Configuration
        $this->startConfiguration();
    
        // Log incoming request packet (debug only and configuration must be initialized)
        Logger::write(sprintf('RequestPacket: %s', Json::encode($requestpackets->getPublic())), Logger::DEBUG, 'rpc');
        if (is_string($requestpackets->getParams())) {
            Logger::write(sprintf('Params: %s', $requestpackets->getParams()), Logger::DEBUG, 'rpc');
        }
        
        // Register Event Dispatcher
        $this->startEventDispatcher();

        // Initialize Endpoint
        $this->connector->initialize();

        if ($this->connector->getPrimaryKeyMapper() === null) {
            throw new ApplicationException('No primary key mapper registered');
        }

        $tokenValidatorExists = false;
        if (is_callable([$this->connector, 'getTokenValidator']) && $this->connector->getTokenValidator() instanceof ITokenValidator) {
            $tokenValidatorExists = true;
        }

        if (is_null($this->connector->getTokenLoader()) && !$tokenValidatorExists) {
            throw new ApplicationException('Neither a token loader nor a token validator is registered');
        }

        if ($this->connector->getChecksumLoader() === null) {
            throw new ApplicationException('No checksum loader registered');
        }

        ChecksumLinker::setChecksumLoader($this->connector->getChecksumLoader());

        switch ($rpcmode) {
            case Packet::SINGLE_MODE:
                $this->runSingle($requestpackets, $rpcmode);
                break;
            case Packet::BATCH_MODE:
                $this->runBatch($requestpackets, $rpcmode);
                break;
        }
    }

    /**
     * Execute RPC Method
     *
     * @param RequestPacket $requestpacket
     * @param integer $rpcmode
     * @throws RpcException
     * @return \jtl\Connector\Core\Rpc\ResponsePacket
     */
    // OLD single Image
    //protected function execute(RequestPacket $requestpacket, $rpcmode, $imagePath = null)
    protected function execute(RequestPacket $requestpacket, $rpcmode, array $imagePaths = array())
    {
        if (!RpcMethod::isMethod($requestpacket->getMethod())) {
            throw new RpcException('Invalid Request', -32600);
        }

        $identityLinker = IdentityLinker::getInstance();
        $identityLinker->setPrimaryKeyMapper($this->connector->getPrimaryKeyMapper());

        ////////////////////
        // Core Connector //
        ////////////////////
        $coreconnector = Connector::getInstance();
        $method = RpcMethod::splitMethod($requestpacket->getMethod());
        $coreconnector->setMethod($method);

        // Rpc Event
        $data = $requestpacket->getParams();
        EventHandler::dispatchRpc($data, $this->eventDispatcher, $method->getController(), $method->getAction(), EventHandler::BEFORE);

        if ($method->isCore() && $coreconnector->canHandle()) {
            $actionresult = $coreconnector->handle($requestpacket);
            if ($actionresult->isHandled()) {
                $responsepacket = $this->buildRpcResponse($requestpacket, $actionresult);

                if ($rpcmode == Packet::SINGLE_MODE) {

                    // Event
                    $class = ($method->getController() === 'connector') ? 'Connector' : null;
                    $res = $actionresult->getResult();
                    EventHandler::dispatch($res, $this->eventDispatcher, $method->getAction(), EventHandler::AFTER, $class, true);

                    $this->triggerRpcAfterEvent($responsepacket->getPublic(), $requestpacket->getMethod());
                    Response::send($responsepacket);
                } else {
                    return $responsepacket;
                }
            }
        }

        ////////////////////////
        // Endpoint Connector //
        ////////////////////////
        $exists = false;

        $this->deserializeRequestParams($requestpacket, $this->connector->getModelNamespace());

        // Image push?
        // OLD single Image
        //$this->handleImagePush($requestpacket, $imagePath);
        $this->handleImagePush($requestpacket, $imagePaths);

        $this->connector->setMethod($method);
        if ($this->connector->canHandle()) {
            /** @var Action $actionresult */
            $actionresult = $this->connector->handle($requestpacket);
            
            if ($requestpacket->getMethod() === 'image.push' && count($imagePaths) > 0) {
                Request::deleteFileuploads($imagePaths);
            }
            
            if ($actionresult instanceof Action) {
                $exists = true;
                if ($actionresult->isHandled()) {

                    if ($actionresult->getError() === null) {
    
                        // Convert boolean to BoolResult
                        if (is_bool($actionresult->getResult())) {
                            $actionresult->setResult((new BoolResult())->setResult($actionresult->getResult()));
                        }

                        // Identity mapping
                        $results = array();
                        $models = is_array($actionresult->getResult()) ? $actionresult->getResult() : array($actionresult->getResult());
                        
                        foreach ($models as $model) {
                            if ($model instanceof DataModel) {
                                $identityLinker->linkModel($model, ($method->getAction() === Method::ACTION_DELETE));

                                // @TODO: Specific identity delete

                                // Checksum linking
                                ChecksumLinker::link($model);
                                
                                // Event
                                $class = ($method->getController() === 'connector') ? 'Connector' : null;
                                EventHandler::dispatch($model, $this->eventDispatcher, $method->getAction(), EventHandler::AFTER, $class);

                                if ($method->getAction() === Method::ACTION_PULL) {
                                    $results[] = $model->getPublic();
                                }
                            }
                        }

                        if ($method->getAction() === Method::ACTION_PULL) {
                            $actionresult->setResult($results);
                        }
                    }

                    // Building response packet
                    $responsepacket = $this->buildRpcResponse($requestpacket, $actionresult);

                    if ($rpcmode == Packet::SINGLE_MODE) {
                        $this->triggerRpcAfterEvent($responsepacket->getPublic(), $requestpacket->getMethod());
                        Response::send($responsepacket);
                    } else {
                        return $responsepacket;
                    }
                }
            } else {
                throw new RpcException('Internal error', -32603);
            }
        } else {
            /*
             * OLD single Image
            if ($requestpacket->getMethod() === 'image.push') {
                Request::deleteFileupload($imagePath);
            }
            */
            if ($requestpacket->getMethod() === 'image.push' && count($imagePaths) > 0) {
                Request::deleteFileuploads($imagePaths);
            }
        }

        if ($exists) {
            throw new RpcException('Method could not be handled', -32000);
        } else {
            throw new RpcException(
                sprintf("Method '%s' not found", $requestpacket->getMethod()),
                -32601
            );
        }
    }

    /**
     *
     * @param IEndpointConnector $endpointconnector
     * @return Application
     */
    public function register(IEndpointConnector $endpointconnector)
    {
        $this->connector = $endpointconnector;
        
        return $this;
    }

    /**
     * Single Mode
     *
     * @param ResponsePacket $requestpacket
     * @param integer $rpcmode
     */
    protected function runSingle(RequestPacket $requestpacket, $rpcmode)
    {
        $requestpacket->validate();
        $this->runActionValidation($requestpacket);
        $this->runModelValidation($requestpacket);

        // Image?
        /*
         * OLD single Image
        $imagePath = null;
        if ($requestpacket->getMethod() === 'image.push') {
            $imagePath = Request::handleFileupload();
        }
        */
        $imagePaths = [];
        if ($requestpacket->getMethod() === 'image.push') {
            $zipFile = Request::handleFileupload();
            $tempDir = Temp::generateDirectory();

            if ($zipFile !== null && $tempDir !== null) {
                $archive = new Zip();
                if ($archive->extract($zipFile, $tempDir)) {
                    $finder = new Finder();
                    $finder->files()->ignoreDotFiles(true)->in($tempDir);
                    foreach ($finder as $file) {
                        $imagePaths[] = $file->getRealpath();
                    }
                } else {
                    @rmdir($tempDir);
                    @unlink($zipFile);

                    throw new ApplicationException(sprintf('Zip File (%s) count not be extracted', $zipFile));
                }

                if ($zipFile !== null) {
                    @unlink($zipFile);
                }
            } else {
                throw new ApplicationException('Zip file or temp dir  is null');
            }
        }

        try {
            // OLD single Image
            //$this->execute($requestpacket, $config, $rpcmode, $imagePath);
            $this->execute($requestpacket, $rpcmode, $imagePaths);
        } catch (\Exception $exc) {
            /*
             * OLD single Image
            if ($requestpacket->getMethod() === 'image.push' && $imagePath !== null) {
                Request::deleteFileupload($imagePath);
            }
            */
            if ($requestpacket->getMethod() === 'image.push' && count($imagePaths) > 0) {
                Request::deleteFileuploads($imagePaths);
            }

            $error = new Error();
            $error->setCode($exc->getCode())
                ->setMessage($exc->getMessage());

            $responsepacket = new ResponsePacket();
            $responsepacket->setId($requestpacket->getId())
                ->setJtlrpc($requestpacket->getJtlrpc())
                ->setError($error);

            $this->triggerRpcAfterEvent($responsepacket->getPublic(), $requestpacket->getMethod());
            Response::send($responsepacket);
        }
    }

    /**
     * Batch Mode
     *
     * @param array $requestpackets
     * @param integer $rpcmode
     */
    protected function runBatch(array $requestpackets, $rpcmode)
    {
        $jtlrpcreponses = array();

        foreach ($requestpackets as $requestpacket) {
            try {
                $requestpacket->validate();
                $this->runActionValidation($requestpacket);
                $this->runModelValidation($requestpacket);
                $jtlrpcreponses[] = $this->execute($requestpacket, $rpcmode);
            } catch (RpcException $exc) {
                $error = new Error();
                $error->setCode($exc->getCode())
                    ->setMessage($exc->getMessage());

                $responsepacket = new ResponsePacket();
                $responsepacket->setId($requestpacket->getId())
                    ->setJtlrpc($requestpacket->getJtlrpc())
                    ->setError($error);

                $jtlrpcreponses[] = $responsepacket;
            }
        }

        Response::sendAll($jtlrpcreponses);
    }

    protected function deserializeRequestParams(RequestPacket &$requestpacket, $modelNamespace)
    {
        $method = RpcMethod::splitMethod($requestpacket->getMethod());
        $modelClass = RpcMethod::buildController($method->getController());

        $namespace = ($method->getAction() === Method::ACTION_PUSH || $method->getAction() === Method::ACTION_DELETE) ?
            sprintf('%s\%s', $modelNamespace, $modelClass) : 'jtl\Connector\Core\Model\QueryFilter';

        if (class_exists("\\{$namespace}") && $requestpacket->getParams() !== null) {
            $serializer = SerializerBuilder::create();

            if ($method->getAction() === Method::ACTION_PUSH || $method->getAction() === Method::ACTION_DELETE) {
                // OLD single Image
                //$ns = ($method->getAction() === Method::ACTION_PUSH && $method->getController() === 'image') ? $namespace : "ArrayCollection<{$namespace}>";
                $ns = "ArrayCollection<{$namespace}>";
                $params = $serializer->deserialize($requestpacket->getParams(), $ns, 'json');

                $identityLinker = IdentityLinker::getInstance();
                if (is_array($params)) {
                    // Identity mapping
                    foreach ($params as &$param) {
                        $identityLinker->linkModel($param);

                        // Checksum linking
                        ChecksumLinker::link($param);

                        // Event
                        EventHandler::dispatch($param, $this->eventDispatcher, $method->getAction(), EventHandler::BEFORE);
                    }
                } else {
                    $identityLinker->linkModel($params);

                    // Checksum linking
                    ChecksumLinker::link($params);

                    // Event
                    EventHandler::dispatch($params, $this->eventDispatcher, $method->getAction(), EventHandler::BEFORE);
                }
            } else {
                $params = $serializer->deserialize($requestpacket->getParams(), $namespace, 'json');

                // Event
                EventHandler::dispatch($params, $this->eventDispatcher, $method->getAction(), EventHandler::BEFORE, $modelClass);
            }

            $requestpacket->setParams($params);
        }
    }

    /**
     * Build RPC Reponse Packet
     *
     * @param \jtl\Connector\Core\Rpc\ResponsePacket $requestpacket
     * @param \jtl\Connector\Result\Action $actionresult
     * @return \jtl\Connector\Core\Rpc\ResponsePacket
     * @throws \jtl\Connector\Core\Exception\RpcException
     */
    protected function buildRpcResponse(RequestPacket $requestpacket, Action $actionresult)
    {
        $responsepacket = new ResponsePacket();
        $responsepacket->setId($requestpacket->getId())
            ->setJtlrpc($requestpacket->getJtlrpc())
            ->setResult($actionresult->getResult())
            ->setError($actionresult->getError());

        $responsepacket->validate();

        return $responsepacket;
    }

    /**
     * Validate Action
     *
     * @param RequestPacket $requestpacket
     * @throws SchemaException
     */
    protected function runActionValidation(RequestPacket $requestpacket)
    {
        $method = RpcMethod::splitMethod($requestpacket->getMethod());

        try {
            Schema::validateAction(__DIR__ . "/../../../../schema/{$method->getController()}/params/{$method->getAction()}.json", $requestpacket->getParams());
        } catch (ValidationException $exc) {
            throw new SchemaException($exc->getMessage());
        }
    }

    /**
     * Validate Model
     *
     * @param string $controller
     * @throws SchemaException
     */
    protected function runModelValidation(RequestPacket $requestpacket)
    {
        /*
        $method = RpcMethod::splitMethod($requestpacket->getMethod());

        if ($method->getAction() === Method::ACTION_PUSH) {
            $controller = str_replace('_', '', $method->getController());

            try {
                Schema::validateAction(CONNECTOR_DIR . "schema/{$controller}/{$controller}.json", $requestpacket->getParams());
            } catch (ValidationException $exc) {
                throw new SchemaException($exc->getMessage());
            }
        }
        */
    }

    /**
     * Initialises the connector configuration instance.
     */
    protected function startConfiguration()
    {
        if (!isset($this->session)) {
            throw new SessionException('Session not initialized', -32001);
        }
    
        // Config
        if (is_null($this->config)) {
            $config_file = Path::combine(CONNECTOR_DIR, 'config', 'config.json');
            if (!file_exists($config_file)) {
                file_put_contents($config_file, json_encode(array('developer_logging' => false), JSON_PRETTY_PRINT));
            }
    
            $this->config = new Config($config_file);
        }
    
        if (!$this->config->has('developer_logging')) {
            $this->config->save('developer_logging', false);
        }
    }

    /**
     * Starting Session
     *
     * @throws \jtl\Connector\Core\Exception\DatabaseException
     * @throws \jtl\Connector\Core\Exception\SessionException
     */
    protected function startSession($sessionId = null, $method)
    {
        if ($sessionId === null && $method !== null && $method !== 'core.connector.auth') {
            throw new SessionException('No session');
        }

        $dir = Path::combine(CONNECTOR_DIR, 'db');
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                throw new ApplicationException('Could not create sqlite database directory');
            }
        }

        $sqlite3 = Sqlite3::getInstance();
        $sqlite3->connect(array('location' => Path::combine($dir, 'connector.s3db')));
        $sqlite3->check();

        $this->session = new Session($sqlite3, $sessionId);
    }

    protected function startEventDispatcher()
    {
        $this->connector->setEventDispatcher($this->eventDispatcher);

        $loader = new \jtl\Connector\Plugin\PluginLoader();
        $loader->load($this->eventDispatcher);
    }

    // OLD single Image
    //protected function handleImagePush(RequestPacket &$requestpacket, $imagePath)
    protected function handleImagePush(RequestPacket &$requestpacket, array $imagePaths = array())
    {
        if ($requestpacket->getMethod() === 'image.push') {
            $images = $requestpacket->getParams();
            if (!is_array($images)) {
                throw new ApplicationException('Request params must be valid images');
            }

            if (count($imagePaths) > 0) {
                for ($i = 0; $i < count($images); $i++) {
                    foreach ($imagePaths as $imagePath) {
                        $infos = pathinfo($imagePath);
                        list ($hostId, $relationType) = explode('_', $infos['filename']);
                        if ((int)$hostId == $images[$i]->getId()->getHost()
                            && strtolower($relationType) === strtolower($images[$i]->getRelationType())
                        ) {
                            $images[$i]->setFilename($imagePath);
                        }
                    }
                }

                $requestpacket->setParams($images);
            } else {
                for ($i = 0; $i < count($images); $i++) {
                    if (strlen($images[$i]->getRemoteUrl()) > 0) {
                        $imageData = file_get_contents($images[$i]->getRemoteUrl());
                        if ($imageData === false) {
                            throw new ApplicationException('Could not get any data from url: ' . $images[$i]->getRemoteUrl());
                        }

                        $path = parse_url($images[$i]->getRemoteUrl(), PHP_URL_PATH);
                        $fileName = pathinfo($path, PATHINFO_BASENAME);
                        $imagePath = Path::combine(Temp::getDirectory(), uniqid() . "_{$fileName}");
                        file_put_contents($imagePath, $imageData);

                        $images[$i]->setFilename($imagePath);
                    } else {
                        throw new ApplicationException('Could not handle fileupload (no file was uploaded via HTTP POST?)');
                    }
                }

                $requestpacket->setParams($images);
            }
        }
    }

    protected function triggerRpcAfterEvent($data, $method)
    {
        $method = RpcMethod::splitMethod($method);
        EventHandler::dispatchRpc($data, $this->eventDispatcher, $method->getController(), $method->getAction(), EventHandler::AFTER);
    }

    /**
     * Connector getter
     *
     * @return IEndpointConnector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Session getter
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return int
     */
    public function getProtocolVersion()
    {
        return self::PROTOCOL_VERSION;
    }
    
    /**
     * @param Config $config
     * @return Application
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        
        return $this;
    }
    
    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * @param string $featurePath
     * @return Application
     */
    public function setFeaturePath($featurePath)
    {
        if (!is_string($featurePath) || !file_exists($featurePath)) {
            throw new \InvalidArgumentException('Param featurePath must be a string and the file must exists');
        }
        
        $this->featurePath = $featurePath;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getFeaturePath()
    {
        return $this->featurePath;
    }

    /**
     * @return IErrorHandler
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * @param IErrorHandler $handler
     * @return $this
     */
    public function setErrorHandler(IErrorHandler $handler)
    {
        $this->errorHandler = $handler;
        return $this;
    }
}
