<?php
namespace jtl\Connector\Event;

use \Symfony\Component\EventDispatcher\EventDispatcher;
use \jtl\Connector\Model\DataModel;
use \jtl\Connector\Core\Model\QueryFilter;
use \jtl\Connector\Core\Utilities\ClassName;

class EventHandler
{
    const BEFORE = 'before';
    const AFTER = 'after';

    public static function dispatch(&$entity, EventDispatcher $dispatcher, $action, $moment, $class = null, $isCore = false)
    {
        if (!$isCore && (!($entity instanceof DataModel) && !($entity instanceof QueryFilter)) || strlen(trim($action)) == 0 || strlen(trim($moment)) == 0) {
            return;
        }

        $class = ($class !== null) ? $class : ClassName::getFromNS(get_class($entity));
        $event = self::createEvent($entity, $class, $action, $moment, $isCore);

        if ($event !== null) {
            $dispatcher->dispatch($event::EVENT_NAME, $event);
        }
    }

    public static function dispatchRpc(&$data, EventDispatcher $dispatcher, $controller, $action, $moment)
    {
        if (strlen(trim($action)) == 0 || strlen(trim($moment)) == 0) {
            return;
        }

        // Rpc Event
        $event = self::createRpcEvent($data, $controller, $action, $moment);
        if ($event !== null) {
            $dispatcher->dispatch($event::EVENT_NAME, $event);
        }
    }

    protected static function createEvent(&$entity, $class, $action, $moment, $isCore)
    {
        if ($isCore) {
            $moment = sprintf('%s%s', $class, ucfirst($moment));
            $class = 'Core';
        }

        $eventClassname = sprintf('\jtl\Connector\Event\%s\%s%s%sEvent', $class, $class, ucfirst($moment), ucfirst($action));

        if (class_exists($eventClassname)) {
            return new $eventClassname($entity);
        }

        return null;
    }

    protected static function createRpcEvent(&$data, $controller, $action, $moment)
    {
        $eventClassname = sprintf('\jtl\Connector\Event\Rpc\Rpc%sEvent', ucfirst($moment));

        if (class_exists($eventClassname)) {
            return new $eventClassname($data, $controller, $action);
        }

        return null;
    }
}