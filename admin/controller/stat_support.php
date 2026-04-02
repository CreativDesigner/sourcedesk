<?php
global $ari, $lang, $db, $CFG, $nfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_SUPPORT'];
title($l['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "stat_support";

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

    $var['ratings'] = [];

    $startdate = date("Y-m-d", strtotime("01." . $var['month'] . "." . $var['year'])) . " 00:00:00";
    $enddate = date("Y-m-d", strtotime(date("t", strtotime($startdate)) . "." . $var['month'] . "." . $var['year'])) . " 23:59:59";

    $overall = $db->query("SELECT COUNT(*) c FROM support_tickets WHERE created >= '$startdate' AND created <= '$enddate'")->fetch_object()->c;

    array_push($var['ratings'], [
        $l['GOOD'],
        $count = $db->query("SELECT COUNT(*) c FROM support_tickets WHERE rating = 1 AND created >= '$startdate' AND created <= '$enddate'")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    array_push($var['ratings'], [
        $l['BAD'],
        $count = $db->query("SELECT COUNT(*) c FROM support_tickets WHERE rating = 2 AND created >= '$startdate' AND created <= '$enddate'")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    array_push($var['ratings'], [
        $l['WAITING'],
        $count = $db->query("SELECT COUNT(*) c FROM support_tickets WHERE rating = 3 AND created >= '$startdate' AND created <= '$enddate'")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    array_push($var['ratings'], [
        $l['NOTSENT'],
        $count = $db->query("SELECT COUNT(*) c FROM support_tickets WHERE rating = 0 AND created >= '$startdate' AND created <= '$enddate'")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    array_push($var['ratings'], [
        $l['DISABLED'],
        $count = $db->query("SELECT COUNT(*) c FROM support_tickets WHERE rating = -1 AND created >= '$startdate' AND created <= '$enddate'")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);
} else {
    alog("general", "insufficient_page_rights", "daily_performance");
    $tpl = "error";
}
