<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Validator
 */
namespace jtl\Connector\Core\Validator;

use \jtl\Connector\Core\Exception\SchemaException;

/**
 * Schema Validator
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Schema
{
    /**
     * Schema file_exists
     *
     * @var mixed
     */
    protected static $_schemaexists = array();
    
    /**
     * Json Schema Classes
     *
     * @var multiple:\jtl\Connector\Core\Validator\Json
     */
    protected static $_jsons = array();
    
    /**
     * Action Validation
     *
     * @param string $schemafile
     * @param mixed $params
     * @throws ValidationException
     */
    public static function validateAction($schemafile, $params)
    {
        if (self::schemaExists($schemafile)) {
            $json = new Json($schemafile);
            $json->validate($params);
        }
    }
    
    /**
     * Model Validation
     *
     * @throws \jtl\Connector\Core\Exception\SchemaException
     */
    public static function validateModel($schemafile, $obj)
    {
        if (self::schemaExists($schemafile)) {
            $json = self::getJson($schemafile);
            
            try {
                $json->validate($obj);
            } catch (ValidationException $exc) {
                throw new SchemaException($exc->getMessage(), $exc->getCode());
            }
        }
    }
    
    /**
     * Checks whether a schema file exists
     *
     * @param string $schemafile
     * @return boolean
     */
    public static function schemaExists($schemafile)
    {
        if (!isset(self::$_schemaexists[$schemafile])) {
            self::$_schemaexists[$schemafile] = file_exists($schemafile);
        }
        
        return self::$_schemaexists[$schemafile];
    }
    
    /**
     * Singleton Json Schema Class
     *
     * @param string $schemafile
     * @return \jtl\Connector\Core\Validator\Json
     */
    public static function getJson($schemafile)
    {
        if (!isset(self::$_jsons[$schemafile])) {
            self::$_jsons[$schemafile] = new Json($schemafile);
        }
        
        return self::$_jsons[$schemafile];
    }
}
