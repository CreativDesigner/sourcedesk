<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Rpc
 */
namespace jtl\Connector\Core\Rpc;

use \jtl\Connector\Core\Model\Model;
use JMS\Serializer\Annotation as Serializer;

/**
 * Rpc Packet
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
abstract class Packet extends Model
{
    /**
     * Single Mode
     *
     * @var integer
     * @Serializer\Type("integer")
     */
    const SINGLE_MODE = 1;
    
    /**
     * Batch Mode
     *
     * @var integer
     * @Serializer\Type("integer")
     */
    const BATCH_MODE = 2;
    
    /**
     * A String specifying the version of the JSON-RPC protocol.
     * MUST be exactly "2.0".
     *
     * @var string
     * @Serializer\Type("string")
     */
    protected $jtlrpc;
    
    /**
     * An identifier established by the Client that MUST contain a String,
     * Number, or NULL value if included.
     *
     * If it is not included it is assumed to be a notification. The value
     * SHOULD normally not be Null [1] and Numbers SHOULD NOT contain fractional
     * parts
     *
     * @var string | NULL
     * @Serializer\Type("string")
     */
    protected $id;

    /**
     * Getter for $jtlrpc
     *
     * @return string
     */
    public function getJtlrpc()
    {
        return $this->jtlrpc;
    }

    /**
     * Setter for $jtlrpc
     *
     * @param string $jtlrpc
     * @return \jtl\Connector\Core\Rpc\Packet
     */
    public function setJtlrpc($jtlrpc)
    {
        $this->jtlrpc = $jtlrpc;
        return $this;
    }

    /**
     * Getter for $id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for $id
     *
     * @param string | number | NULL $id
     * @return \jtl\Connector\Core\Rpc\Packet
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}
