<?php
/**
 *
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Http
 */
namespace jtl\Connector\Core\Http;

use \jtl\Connector\Core\Rpc\ResponsePacket;
use \jtl\Connector\Core\Serializer\Json;
use \jtl\Connector\Core\Logger\Logger;

/**
 * Http Response Handler
 *
 * @access public
 * @author Daniel Böhmer <daniel.boehmer@jtl-software.de>
 */
class Response
{
    /**
     * Http Response sender
     *
     * @param ResponsePacket $responsepacket
     */
    public static function send(ResponsePacket $responsepacket)
    {
        $jsonResponse = Json::encode($responsepacket->getPublic());

        Logger::write($jsonResponse, Logger::DEBUG, 'rpc');

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json', true, 200);
        
        echo $jsonResponse;
        
        exit();
    }
    
    /**
     * Http Response sender
     *
     * @param array $reponsepackets
     */
    public static function sendAll(array $reponsepackets)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json', true, 200);
        
        $packets = array();
        foreach ($reponsepackets as $responsepacket) {
            $response = $responsepacket->getPublic();
            Logger::write(Json::encode($response), Logger::DEBUG, 'rpc');

            $packets[] = $responsepacket->getPublic();
        }
        
        echo Json::encode($packets);
        
        exit();
    }
}
