<?php
namespace jtl\Connector\Event\StatusChange;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Statistic;


class StatusChangeAfterStatisticEvent extends Event
{
    const EVENT_NAME = 'statuschange.after.statistic';

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