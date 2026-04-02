<?php
namespace jtl\Connector\Event\Payment;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Statistic;


class PaymentAfterStatisticEvent extends Event
{
    const EVENT_NAME = 'payment.after.statistic';

	protected $statistic;

    public function __construct(Statistic &$statistic)
    {
		$this->statistic = $statistic;
    }

    public function getStatistic()
    {
        return $this->statistic;
	}
	
}