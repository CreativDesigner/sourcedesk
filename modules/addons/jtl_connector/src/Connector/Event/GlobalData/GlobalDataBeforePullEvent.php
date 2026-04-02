<?php
namespace jtl\Connector\Event\GlobalData;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class GlobalDataBeforePullEvent extends Event
{
    const EVENT_NAME = 'globaldata.before.pull';

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