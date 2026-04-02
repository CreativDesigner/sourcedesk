<?php
namespace jtl\Connector\Event\Customer;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Customer;


class CustomerAfterPullEvent extends Event
{
    const EVENT_NAME = 'customer.after.pull';

	protected $customer;

    public function __construct(Customer &$customer)
    {
		$this->customer = $customer;
    }

    public function getCustomer()
    {
        return $this->customer;
	}
	
}