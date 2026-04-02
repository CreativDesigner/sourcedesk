<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Model
 */

namespace jtl\Connector\Core\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * Database Query Filter
 *
 * @access public
 * @package jtl\Connector\Model
 * @Serializer\AccessType("public_method")
 */
class QueryFilter
{
    const FILTER_FETCH_CHILDREN = 'fetchChildren';
    const FILTER_PARENT_ID = 'parentId';
    const FILTER_RELATION_TYPE = 'relationType';

    /**
     * Query item count limitation
     *
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("limit")
     */
    protected $limit;
    
    /**
     * Query item filter (where)
     *
     * @var multiple: string
     * @Serializer\Type("array<string, string>")
     * @Serializer\SerializedName("filters")
     */
    protected $filters;
    
    /**
     * Constructor
     */
    public function __construct($limit = null)
    {
        $this->filters = array();
        
        if ($limit !== null) {
            $this->limit = $limit;
        }
    }
    
    /**
     * Limit Setter
     *
     * @param integer $limit
     * @return \jtl\Connector\Model\QueryFilter
     */
    public function setLimit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }
    
    /**
     * Limit Getter
     *
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return bool
     */
    public function isLimit()
    {
        return ($this->limit !== null && $this->limit > 0);
    }
    
    /**
     * Filters Setter
     *
     * @param multiple: string $filters
     * @return \jtl\Connector\Model\QueryFilter
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }
    
    /**
     * Filters Getter
     *
     * @return multiple: string
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * Add one Filter
     *
     * @param string $key Filter key
     * @param string $value Filter value
     * @return \jtl\Connector\Core\Model\QueryFilter
     */
    public function addFilter($key, $value)
    {
        if ($this->filters === null) {
            $this->filters = array();
        }
        
        $this->filters[$key] = $value;
        return $this;
    }
    
    /**
     * Delete one Filter
     *
     * @param string $key
     * @return boolean
     */
    public function deleteFilter($key)
    {
        if ($this->isFilter($key)) {
            unset($this->filters[$key]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     *
     * @param string $key
     * @return boolean
     */
    public function isFilter($key)
    {
        return isset($this->filters[$key]);
    }
    
    /**
     *
     * @param string $key
     * @return mixed|NULL
     */
    public function getFilter($key)
    {
        if ($this->isFilter($key)) {
            return $this->filters[$key];
        }
        
        return null;
    }
    
    /**
     *
     * @param string $oldKey
     * @param string $newKey
     * @param mixed $value
     * @return boolean
     */
    public function overrideFilter($oldKey, $newKey, $value = null)
    {
        if ($this->isFilter($oldKey)) {
            if ($value === null) {
                $value = $this->filters[$oldKey];
            }
            
            unset($this->filters[$oldKey]);
            $this->filters[$newKey] = $value;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Setter
     *
     * @param \stdClass $obj
     */
    public function set(\stdClass $obj)
    {
        if (!is_object($obj)) {
            return;
        }

        if (isset($obj->limit)) {
            $this->setLimit($obj->limit);
        }
        
        if (isset($obj->filters) && is_object($obj->filters)) {
            $this->setFilters(get_object_vars($obj->filters));
        }
    }
}
