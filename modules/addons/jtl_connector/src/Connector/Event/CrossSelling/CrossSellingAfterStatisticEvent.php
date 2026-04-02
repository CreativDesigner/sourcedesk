<?php
namespace jtl\Connector\Event\CrossSelling;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Statistic;


class CrossSellingAfterStatisticEvent extends Event
{
    const EVENT_NAME = 'crossselling.after.statistic';

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