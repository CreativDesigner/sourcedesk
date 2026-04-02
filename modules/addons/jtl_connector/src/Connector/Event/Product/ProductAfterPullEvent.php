<?php
namespace jtl\Connector\Event\Product;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\Product;


class ProductAfterPullEvent extends Event
{
    const EVENT_NAME = 'product.after.pull';

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