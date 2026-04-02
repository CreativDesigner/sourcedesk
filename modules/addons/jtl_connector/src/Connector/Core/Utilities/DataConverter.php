<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

final class DataConverter
{
    public static function toObject(array $values, $forceOwn = false)
    {
        if (function_exists('json_encode') && !$forceOwn) {
            return json_decode(json_encode($values));
        } else {
            $obj = new \stdClass();
            if ($values !== null) {
                $toObject = function ($values, $obj) use (&$toObject) {
                    foreach ($values as $key => $value) {
                        if (is_array($value)) {
                            $obj->$key = new \stdClass();
                            $obj->$key = $toObject($value, $obj->$key);
                        } else {
                            $obj->$key = $value;
                        }
                    }
                    
                    return $obj;
                };
                
                $obj = $toObject($values, $obj);
            }

            return $obj;
        }
    }

    public static function toArray(\stdClass $obj, $forceOwn = false)
    {
        if (function_exists('json_encode') && !$forceOwn) {
            return json_decode(json_encode($obj), true);
        } else {
            $arr = array();
            if ($obj !== null) {
                $toArray = function (array $objVars, array $arr) use (&$toArray) {
                    foreach ($objVars as $key => $var) {
                        if (is_object($var)) {
                            $arr[$key] = array();
                            $arr[$key] = $toArray(get_object_vars($var), $arr[$key]);
                        } else {
                            $arr[$key] = $var;
                        }
                    }
                    
                    return $arr;
                };
                
                $arr = $toArray(get_object_vars($obj), $arr);
            }
            
            return $arr;
        }
    }
}
