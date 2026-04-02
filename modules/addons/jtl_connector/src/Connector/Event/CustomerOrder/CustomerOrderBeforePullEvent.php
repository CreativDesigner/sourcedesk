<?php
namespace jtl\Connector\Event\CustomerOrder;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class CustomerOrderBeforePullEvent extends Event
{
    const EVENT_NAME = 'customerorder.before.pull';

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