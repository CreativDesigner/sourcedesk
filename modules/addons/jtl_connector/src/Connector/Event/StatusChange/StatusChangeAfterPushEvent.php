<?php
namespace jtl\Connector\Event\StatusChange;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\StatusChange;


class StatusChangeAfterPushEvent extends Event
{
    const EVENT_NAME = 'statuschange.after.push';

	protected $statusChange;

    public function __construct(StatusChange &$statusChange)
    {
		$this->statusChange = $statusChange;
    }

    public function getStatusChange()
    {
        return $this->statusChange;
	}
	
}