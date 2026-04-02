<?php
namespace jtl\Connector\Event\DeliveryNote;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\DeliveryNote;


class DeliveryNoteAfterPushEvent extends Event
{
    const EVENT_NAME = 'deliverynote.after.push';

	protected $deliveryNote;

    public function __construct(DeliveryNote &$deliveryNote)
    {
		$this->deliveryNote = $deliveryNote;
    }

    public function getDeliveryNote()
    {
        return $this->deliveryNote;
	}
	
}