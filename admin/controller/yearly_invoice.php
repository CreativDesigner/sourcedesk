<?php
global $ari, $lang, $CFG, $db, $var;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['YEARLY_INVOICE'];
title($l['TITLE']);
menu("settings");

// Check admin rights
if ($ari->check(25) && !empty($_GET['cid']) && is_numeric($_GET['cid']) && ($user = User::getInstance($_GET['cid'], "ID"))) {
    $var['cname'] = $user->getfName();
    $var['years'] = [];

    $sql = $db->query("SELECT YEAR(date) year FROM invoices WHERE client = {$user->get()['ID']} GROUP BY YEAR(date)");
    while ($row = $sql->fetch_object()) {
        array_push($var['years'], $row->year);
    }
    asort($var['years']);

    if (!empty($_GET['year']) && in_array($_GET['year'], $var['years'])) {
        $pdf = YearlyInvoice::init($user->get()['ID'], $_GET['year']);
        $pdf->output($l['FILENAME'] . "-" . $_GET['year'] . "-" . $user->get()['ID'], "I");
        exit;
    }

    $tpl = "yearly_invoice";
} else {
    if (!$ari->check(25)) {
        alog("general", "insufficient_page_rights", "update");
    }
    $tpl = "error";
}
