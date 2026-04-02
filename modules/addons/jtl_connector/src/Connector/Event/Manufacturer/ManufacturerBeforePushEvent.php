<?php
namespace jtl\Connector\Event\Manufacturer;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Manufacturer;


class ManufacturerBeforePushEvent extends Event
{
    const EVENT_NAME = 'manufacturer.before.push';

	protected $manufacturer;

    public function __construct(Manufacturer &$manufacturer)
    {
		$this->manufacturer = $manufacturer;
    }

    public function getManufacturer()
    {
        return $this->manufacturer;
	}
	
}