<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Example\Controller
 */

namespace jtl\Connector\Example\Controller;

use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product2Category;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n;
use jtl\Connector\Model\ProductPrice;
use jtl\Connector\Model\ProductPriceItem;
use jtl\Connector\Result\Action;

class Product extends DataController
{
    public function pull(QueryFilter $filter)
    {
        global $db, $CFG, $lang;

        $action = new Action();
        $action->setHandled(true);

        $products = [];

        $sql = $db->query("SELECT * FROM products");
        while ($row = $sql->fetch_object()) {
            $product = (new ProductModel)->setId(new Identity($row->ID))
                ->setIsActive((bool) $row->status);

            $i18n = (new ProductI18n)->setProductId($product->getId())
                ->setLanguageIso($lang['ISOCODE'])
                ->setName(unserialize($row->name)[$CFG['LANG']]);

            $prices = [
                (new ProductPrice)->setId($product->getId())
                    ->setProductId($product->getId())
                    ->addItem((new ProductPriceItem)
                            ->setProductPriceId($product->getId())
                            ->setQuantity(1)
                            ->setNetPrice((double) $row->price)),
            ];

            $categories = [(new Product2Category)->setId($product->getId())
                    ->setProductId($product->getId())
                    ->setCategoryId(new Identity($row->category))];

            $product = $product->addI18n($i18n)
                ->setPrices($prices)
                ->setCategories($categories);

            $products[] = $product;
        }

        $action->setResult($products);
        return $action;
    }

    public function statistic(QueryFilter $queryFilter)
    {
        global $db, $CFG;

        $action = new Action();
        $action->setHandled(true);

        $action->setResult([
            "available" => $db->query("SELECT 1 FROM products")->num_rows,
            "controllerName" => "product",
        ]);

        return $action;
    }
}
