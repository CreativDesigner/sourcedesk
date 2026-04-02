<?php
global $ari, $lang, $nfo, $cur, $db, $CFG;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_FORECAST'];
title($l['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "stat_forecast";

    $var['expected'] = [];

    // Set most specific locale as possible
    try {
        setlocale(LC_TIME, $lang['LCTIME']);
        setlocale(LC_TIME, $lang['LCTIME'] . ".utf8");
    } catch (Exception $ex) {}

    for ($i = 1; $i <= 24; $i++) {
        $var['expected'][$i] = [
            htmlentities(strftime("%B %Y", strtotime("+$i month"))),
            0,
        ];
    }

    $cycles = [
        "monthly" => "1",
        "quarterly" => "3",
        "semiannually" => "6",
        "annually" => "12",
        "biennially" => "24",
        "trinnially" => "36",
    ];

    $sum = 0;

    for ($i = 1; $i <= 24; $i++) {
        $date = date("Y-m", strtotime("+$i month"));

        $maxCan = date("Y-m-t", strtotime("+" . ($i - 1) . " month"));

        $sql = $db->query("SELECT price, billing, last_billed FROM client_products WHERE cancellation_date <= '$maxCan' AND last_billed LIKE '$date-%'");
        while ($row = $sql->fetch_object()) {
            if (array_key_exists($row->billing, $cycles)) {
                $cycle = $cycles[$row->billing];

                for ($i2 = $i + $cycle; $i2 <= 24; $i2 += $cycle) {
                    $var['expected'][$i2][1] += $row->price;
                    $sum += $row->price;
                }
            }

            $var['expected'][$i][1] += $row->price;
            $sum += $row->price;
        }
    }

    foreach ($var['expected'] as &$v) {
        $v[1] = $cur->infix($nfo->format($v[1]), $cur->getBaseCurrency());
    }
    unset($v);

    $var['expected'][25] = ["<b>" . $l['SUM'] . "</b>", "<b>" . $cur->infix($nfo->format($sum), $cur->getBaseCurrency()) . "</b>"];
} else {
    alog("general", "insufficient_page_rights", "daily_performance");
    $tpl = "error";
}
