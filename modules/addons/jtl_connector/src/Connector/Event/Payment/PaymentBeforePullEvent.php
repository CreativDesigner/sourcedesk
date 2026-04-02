<?php
namespace jtl\Connector\Event\Payment;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Core\Model\QueryFilter;


class PaymentBeforePullEvent extends Event
{
    const EVENT_NAME = 'payment.before.pull';

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