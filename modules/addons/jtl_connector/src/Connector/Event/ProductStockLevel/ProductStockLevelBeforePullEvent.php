<?php
namespace jtl\Connector\Event\ProductStockLevel;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class ProductStockLevelBeforePullEvent extends Event
{
    const EVENT_NAME = 'productstocklevel.before.pull';

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