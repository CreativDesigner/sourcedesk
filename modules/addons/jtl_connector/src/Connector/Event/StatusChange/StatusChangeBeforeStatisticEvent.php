<?php
namespace jtl\Connector\Event\StatusChange;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class StatusChangeBeforeStatisticEvent extends Event
{
    const EVENT_NAME = 'statuschange.before.statistic';

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