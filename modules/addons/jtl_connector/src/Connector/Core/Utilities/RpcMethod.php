<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

use \jtl\Connector\Core\Rpc\Method;

/**
 * Rpc Method Utilities
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class RpcMethod
{
    /**
     * Method validation
     *
     * @param string $method
     * @return boolean
     */
    public static function isMethod($method)
    {
        $pregcore = "";
        if (strpos($method, "core.") !== false) {
            $pregcore = "core.";
        }
        
        if (preg_match("/{$pregcore}[a-z0-9]{3,}[.]{1}[a-z0-9]{3,}/", $method) === 1) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Controller and Action Splitter
     *
     * @param string $method
     * @return \jtl\Connector\Core\Rpc\Method
     */
    public static function splitMethod($method)
    {
        $methodObj = new Method($method);
        
        if (strpos($method, "core.") !== false) {
            list($core, $controller, $action) = explode(".", $method);
        } else {
            list($controller, $action) = explode(".", $method);
        }
        
        $methodObj->setController($controller)
            ->setAction($action);
        
        return $methodObj;
    }
    
    /**
     * Controller Name Builder
     *
     * @param string $controller
     * @return string
     */
    public static function buildController($controller)
    {
        return self::convert($controller);
    }

    /**
     * Action Name Builder
     *
     * @param string $action
     * @return string
     */
    public static function buildAction($action)
    {
        return self::convert($action, true);
    }

    /**
     * String converter
     *
     * @param string $str
     * @param bool $isAction
     * @return string
     */
    protected static function convert($str, $isAction = false)
    {
        $pos = strpos($str, '_');
        if ($pos !== false) {
            $parts = explode('_', $str);
            $str = '';
            foreach ($parts as $i => $part) {
                $str .= ($i == 0 && $isAction) ? $part : ucfirst($part);
            }
            
            return $str;
        } else {
            return $isAction ? $str : ucfirst($str);
        }
    }
}
