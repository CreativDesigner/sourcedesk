<?php
global $ari, $lang, $db, $CFG, $nfo, $languages;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_CUSTOMERS'];
title($l['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "stat_customers";

    $overall = $db->query("SELECT COUNT(*) c FROM clients")->fetch_object()->c;

    // Countries
    $var['countries'] = [];

    $sql = $db->query("SELECT ID, name FROM client_countries ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        array_push($var['countries'], [
            $row->name,
            $count = $db->query("SELECT COUNT(*) c FROM clients WHERE country = {$row->ID}")->fetch_object()->c,
            $nfo->format($count / $overall * 100) . " %",
        ]);
    }

    array_push($var['countries'], [
        $l['NT'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE country = 0")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Currencies
    $var['currencies'] = [];

    $sql = $db->query("SELECT currency_code, name FROM currencies ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        array_push($var['currencies'], [
            $row->name,
            $count = $db->query("SELECT COUNT(*) c FROM clients WHERE currency = '{$row->currency_code}'")->fetch_object()->c,
            $nfo->format($count / $overall * 100) . " %",
        ]);
    }

    array_push($var['currencies'], [
        $l['NT'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE currency = ''")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Languages
    $var['languages'] = [];

    foreach ($languages as $slug => $name) {
        array_push($var['languages'], [
            $name,
            $count = $db->query("SELECT COUNT(*) c FROM clients WHERE language = '{$slug}'")->fetch_object()->c,
            $nfo->format($count / $overall * 100) . " %",
        ]);
    }

    array_push($var['languages'], [
        $l['NT'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE language = ''")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Gender
    $var['gender'] = [];

    foreach (["MALE" => $l['MALE'], "FEMALE" => $l['FEMALE'], "DIVERS" => $l['DIVERS']] as $slug => $name) {
        array_push($var['gender'], [
            $name,
            $count = $db->query("SELECT COUNT(*) c FROM clients WHERE salutation = '{$slug}'")->fetch_object()->c,
            $nfo->format($count / $overall * 100) . " %",
        ]);
    }

    array_push($var['gender'], [
        $l['NT'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE salutation = ''")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Ages
    $var['ages'] = [];

    $ages = [
        [0, 17],
        [18, 25],
        [26, 35],
        [36, 45],
        [46, 50],
        [51, 60],
        [61, 70],
        [71, 100],
    ];

    foreach ($ages as $a) {
        $min = $a[0];
        $max = $a[1];

        array_push($var['ages'], [
            "$min - $max",
            0,
            "",
        ]);
    }

    $sql = $db->query("SELECT birthday FROM clients WHERE birthday != '0000-00-00'");
    while ($row = $sql->fetch_object()) {
        $birthDate = explode("/", date("m/d/Y", strtotime($row->birthday)));
        $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md") ? ((date("Y") - $birthDate[2]) - 1) : (date("Y") - $birthDate[2]));

        foreach ($ages as $k => $a) {
            if ($age >= $a[0] && $age <= $a[1]) {
                $var['ages'][$k][1]++;
                break;
            }
        }
    }

    foreach ($var['ages'] as &$v) {
        $v[2] = $nfo->format($v[1] / $overall * 100) . " %";
    }
    unset($v);

    array_push($var['ages'], [
        $l['NT'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE birthday = '0000-00-00'")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Types
    $var['types'] = [];

    array_push($var['types'], [
        $l['B2C'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE company = ''")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    array_push($var['types'], [
        $l['B2B'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE company != ''")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Group
    $var['groups'] = [];

    $sql = $db->query("SELECT ID, name, color FROM client_groups ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        array_push($var['groups'], [
            "<span style=\"background-color: {$row->color};\">{$row->name}</span>",
            $count = $db->query("SELECT COUNT(*) c FROM clients WHERE cgroup = '{$row->ID}'")->fetch_object()->c,
            $nfo->format($count / $overall * 100) . " %",
        ]);
    }

    array_push($var['groups'], [
        $l['NT2'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE cgroup = 0")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    // Properties
    $var['properties'] = [];

    array_push($var['properties'], [
        $l['VERIFIED'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE verified = 1")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);

    array_push($var['properties'], [
        $l['LOCKED'],
        $count = $db->query("SELECT COUNT(*) c FROM clients WHERE locked = 1")->fetch_object()->c,
        $nfo->format($count / $overall * 100) . " %",
    ]);
} else {
    alog("general", "insufficient_page_rights", "daily_performance");
    $tpl = "error";
}
