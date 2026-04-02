<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Model
 * @subpackage Internal 
 */

namespace jtl\Connector\Model;

use \jtl\Connector\Core\Model\DataModel as CoreModel;
use JMS\Serializer\Annotation as Serializer;

/**
 * Entity data model
 *
 * @access public
 * @package jtl\Connector\Model
 * @subpackage Internal
 * @Serializer\AccessType("public_method")
 */
abstract class DataModel extends CoreModel
{
    /**
     * @var \jtl\Connector\Type\DataType
     * @Serializer\Type("jtl\Connector\Type\DataType")
     * @Serializer\AccessType("reflection")
     * @Serializer\Exclude
     */
    private $_type = null;

    /**
     * @var boolean
     * @Serializer\Type("boolean")
     * @Serializer\AccessType("reflection")
     * @Serializer\Exclude
     */
    protected $isEncrypted = false;

    /**
     * @return \jtl\Connector\Type\DataType
     */
    public function getModelType()
    {
        if ($this->_type === null) {
            $reflect = new \ReflectionClass($this);
            $class = '\\jtl\\Connector\\Type\\' . $reflect->getShortName();

            $this->_type = new $class;
        }

        return $this->_type;
    }

    /**
     * Encrypted Status
     * 
     * @return boolean
     */
    public function isEncrypted()
    {
        return $this->isEncrypted;
    }

    /**
     * Convert the Model into stdClass Object
     *
     * @param array $publics
     * @return stdClass $object
     */
    public function getPublic(array $publics = array('fields', 'isEncrypted', 'identities', '_type'))
    {
        $object = new \stdClass();

        $recursive = function (array $subElems, array $publics) use (&$recursive) {
            $arr = array();
            foreach ($subElems as $subElem) {
                if ($subElem instanceof DataModel) {
                    $arr[] = $subElem->getPublic($publics);
                } elseif ($subElem instanceof Identity) {
                    $arr[] = $subElem->toArray();
                } elseif ($subElem instanceof \DateTime) {
                    $arr[] = $subElem->format(\DateTime::ISO8601);
                } elseif (is_array($subElem)) {
                    $arr[] = $recursive($subElem, $publics);
                } else {
                    $arr[] = $subElem;
                }
            }

            return $arr;
        };

        $members = array_keys(get_object_vars($this));
        if (is_array($members) && count($members) > 0) {
            foreach ($members as $member) {
                $property = ucfirst($member);
                $getter = 'get' . $property;

                if (!in_array($member, $publics)) {
                    if ($this->{$getter}() instanceof DataModel) {
                        $object->{$member} = $this->{$getter}()->getPublic($publics);
                    } elseif ($this->{$getter}() instanceof Identity) {
                        $object->{$member} = $this->{$getter}()->toArray();
                    } elseif ($this->{$getter}() instanceof \DateTime) {
                        $object->{$member} = $this->{$getter}()->format(\DateTime::ISO8601);
                    } elseif (is_array($this->{$member})) {
                        $object->{$member} = $recursive($this->{$member}, $publics);
                    } else {
                        $object->{$member} = $this->{$member};
                    }
                }
            }
        }
        
        return $object;
    }
    
    /**
     * @param string $propertyName
     * @param string|null $endpoint
     * @param int|null $host
     */
    public function setIdentity($propertyName, $endpoint = null, $host = null)
    {
        foreach ($this->getModelType()->getProperties() as $propertyInfo) {
            $property = ucfirst($propertyInfo->getName());
            $getter = 'get' . $property;
            
            if ($propertyInfo->isNavigation() && !is_null($this->{$getter}())) {
                if (is_array($this->{$getter}())) {
                    $list = $this->{$getter}();
                    foreach ($list as &$entity) {
                        if ($entity instanceof self) {
                            $entity->setIdentity($propertyName, $endpoint, $host);
                        } elseif ($entity instanceof Identity && $propertyName === $propertyInfo->getName()) {
                            if (!is_null($endpoint)) {
                                $entity->setEndpoint($endpoint);
                            }
                            
                            if (!is_null($host)) {
                                $entity->setHost($host);
                            }
                        }
                    }
                } elseif ($this->{$getter}() instanceof self) {
                    /* @var self $entity */
                    $entity = $this->{$getter}();
                    $entity->setIdentity($propertyName, $endpoint, $host);
                }
            } elseif ($propertyInfo->isIdentity() && $propertyName === $propertyInfo->getName()) {
                /* @var Identity $identity */
                $identity = $this->{$getter}();
                
                if (!is_null($endpoint)) {
                    $identity->setEndpoint($endpoint);
                }
    
                if (!is_null($host)) {
                    $identity->setHost($host);
                }
            }
        }
    }

    /**
     * Sets Properties with matching Array Values
     *
     * @param \stdClass $object
     * @param array $options
     * @return \jtl\Connector\Model\DataModel
     */
    public function setOptions(\stdClass $object = null, array $options = null)
    {
        parent::setOptions($object, $options);

        return $this;
    }

    protected function setProperty($name, $value, $type)
    {
        if (!$this->validateType($value, $type)) {
            throw new \InvalidArgumentException(sprintf("%s (%s): expected type '%s', given value '%s'.", $name, get_class($this), $type, gettype($value)));
        }

        $this->{$name} = $value;
        
        return $this;
    }

    protected function validateType($value, $type)
    {
        if ($value === null) {
            return true;
        }

        switch ($type)
        {
            case 'boolean':
            case 'bool':
                return is_bool($value);
            case 'integer':
            case 'int':
                return is_integer($value);
            case 'float':
            case 'double':
                return (is_float($value) || is_integer($value) || is_double($value));
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'Identity':
                return ($value instanceof Identity);
            case 'DateTime':
                return ($value instanceof \DateTime);
            default:
                if (is_object($value)) {
                    return is_null($value) || is_subclass_of($value, 'jtl\Connector\Model\DataModel');
                }

                throw new \InvalidArgumentException(sprintf("type '%s' validator not found", $type));
        }
    }
}
