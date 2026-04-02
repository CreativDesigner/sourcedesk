<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Example\Controller
 */

namespace jtl\Connector\Example\Controller;

use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\CategoryI18n as CategoryI18nModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\Result\Action;

class Category extends DataController
{
    public function pull(QueryFilter $filter)
    {
        global $db, $CFG, $lang;

        $action = new Action();
        $action->setHandled(true);

        $categories = [];

        $sql = $db->query("SELECT * FROM product_categories");
        while ($row = $sql->fetch_object()) {
            $category = (new CategoryModel)->setId(new Identity($row->ID))
                ->setIsActive(true);

            $i18n = (new CategoryI18nModel)->setCategoryId($category->getId())
                ->setLanguageIso($lang['ISOCODE'])
                ->setName(unserialize($row->name)[$CFG['LANG']]);

            $categories[] = $category->addI18n($i18n);
        }

        $action->setResult($categories);
        return $action;
    }

    public function statistic(QueryFilter $queryFilter)
    {
        global $db, $CFG;

        $action = new Action();
        $action->setHandled(true);

        $action->setResult([
            "available" => $db->query("SELECT 1 FROM product_categories")->num_rows,
            "controllerName" => "category",
        ]);

        return $action;
    }
}
