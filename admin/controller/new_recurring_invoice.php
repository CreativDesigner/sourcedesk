<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['RECURRING_INVOICE'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($l['TITLEC']);
menu("customers");

$uI = User::getInstance($_GET['user'], "ID");

if ($ari->check(13) && $uI) {
    if (isset($_POST['description'])) {
        try {
            if (empty($_POST['first']) || strtotime($_POST['first']) === false) {
                throw new Exception($l['ERR5']);
            }

            if (empty($_POST['interval1']) || !is_numeric($_POST['interval1']) || $_POST['interval1'] <= 0 || empty($_POST['interval2']) || !in_array($_POST['interval2'], array("day", "week", "month", "year"))) {
                throw new Exception($l['ERR6']);
            }

            if (empty($_POST['description'])) {
                throw new Exception($l['ERR7']);
            }

            if (empty($_POST['amount']) || doubleval($nfo->phpize($_POST['amount'])) != $nfo->phpize($_POST['amount']) || $nfo->phpize($_POST['amount']) <= 0) {
                throw new Exception($l['ERR8']);
            }

            $show_period = isset($_POST['show_period']) && $_POST['show_period'] == "yes" ? 1 : 0;

            if (!isset($_POST['status']) || !in_array($_POST['status'], ["0", "1"])) {
                throw new Exception($l['ERR9']);
            }

            $amount = $nfo->phpize($_POST['amount']);
            if (isset($_POST['net']) && $_POST['net'] == "yes") {
                $amount = $uI->addTax($amount);
            }

            $limit_invoices = isset($_POST['limit_invoices']) && $_POST['limit_invoices'] != "" ? intval($_POST['limit_invoices']) : -1;
            $limit_date = !empty($_POST['limit_date']) && strtotime($_POST['limit_date']) !== false ? date("Y-m-d", strtotime($_POST['limit_date'])) : "0000-00-00";

            $sql = $db->prepare("INSERT INTO invoice_items_recurring (`limit_invoices`, `limit_date`, `user`, `first`, `status`, `description`, `amount`, `show_period`, `period`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sql->bind_param("isisisdis", $limit_invoices, $limit_date, $_GET['user'], $a = date("Y-m-d", strtotime($_POST['first'])), $_POST['status'], $_POST['description'], $amount, $show_period, $c = $_POST['interval1'] . " " . $_POST['interval2']);
            $sql->execute();
            $sql->close();

            $id = $db->insert_id;

            $obj = new RecurringInvoice($id);
            $inv = $obj->bill();
            alog("recurring_invoice", "created", $id);

            $invoice = $inv !== false ? $l['SUC1'] : $l['SUC2'];
            $var['success'] = $l['SUCC'] . " $invoice";
            unset($_POST);
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    $tpl = "new_recurring_invoice";

    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['user'] = $uI->get();

    $var['products'] = [];

    $sql = $db->query("SELECT name, price, description FROM products");
    while ($row = $sql->fetch_object()) {
        array_push($var['products'], [
            unserialize($row->name)[$CFG['LANG']] . " - " . $nfo->format($row->price),
            unserialize($row->description)[$CFG['LANG']],
        ]);
    }
} else {
    alog("general", "insufficient_page_rights", "new_invoice");
    $tpl = "error";
}
