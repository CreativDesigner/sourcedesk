<?php
namespace jtl\Connector\Event\ProductPrice;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class ProductPriceBeforeStatisticEvent extends Event
{
    const EVENT_NAME = 'productprice.before.statistic';

	protected $filter;

    public function __construct(QueryFilter &$filter)
    {
		$this->filter = $filter;
    }

    public function getFilter()
    {
        return $this->filter;
	}
	
}