<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Rpc
 */
namespace jtl\Connector\Core\Rpc;

use \jtl\Connector\Core\Model\Model;
use \jtl\Connector\Core\Exception\RpcException;
use JMS\Serializer\Annotation as Serializer;

/**
 * Rpc Error
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Error extends Model
{
    /**
     * A Number that indicates the error type that occurred.
     *
     * This MUST be an integer.
     *
     * @var integer
     * @Serializer\Type("integer")
     */
    public $code;
    
    /**
     * A String providing a short description of the error.
     *
     * The message SHOULD be limited to a concise single sentence.
     *
     * @var string
     * @Serializer\Type("string")
     */
    public $message;
    
    /**
     * A Primitive or Structured value that contains additional information
     * about the error.
     *
     * This may be omitted. The value of this member is defined by the Server
     * (e.g. detailed error information, nested errors etc.).
     *
     * @var integer | string | array | object
     */
    public $data;

    /**
     * Getter for $code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Setter for $code
     *
     * @param int $code
     * @return \jtl\Connector\Core\Rpc\Error
     */
    public function setCode($code)
    {
        $this->code = (int) $code;
        return $this;
    }

    /**
     * Getter for $message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Setter for $message
     *
     * @param string $message
     * @return \jtl\Connector\Core\Rpc\Error
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Getter for $data
     *
     * @return integer | string | array | object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Setter for $data
     *
     * @param
     *            integer | string | array | object $data
     * @return \jtl\Connector\Core\Rpc\Error
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Validates a Rpc Error
     *
     * @throws RpcException
     */
    final public function validate()
    {
        $isValid = true;
        
        // A Number that indicates the error type that occurred. This MUST be an
        // integer.
        if ($this->getCode() === null) {
            $isValid = false;
        }
            
            // A String providing a short description of the error. The message
        // SHOULD be limited to a concise single sentence.
        if ($this->getMessage() === null || strlen($this->getMessage()) == 0) {
            $isValid = false;
        }
        
        if (!$isValid) {
            throw new RpcException("Parse error", -32701);
        }
    }
}
