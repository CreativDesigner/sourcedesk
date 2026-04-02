<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['RECURRING_INVOICE'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($l['TITLE']);
menu("customers");

$inv = RecurringInvoice::getInstance(isset($_GET['id']) ? $_GET['id'] : -1);

if ($ari->check(13) && $inv) {
    $uI = User::getInstance($inv->getUser(), "ID");

    if (isset($_POST['description'])) {
        try {
            if (empty($_POST['interval1']) || !is_numeric($_POST['interval1']) || $_POST['interval1'] <= 0 || empty($_POST['interval2']) || !in_array($_POST['interval2'], array("day", "week", "month", "year"))) {
                throw new Exception($l['ERR1']);
            }

            if (empty($_POST['description'])) {
                throw new Exception($l['ERR2']);
            }

            if (empty($_POST['amount']) || doubleval($nfo->phpize($_POST['amount'])) != $nfo->phpize($_POST['amount']) || $nfo->phpize($_POST['amount']) <= 0) {
                throw new Exception($l['ERR3']);
            }

            $show_period = isset($_POST['show_period']) && $_POST['show_period'] == "yes" ? 1 : 0;

            if (!isset($_POST['status']) || !in_array($_POST['status'], ["0", "1"])) {
                throw new Exception($l['ERR4']);
            }

            $limit_invoices = isset($_POST['limit_invoices']) && $_POST['limit_invoices'] != "" ? intval($_POST['limit_invoices']) : -1;
            $limit_date = !empty($_POST['limit_date']) && strtotime($_POST['limit_date']) !== false ? date("Y-m-d", strtotime($_POST['limit_date'])) : "0000-00-00";

            $sql = $db->prepare("UPDATE invoice_items_recurring SET limit_invoices = ?, limit_date = ?, status = ?, description = ?, amount = ?, show_period = ?, period = ? WHERE ID = ?");
            $sql->bind_param("isisdisi", $limit_invoices, $limit_date, $_POST['status'], $_POST['description'], $a = $nfo->phpize($_POST['amount']), $show_period, $b = $_POST['interval1'] . " " . $_POST['interval2'], $inv->getId());
            $sql->execute();
            $sql->close();

            alog("recurring_invoice", "changed", $inv->getId());
            $var['success'] = $l['SUC'];
            unset($_POST);
            $inv = RecurringInvoice::getInstance($inv->getId());
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    $tpl = "recurring_invoice";

    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['first'] = $dfo->format($inv->getFirst(), false);
    $var['next'] = $dfo->format($inv->getNext(), false);
    $var['last'] = $inv->info->last != "0000-00-00" ? $dfo->format($inv->info->last, false) : 0;
    $var['int1'] = array_shift(explode(" ", $inv->getInterval()));
    $var['int2'] = array_pop(explode(" ", $inv->getInterval()));
    $var['desc'] = htmlentities($inv->info->description);
    $var['amount'] = $inv->getAmount();
    $var['show_period'] = $inv->info->show_period;
    $var['status'] = $inv->getStatus();
    $var['user'] = $uI->get();
    $var['limit_invoices'] = $inv->info->limit_invoices >= 0 ? $inv->info->limit_invoices : -1;
    $var['limit_date'] = $inv->info->limit_date != "0000-00-00" ? $inv->info->limit_date : "";

    $sql = $db->query("SELECT invoice FROM invoiceitems WHERE recurring = {$inv->getId()} GROUP BY invoice ORDER BY invoice ASC");
    $var['invs'] = "";
    while ($row = $sql->fetch_object()) {
        $var['invs'] .= "<a href='?p=invoice&id={$row->invoice}'>{$row->invoice}</a>, ";
    }

    $var['invs'] = rtrim($var['invs'], ', ');
    alog("recurring_invoice", "viewed", $inv->getId());

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
