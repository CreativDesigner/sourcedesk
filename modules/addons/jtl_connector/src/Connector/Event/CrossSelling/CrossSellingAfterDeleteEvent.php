<?php
namespace jtl\Connector\Event\CrossSelling;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\CrossSelling;


class CrossSellingAfterDeleteEvent extends Event
{
    const EVENT_NAME = 'crossselling.after.delete';

	protected $crossSelling;

    public function __construct(CrossSelling &$crossSelling)
    {
		$this->crossSelling = $crossSelling;
    }

    public function getCrossSelling()
    {
        return $this->crossSelling;
	}
	
}