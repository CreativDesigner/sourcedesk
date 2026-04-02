<?php
namespace jtl\Connector\Event\Specific;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Statistic;


class SpecificAfterStatisticEvent extends Event
{
    const EVENT_NAME = 'specific.after.statistic';

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