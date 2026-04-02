<?php
namespace jtl\Connector\Event\Connector;

use jtl\Connector\Model\BoolResult;
use Symfony\Component\EventDispatcher\Event;

class ConnectorAfterFinishEvent extends Event
{
    const EVENT_NAME = 'connector.after.finish';
    
    /**
     * @var BoolResult
     */
    protected $boolResult;
    
    public function __construct(BoolResult &$boolResult)
    {
        $this->boolResult = $boolResult;
    }
    
    public function getBoolResult()
    {
        return $this->boolResult;
    }
    
}