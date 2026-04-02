<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Internal 
 */

namespace jtl\Connector\Model;

use \jtl\Connector\Core\Model\Model;
use JMS\Serializer\Annotation as Serializer;

/**
 * Identity
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Internal
 * @Serializer\AccessType("public_method")
 */
class Identity extends Model
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("endpoint")
     * @Serializer\Accessor(getter="getEndpoint",setter="setEndpoint")
     */
    protected $endpoint = '';
    
    /**
     * @var int
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("host")
     * @Serializer\Accessor(getter="getHost",setter="setHost")
     */
    protected $host = 0;

    /**
     * Constructor
     */
    public function __construct($endpoint = '', $host = 0)
    {
        $this->endpoint = (string)$endpoint;
        $this->host = (int)$host;
    }

    /**
     * Gets the value of endpoint.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }
    
    /**
     * Sets the value of endpoint.
     *
     * @param string $endpoint the endpoint
     *
     * @return \jtl\Connector\Model\Identity
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = (string)$endpoint;
        return $this;
    }

    /**
     * Gets the value of host.
     *
     * @return int
     */
    public function getHost()
    {
        return $this->host;
    }
    
    /**
     * Sets the value of host.
     *
     * @param int $host the host
     *
     * @return \jtl\Connector\Model\Identity
     */
    public function setHost($host)
    {
        $this->host = (int)$host;
        return $this;
    }

    /**
     * Convert to Array
     *
     * @return array
     */
    public function toArray()
    {
        return array($this->endpoint, $this->host);
    }

    /**
     * Convert the Model into array
     *            
     * @return array
     */
    public function getPublic(array $publics = null)
    {
        return $this->toArray();
    }

    /**
     * Convert from Array
     *
     * @throws \InvalidArgumentException
     * @return \jtl\Connector\Model\Identity
     */
    public static function fromArray(array $data)
    {
        if ($data === null || count($data) != 2 || !array_key_exists(0, $data) || !array_key_exists(1, $data)) {
            throw new \InvalidArgumentException('The data parameter can not be null and must contain two values'); 
        }

        return new self($data[0], $data[1]);
    }

    /**
     * Dynamic Converter
     *
     * @return \jtl\Connector\Model\Identity
     */
    public static function convert($data)
    {
        if ($data instanceof self) {
            return $data;
        }

        if (!is_array($data) && $data !== null) {
            return new self($data);
        }

        if ($data === null || (is_array($data) && (count($data) != 2 || !array_key_exists(0, $data) || !array_key_exists(1, $data)))) {
            return new self;
        }

        try {
            return self::fromArray($data);
        } catch (\Exception $exc) {
            return new self;
        }
    }
}