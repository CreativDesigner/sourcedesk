<?php
/**
 * @copyright 2010-2015 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Product
 */

namespace jtl\Connector\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;

/**
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Product
 *
 * @Serializer\AccessType("public_method")
 */
class ConnectorServerInfo extends DataModel
{
    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("memoryLimit")
     * @Serializer\Accessor(getter="getMemoryLimit",setter="setMemoryLimit")
     */
    protected $memoryLimit = 0;

    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("executionTime")
     * @Serializer\Accessor(getter="getExecutionTime",setter="setExecutionTime")
     */
    protected $executionTime = 0;

    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("postMaxSize")
     * @Serializer\Accessor(getter="getPostMaxSize",setter="setPostMaxSize")
     */
    protected $postMaxSize = 0;

    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("uploadMaxFilesize")
     * @Serializer\Accessor(getter="getUploadMaxFilesize",setter="setUploadMaxFilesize")
     */
    protected $uploadMaxFilesize = 0;

    /**
     * @return integer
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * @param integer $memoryLimit
     * @return \jtl\Connector\Model\ConnectorServerInfo
     */
    public function setMemoryLimit($memoryLimit)
    {
        return $this->setProperty('memoryLimit', $memoryLimit, 'integer');
    }

    /**
     * @return int
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    /**
     * @param integer $executionTime
     * @return \jtl\Connector\Model\ConnectorServerInfo
     */
    public function setExecutionTime($executionTime)
    {
        return $this->setProperty('executionTime', $executionTime, 'integer');
    }

    /**
     * @return int
     */
    public function getPostMaxSize()
    {
        return $this->postMaxSize;
    }

    /**
     * @param integer $postMaxSize
     * @return \jtl\Connector\Model\ConnectorServerInfo
     */
    public function setPostMaxSize($postMaxSize)
    {
        return $this->setProperty('postMaxSize', $postMaxSize, 'integer');
    }

    /**
     * @return int
     */
    public function getUploadMaxFilesize()
    {
        return $this->uploadMaxFilesize;
    }

    /**
     * @param integer $uploadMaxFilesize
     * @return \jtl\Connector\Model\ConnectorServerInfo
     */
    public function setUploadMaxFilesize($uploadMaxFilesize)
    {
        return $this->setProperty('uploadMaxFilesize', $uploadMaxFilesize, 'integer');
    }
}