<?php
global $ari, $lang, $db, $CFG, $nfo, $cur;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_ORDERS'];
title($l['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "stat_orders";

    $var['month'] = !empty($_GET['month']) && is_numeric($_GET['month']) && $_GET['month'] <= 12 && $_GET['month'] >= 1 ? $_GET['month'] : date("m");
    $var['year'] = !empty($_GET['year']) && is_numeric($_GET['year']) && $_GET['year'] <= date("Y") && $_GET['year'] >= 1990 ? $_GET['year'] : date("Y");

    if ($var['year'] == date("Y") && $var['month'] > date("m")) {
        $var['month'] = date("m");
    }

    $lastday = date("t", strtotime("01." . $var['month'] . "." . $var['year']));

    $var['prevmonth'] = $var['month'] - 1;
    $var['prevyear'] = $var['year'];

    if ($var['prevmonth'] <= 0) {
        $var['prevmonth'] = "12";
        $var['prevyear']--;
    }

    $var['month'] = str_pad($var['month'], 2, "0", STR_PAD_LEFT);

    if ($var['year'] < date("Y") || $var['month'] < date("m")) {
        $var['nextmonth'] = $var['month'] + 1;
        $var['nextyear'] = $var['year'];

        if ($var['nextmonth'] > 12) {
            $var['nextmonth'] = "1";
            $var['nextyear']++;
        }
    }

    $starttime = strtotime("01." . $var['month'] . "." . $var['year'] . " 00:00:00");
    $endtime = strtotime(date("t", $starttime) . "." . $var['month'] . "." . $var['year'] . " 00:00:00");

    $var['res'] = [];

    $sql = $db->query("SELECT ID, price, category, name FROM products");
    while ($p = $sql->fetch_object()) {
        $cast = "";
        $cat = "";

        if ($p->category) {
            $cSql = $db->query("SELECT name, cast FROM product_categories WHERE ID = " . intval($p->category));
            if ($cSql->num_rows) {
                $cInfo = $cSql->fetch_object();
                $cast = unserialize($cInfo->cast)[$CFG['LANG']];
                $cat = unserialize($cInfo->name)[$CFG['LANG']];
            }
        }

        if (!array_key_exists($cat, $var['res'])) {
            $var['res'][$cat] = [];
        }

        $name = unserialize($p->name)[$CFG['LANG']];

        $sells = $db->query("SELECT COUNT(*) c FROM client_products WHERE date >= $starttime AND date <= $endtime AND product = {$p->ID}")->fetch_object()->c;

        $var['res'][$cat][$name] = [
            $sells,
            $cur->infix($nfo->format($sells * $p->price), $cur->getBaseCurrency()),
        ];
    }
    
    foreach ($var['res'] as $k => $v) {
        ksort($var['res'][$k]);
    }

    ksort($var['res']);
} else {
    alog("general", "insufficient_page_rights", "daily_performance");
    $tpl = "error";
}
