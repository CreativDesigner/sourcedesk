<?php
namespace jtl\Connector\Event\CrossSelling;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\CrossSelling;


class CrossSellingBeforePushEvent extends Event
{
    const EVENT_NAME = 'crossselling.before.push';

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