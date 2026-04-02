<?php
namespace jtl\Connector\Event\ProductPrice;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Statistic;


class ProductPriceAfterStatisticEvent extends Event
{
    const EVENT_NAME = 'productprice.after.statistic';

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