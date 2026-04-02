<?php
/**
 * @copyright JTL-Software GmbH
 * @package jtl\Connector\Core\Model
 */
namespace jtl\Connector\Core\Model;

use \jtl\Connector\Core\Validator\Schema;
use \jtl\Connector\Core\Utilities\ClassName;
use \jtl\Connector\Core\Exception\NotImplementedException;
use JMS\Serializer\Annotation as Serializer;

/**
 * DataModel Class
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 * @Serializer\ExclusionPolicy("none");
 */
abstract class DataModel extends Model
{
    /**
     * @var array list of strings
     * @Serializer\Type("array<string, string>")
     * @Serializer\Exclude
     * @Serializer\ReadOnly
     */
    protected $fields = array();

    /**
     * Fields Getter
     *
     * @return mixed:string
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Object Validation
     *
     * @throws \jtl\Connector\Core\Exception\SchemaException
     */
    public function validate()
    {
        $class = ClassName::getFromNS(strtolower(get_called_class()));
        
        Schema::validateModel(CONNECTOR_DIR . "schema/{$class}/{$class}.json", $this->getPublic());
    }
    
    /**
     * Get a Model Member Name
     *
     * @param boolean $toWawi
     * @param string $key
     * @return mixed:string|NULL
     */
    public function getField($toWawi = false, $key)
    {
        if ($this->fields !== null && is_array($this->fields)) {
            $fields = $this->fields;
            if (!$toWawi) {
                $fields = array_flip($fields);
            }
            
            if (is_array($fields) && isset($fields[$key])) {
                return $fields[$key];
            }
        }
            
        return null;
    }

    /**
     * Object Mapping
     *
     * @param boolean $toWawi
     * @param mixed $obj Object to map
     */
    public function map($toWawi = false, \stdClass $obj = null)
    {
        throw new NotImplementedException;
    }
}
