<?php
namespace jtl\Connector\Event\Image;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class ImageBeforeStatisticEvent extends Event
{
    const EVENT_NAME = 'image.before.statistic';

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