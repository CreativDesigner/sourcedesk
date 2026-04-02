<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Rpc
 */
namespace jtl\Connector\Core\Rpc;

use \jtl\Connector\Core\Exception\RpcException;
use \jtl\Connector\Core\Serializer\Json;
use JMS\Serializer\Annotation as Serializer;
use \jtl\Connector\Core\Logger\Logger;
use \jtl\Connector\Formatter\ExceptionFormatter;

/**
 * Rpc Request Packet
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class RequestPacket extends Packet
{
    /**
     * A String containing the name of the method to be invoked.
     * Method names that begin with the word rpc followed by a period character
     * (U+002E or ASCII 46)
     * are reserved for rpc-internal methods and extensions and MUST NOT be used
     * for anything else.
     *
     * @var string
     * @Serializer\Type("string")
     */
    protected $method;
    
    /**
     * A Structured value that holds the parameter values to be used during the
     * invocation of the method.
     * This member MAY be omitted.
     *
     * @var array | object
     * @Serializer\Type("jtl\Connector\Core\Rpc\JsonString")
     */
    protected $params;

    /**
     * Getter for $method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Setter for $method
     *
     * @param string $method
     * @return \jtl\Connector\Core\Rpc\Packet
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Getter for $params
     *
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Setter for $params
     *
     * @param array | object $params
     * @return \jtl\Connector\Core\Rpc\Packet
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Validates a Rpc Request Packet
     *
     * @throws RpcException
     */
    final public function validate()
    {
        $isValid = true;
        
        // JSON-RPC protocol
        if ($this->getJtlrpc() === null || $this->getJtlrpc() != "2.0") {
            $isValid = false;
        }
        
        // A String containing the name of the method to be invoked
        if ($this->getMethod() === null || strlen($this->getMethod()) == 0) {
            $isValid = false;
        }
        
        // An identifier established by the Client that MUST contain a
        // String, Number, or NULL value if included
        if ($this->getId() === null || strlen($this->getId()) == 0) {
            $isValid = false;
        }
        
        if (!$isValid) {
            throw new RpcException("Parse error", -32700);
        }
    }
    
    /**
     * Build RequestPacket
     *
     * @param string $jtlrpc
     * @throws RpcException
     * @return \jtl\Connector\Core\Rpc\RequestPacket|multitype:\jtl\Connector\Core\Rpc\RequestPacket
     */
    public static function build($jtlrpc)
    {
        if ($jtlrpc !== null) {
            try {
                $serializer = \JMS\Serializer\SerializerBuilder::create()
                    ->addDefaultHandlers()
                    ->configureHandlers(function (\JMS\Serializer\Handler\HandlerRegistry $registry) {
                        $registry->registerSubscribingHandler(new \jtl\Connector\Core\Serializer\Handler\JsonStringHandler());
                    })
                    ->build();

                return $serializer->deserialize($jtlrpc, 'jtl\Connector\Core\Rpc\RequestPacket', 'json');
                
                /*
                $data = Json::decode($jtlrpc);
    
                // Single Mode
                if (is_object($data)) {
                    $requestpacket = new RequestPacket();
                    $requestpacket->setOptions($data);
    
                    return $requestpacket;
                }
    
                // Batch Mode
                elseif (is_array($data)) {
                    $requestpackets = array();
                    foreach ($data as $packet) {
                        $requestpacket = new RequestPacket();
                        $requestpacket->setOptions($packet);
                        
                        $requestpackets[] = $requestpacket;
                    }
    
                    return $requestpackets;
                }
                else {
                    throw new RpcException("Invalid Request", -32600);
                }
                */
            } catch (RpcException $exc) {
                Logger::write(ExceptionFormatter::format($exc), Logger::ERROR, 'global');

                throw $exc;
            } catch (\Exception $exc) {
                throw new RpcException("Parse error", -32700);
            }
        } else {
            throw new RpcException("Invalid Request", -32600);
        }
    }
}
