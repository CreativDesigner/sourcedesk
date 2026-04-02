<?php

/**
 * enthält Funktionen für die Ausgabe der unterstützten Shopsysteme
 */
class Shopsystems
{
    private static $supportedShops = array(
        "gambio" => array(
            "name" => "Gambio",
            "filename" => "gambio"
        ),
        "individuell" => array(
            "name" => "Angepasstes System",
            "filename" => "individuell"
        ),
        "koobi" => array(
            "name" => "koobi",
            "filename" => "koobi"
        ),
        "magento" => array(
            "name" => "Magento",
            "filename" => "magento"
        ),
        "oscommerce" => array(
            "name" => "osCommerce",
            "filename" => "oscommerce"
        ),
        "prestashop" => array(
            "name" => "Prestashop",
            "filename" => "prestashop"
        ),
        "shopware" => array(
            "name" => "Shopware 3.5.x",
            "filename" => "shopware"
        ),
        "veyton" => array(
            "name" => "xt:Commerce 4 / 5",
            "filename" => "veyton"
        ),
        "virtuemart" => array(
            "name" => "VirtueMart",
            "filename" => "virtuemart"
        ),
        "virtuemart2" => array(
            "name" => "VirtueMart 2 & 3",
            "filename" => "virtuemart2"
        ),
        "xaran" => array(
            "name" => "Xaran Shop",
            "filename" => "xaran"
        ),
        "xtcommerce" => array(
            "name" => "xt:Commerce / xt:Modified",
            "filename" => "xtcommerce"
        )
    );

    public static function getList()
    {
        return Shopsystems::$supportedShops;
    }

    public static function getSortedList()
    {
        $shopList = Shopsystems::$supportedShops;
        $name = array();
        foreach($shopList as $key => $row)
        {
            // strtolower, damit nach Alphabet sortiert und Groß-/Kleinschreibung nicht berücksichtigt wird.
            $name[$key] = strtolower($row['name']);
        }
        array_multisort($name, SORT_ASC, $shopList);

        return $shopList;
    }
}

?>