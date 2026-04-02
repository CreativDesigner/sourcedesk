<?php
namespace jtl\Connector\Event\Manufacturer;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Statistic;


class ManufacturerAfterStatisticEvent extends Event
{
    const EVENT_NAME = 'manufacturer.after.statistic';

	protected $statistic;

    public function __construct(Statistic &$statistic)
    {
		$this->statistic = $statistic;
    }

    public function getStatistic()
    {
        return $this->statistic;
	}
	
}