<?php
namespace jtl\Connector\Event\Core;

use Symfony\Component\EventDispatcher\Event;

class CoreConnectorAfterFeaturesEvent extends Event
{
    const EVENT_NAME = 'core.connector.features';

	protected $features;

    public function __construct(&$features)
    {
		$this->features = $features;
    }

    public function getFeatures()
    {
        return $this->features;
	}
	
}