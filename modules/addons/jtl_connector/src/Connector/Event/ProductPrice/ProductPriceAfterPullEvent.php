<?php
namespace jtl\Connector\Event\ProductPrice;

use Symfony\Component\EventDispatcher\Event;
use jtl\Connector\Model\ProductPrice;


class ProductPriceAfterPullEvent extends Event
{
    const EVENT_NAME = 'productprice.after.pull';

	protected $productPrice;

    public function __construct(ProductPrice &$productPrice)
    {
		$this->productPrice = $productPrice;
    }

    public function getProductPrice()
    {
        return $this->productPrice;
	}
	
}