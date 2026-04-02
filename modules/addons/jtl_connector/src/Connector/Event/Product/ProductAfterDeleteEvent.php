<?php
namespace jtl\Connector\Event\Product;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Product;


class ProductAfterDeleteEvent extends Event
{
    const EVENT_NAME = 'product.after.delete';

	protected $product;

    public function __construct(Product &$product)
    {
		$this->product = $product;
    }

    public function getProduct()
    {
        return $this->product;
	}
	
}