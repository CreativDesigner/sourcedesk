<?php
namespace jtl\Connector\Event\CrossSelling;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class CrossSellingBeforeStatisticEvent extends Event
{
    const EVENT_NAME = 'crossselling.before.statistic';

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