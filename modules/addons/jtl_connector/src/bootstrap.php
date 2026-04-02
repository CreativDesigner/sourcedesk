<?php
/**
 * @copyright 2010-2015 JTL-Software GmbH
 * @package jtl\Connector\Example
 */

use jtl\Connector\Application\Application;
use jtl\Connector\Example\Connector;

$application = null;

function jtl_connector_class_loader($class)
{
    $class = explode("\\", $class);

    if (array_shift($class) != "jtl" || array_shift($class) != "Connector") {
        return;
    }

    if (array_values($class)[0] == "Example") {
        array_shift($class);
        include __DIR__ . "/" . implode("/", $class) . ".php";
    } else {
        include __DIR__ . "/Connector/" . implode("/", $class) . ".php";
    }
}
spl_autoload_register("jtl_connector_class_loader");

try {
    $logDir = CONNECTOR_DIR . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        mkdir($logDir);
        chmod($logDir, 0777);
    }

    // Connector instance
    $connector = Connector::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
} catch (\Exception $e) {
    if (is_object($application)) {
        $handler = $application->getErrorHandler()->getExceptionHandler();
        $handler($e);
    }
}
