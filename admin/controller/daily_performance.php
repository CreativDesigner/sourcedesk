<?php
global $ari, $lang, $dfo, $db, $CFG, $cur, $nfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['DAILY_PERFORMANCE'];
title($l['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "daily_performance";

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

    $var['res'] = [];
    $var['sum'] = [
        0,
        0,
        0,
        0,
        0,
        0,
    ];

    for ($i = 1; $i <= $lastday; $i++) {
        if ($var['year'] == date("Y") && $var['month'] == date("m") && $i > date("d")) {
            break;
        }

        $date = $i . ".{$var['month']}.{$var['year']}";
        $starttime = strtotime($date . " 00:00:00");
        $endtime = strtotime($date . " 23:59:59");
        $stamp = date("Y-m-d", $starttime);

        $invoiceSum = 0;
        $inv = new Invoice;

        $sql = $db->query("SELECT ID FROM invoices WHERE date = '$stamp'");
        while ($row = $sql->fetch_object()) {
            if ($inv->load($row->ID)) {
                $invoiceSum += $inv->getAmount();
            }
        }

        $var['res'][$dfo->format($i . ".{$var['month']}.{$var['year']}", false, false)] = [
            $db->query("SELECT COUNT(*) c FROM clients WHERE registered >= $starttime AND registered <= $endtime")->fetch_object()->c,
            $db->query("SELECT COUNT(*) c FROM client_products WHERE date >= $starttime AND date <= $endtime")->fetch_object()->c,
            $db->query("SELECT COUNT(*) c FROM invoices WHERE date = '$stamp'")->fetch_object()->c,
            $cur->infix($nfo->format($invoiceSum), $cur->getBaseCurrency()),
            $db->query("SELECT COUNT(*) c FROM support_tickets WHERE created LIKE '$stamp%'")->fetch_object()->c,
            $db->query("SELECT COUNT(*) c FROM support_ticket_answers WHERE time LIKE '$stamp%'")->fetch_object()->c,
        ];

        $var['sum'][0] += $db->query("SELECT COUNT(*) c FROM clients WHERE registered >= $starttime AND registered <= $endtime")->fetch_object()->c;
        $var['sum'][1] += $db->query("SELECT COUNT(*) c FROM client_products WHERE date >= $starttime AND date <= $endtime")->fetch_object()->c;
        $var['sum'][2] += $db->query("SELECT COUNT(*) c FROM invoices WHERE date = '$stamp'")->fetch_object()->c;
        $var['sum'][3] += $invoiceSum;
        $var['sum'][4] += $db->query("SELECT COUNT(*) c FROM support_tickets WHERE created LIKE '$stamp%'")->fetch_object()->c;
        $var['sum'][5] += $db->query("SELECT COUNT(*) c FROM support_ticket_answers WHERE time LIKE '$stamp%'")->fetch_object()->c;
    }

    $var['sum'][3] = $cur->infix($nfo->format($var['sum'][3]), $cur->getBaseCurrency());
} else {
    alog("general", "insufficient_page_rights", "daily_performance");
    $tpl = "error";
}
