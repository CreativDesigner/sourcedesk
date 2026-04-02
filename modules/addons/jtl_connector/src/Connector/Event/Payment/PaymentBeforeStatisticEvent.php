<?php
namespace jtl\Connector\Event\Payment;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class PaymentBeforeStatisticEvent extends Event
{
    const EVENT_NAME = 'payment.before.statistic';

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