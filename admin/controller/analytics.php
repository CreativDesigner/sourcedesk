<?php
global $lang, $var, $db, $CFG;

$l = $var['l'] = $lang['ANALYTICS'];
title($l['TITLE']);
menu("statistics");
$tpl = "analytics";

$timeFrame = [
    date("Y-m-d 00:00:00"),
    date("Y-m-d 23:59:59"),
];

if (isset($_POST['from']) && strtotime($_POST['from']) !== false) {
    $timeFrame[0] = date("Y-m-d H:i:s", strtotime($_POST['from']));
}

if (isset($_POST['to']) && strtotime($_POST['to']) !== false) {
    $timeFrame[1] = date("Y-m-d H:i:s", strtotime($_POST['to']));
}

$var['tf'] = $timeFrame;

$counts = $db->query("SELECT SUM(pages) pages, COUNT(*) visitors FROM visits WHERE `time` >= '" . $timeFrame[0] . "' AND `time` <= '" . $timeFrame[1] . "'")->fetch_object();
$var['visitors'] = $counts->visitors;
$var['pages'] = $counts->pages;

$var['start_page'] = $db->query("SELECT COUNT(*) c, start_page p FROM visits WHERE `time` >= '" . $timeFrame[0] . "' AND `time` <= '" . $timeFrame[1] . "' GROUP BY start_page ORDER BY c DESC");
$var['end_page'] = $db->query("SELECT COUNT(*) c, end_page p FROM visits WHERE `time` >= '" . $timeFrame[0] . "' AND `time` <= '" . $timeFrame[1] . "' GROUP BY end_page ORDER BY c DESC");
$var['os'] = $db->query("SELECT COUNT(*) c, os p FROM visits WHERE `time` >= '" . $timeFrame[0] . "' AND `time` <= '" . $timeFrame[1] . "' GROUP BY os ORDER BY c DESC");
$var['browser'] = $db->query("SELECT COUNT(*) c, browser p FROM visits WHERE `time` >= '" . $timeFrame[0] . "' AND `time` <= '" . $timeFrame[1] . "' GROUP BY browser ORDER BY c DESC");
$var['country'] = $db->query("SELECT COUNT(*) c, country p FROM visits WHERE `time` >= '" . $timeFrame[0] . "' AND `time` <= '" . $timeFrame[1] . "' GROUP BY country ORDER BY c DESC");
