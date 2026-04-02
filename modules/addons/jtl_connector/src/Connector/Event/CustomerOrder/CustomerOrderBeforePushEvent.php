<?php
namespace jtl\Connector\Event\CustomerOrder;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\CustomerOrder;


class CustomerOrderBeforePushEvent extends Event
{
    const EVENT_NAME = 'customerorder.before.push';

	protected $customerOrder;

    public function __construct(CustomerOrder &$customerOrder)
    {
		$this->customerOrder = $customerOrder;
    }

    public function getCustomerOrder()
    {
        return $this->customerOrder;
	}
	
}