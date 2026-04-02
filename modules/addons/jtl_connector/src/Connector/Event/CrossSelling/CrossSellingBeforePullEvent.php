<?php
namespace jtl\Connector\Event\CrossSelling;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class CrossSellingBeforePullEvent extends Event
{
    const EVENT_NAME = 'crossselling.before.pull';

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