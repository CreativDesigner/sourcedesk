<?php

/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Http
 */

namespace jtl\Connector\Core\Http;

/**
 * Http SSL class that can return specific information.
 *
 * @access public
 * @author David Spickers <david.spickers@jtl-software.de>
 */
class Ssl
{

    /**
     * Returns if the connector is actually using the ssl encryption.
     * @return type
     */
    public static function isEnabled()
    {
        $srv = (isset($_SERVER)) ? $_SERVER : array();
        $https = array();
        $https[] = isset($srv['HTTP_X_FORWARDED_HOST']) && $srv['HTTP_X_FORWARDED_HOST'] == 'ssl.webpack.de'; //Hosteurope
        $https[] = isset($srv['SCRIPT_URI']) && preg_match("/^ssl-id/", $srv['SCRIPT_URI']); //Strato
        $https[] = isset($srv['HTTP_X_FORWARDED_HOST']) && preg_match("/^ssl/", $srv['HTTP_X_FORWARDED_HOST']); //1und1
        $https[] = isset($srv['HTTPS']) && $srv['HTTPS'] == 1; //Default

        $enabled = false;
        foreach ($https as $http) {
            $enabled = $enabled || $http;
            if ($enabled) {
                return $enabled;
            }
        }

        return $enabled;
    }
}
