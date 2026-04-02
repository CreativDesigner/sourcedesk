<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['EINVOICE'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

menu("payments");
title($lang['EINVOICE']['TITLE']);

$inv = new Invoice;
if ($ari->check(13) && isset($_GET['id']) && $inv->load($_GET['id'])) {
    $currencySql = $db->query("SELECT currency_code, name FROM currencies ORDER BY name ASC");
    $currencies = array();
    while ($row = $currencySql->fetch_assoc()) {
        $currencies[$row["currency_code"]] = $row['name'];
    }

    $sql = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
    $var['countries'] = array();
    while ($row = $sql->fetch_object()) {
        $var['countries'][$row->ID] = $row->name;
    }

    if (isset($_POST['invoiceitem_description'])) {
        try {
            if (empty($_POST['date']) || strtotime($_POST['date']) === false) {
                throw new Exception($l['ERR1']);
            }

            if (empty($_POST['deliverydate']) || strtotime($_POST['deliverydate']) === false) {
                throw new Exception($l['ERR2']);
            }

            if (empty($_POST['duedate']) || strtotime($_POST['duedate']) === false) {
                throw new Exception($l['ERR3']);
            }

            if (strtotime($_POST['date']) > strtotime($_POST['duedate'])) {
                throw new Exception($l['ERR4']);
            }

            if (!empty($_POST['customno']) && $db->query("SELECT 1 FROM invoices WHERE customno LIKE '" . $db->real_escape_string($_POST['customno']) . "' AND ID != " . intval($_GET['id']))->num_rows != 0) {
                throw new Exception($l['ERR5']);
            }

            $cd = array();

            if (empty($_POST['firstname'])) {
                throw new Exception($l['ERR6']);
            }

            $cd['firstname'] = $_POST['firstname'];

            if (empty($_POST['lastname'])) {
                throw new Exception($l['ERR7']);
            }

            $cd['lastname'] = $_POST['lastname'];

            $cd['company'] = $_POST['company'];

            if (empty($_POST['street'])) {
                throw new Exception($l['ERR8']);
            }

            $cd['street'] = $_POST['street'];

            if (empty($_POST['street_number'])) {
                throw new Exception($l['ERR9']);
            }

            $cd['street_number'] = $_POST['street_number'];

            if (empty($_POST['postcode'])) {
                throw new Exception($l['ERR10']);
            }

            $cd['postcode'] = $_POST['postcode'];

            if (empty($_POST['city'])) {
                throw new Exception($l['ERR11']);
            }

            $cd['city'] = $_POST['city'];

            if (empty($_POST['icountry']) || !array_key_exists($_POST['icountry'], $var['countries'])) {
                throw new Exception($l['ERR12']);
            }

            $cd['country'] = $var['countries'][$_POST['icountry']];

            if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception($l['ERR13']);
            }

            $cd['email'] = $_POST['email'];

            if (!empty($_POST['vatid'])) {
                $obj = new EuVAT($_POST['vatid']);
                if (!$obj->isValid()) {
                    throw new Exception($l['ERR14']);
                }

                $cd['vatid'] = $_POST['vatid'];
            }

            if (empty($_POST['language']) || !array_key_exists($_POST['language'], $GLOBALS['languages'])) {
                throw new Exception($l['ERR15']);
            }

            $cd['language'] = $_POST['language'];

            $tax = array('', 0);
            if (!empty($_POST['ptax0'])) {
                $tax[0] = $_POST['ptax0'];
            }

            if (isset($_POST['ptax1'])) {
                $t1 = $nfo->phpize($_POST['ptax1']);
                if (doubleval($t1) != $t1 && intval($t1) != $t1) {
                    throw new Exception($l['ERR16']);
                }

                $tax[1] = $t1;
            }
            $cd['ptax'] = $tax;

            if (empty($_POST['currency']) || !array_key_exists($_POST['currency'], $currencies)) {
                throw new Exception($l['ERR17']);
            }

            $cd['currency'] = $_POST['currency'];

            if (!isset($_POST['paid_amount']) || ($nfo->phpize($_POST['paid_amount']) != doubleval($nfo->phpize($_POST['paid_amount'])) && $nfo->phpize($_POST['paid_amount']) != intval($nfo->phpize($_POST['paid_amount'])))) {
                throw new Exception($l['ERR18']);
            }

            if ($nfo->phpize($_POST['paid_amount']) > $inv->getAmount() + $inv->getLateFees()) {
                throw new Exception($l['ERR19']);
            }

            if (!isset($_POST['latefee']) || ($nfo->phpize($_POST['latefee']) != doubleval($nfo->phpize($_POST['latefee'])) && $nfo->phpize($_POST['latefee']) != intval($nfo->phpize($_POST['latefee'])))) {
                throw new Exception($l['ERR20']);
            }

            if (!is_array($_POST['invoiceitem_description']) || !is_array($_POST['invoiceitem_amount']) || count($_POST['invoiceitem_description']) != count($_POST['invoiceitem_amount'])) {
                throw new Exception($l['ERR21']);
            }

            $oldItems = $inv->getItems();
            foreach ($_POST['invoiceitem_description'] as $k => $d) {
                $a = $_POST['invoiceitem_amount'][$k];

                if (empty($d)) {
                    throw new Exception($l['ERR22']);
                }

                if (!isset($a) || (doubleval($nfo->phpize($a)) != $nfo->phpize($a) && intval($nfo->phpize($a)) != $nfo->phpize($a))) {
                    throw new Exception($l['ERR23']);
                }

            }

            $items = array();
            foreach ($_POST['invoiceitem_description'] as $k => $d) {
                $a = $nfo->phpize($_POST['invoiceitem_amount'][$k]);

                if (isset($_POST['net']) && $_POST['net'] == "yes" && isset($_POST['invoiceitem_tax'][$k]) && $_POST['invoiceitem_tax'][$k] == "1") {
                    $a *= $tax[1] / 100 + 1;
                }

                $item = array_shift($oldItems);
                if ($item === null) {
                    $item = new InvoiceItem;
                }

                $item->setDescription($d);
                $item->setAmount($a);
                $item->setTax(isset($_POST['invoiceitem_tax'][$k]) && $_POST['invoiceitem_tax'][$k] == "1");
                $item->setQty(floatval($nfo->phpize($_POST['invoiceitem_qty'][$k] ?? 1)));
                $item->setUnit($_POST['invoiceitem_unit'][$k] ?? "x");
                array_push($items, $item);
            }

            while (null !== ($oldItem = array_shift($oldItems))) {
                $oldItem->delete();
            }

            $inv->setDate(date("Y-m-d", strtotime($_POST['date'])));
            $inv->setDeliveryDate(date("Y-m-d", strtotime($_POST['deliverydate'])));
            $inv->setClient($_POST['client'] ?? 0);
            $inv->setDueDate(date("Y-m-d", strtotime($_POST['duedate'])));
            $inv->setReminders(isset($_POST['no_reminders']) && $_POST['no_reminders'] == "yes" ? false : true);
            $inv->setPaidAmount($nfo->phpize($_POST['paid_amount']));
            $inv->setLateFees($nfo->phpize($_POST['latefee']));
            $inv->setClientData(serialize($cd));
            if ($nfo->phpize($_POST['paid_amount']) >= $inv->getAmount() + $inv->getLateFees()) {
                $inv->setStatus(1);
            }

            if (!empty($_POST['customno'])) {
                $inv->setCustomNo($_POST['customno']);
            }

            foreach ($items as $item) {
                $inv->addItem($item);
                $item->save();
            }
            $inv->save();

            alog("einvoice", "changed", $_GET['id']);
            $var['success'] = $l['SUC1'] . ($nfo->phpize($_POST['paid_amount']) >= $inv->getAmount() + $inv->getLateFees() ? " " . $l['SUC2'] : "");
            unset($_POST);
            $inv->load($_GET['id']);
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    array_push($var['customJSFiles'], "invoice");
    $tpl = "einvoice";

    $var['inv'] = $inv;
    $var['ii'] = unserialize($inv->getClientData());
    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['date'] = $dfo->format(strtotime($inv->getDate()), false);
    $var['deliverydate'] = $dfo->format(strtotime($inv->getDeliveryDate()), false);
    $var['duedate'] = $dfo->format(strtotime($inv->getDueDate()), false);
    $var['positions'] = isset($_POST['invoiceitem_description']) && is_array($_POST['invoiceitem_description']) ? count($_POST['invoiceitem_description']) : count($inv->getItems());
    $var['noReminders'] = !$inv->getReminders();
    $var['languages'] = $GLOBALS['languages'];

    $var['pd'] = array();
    if (isset($_POST['invoiceitem_description']) && is_array($_POST['invoiceitem_description'])) {
        $var['pd'] = $_POST['invoiceitem_description'];
    } else {
        $i = 1;
        foreach ($inv->getItems() as $item) {
            $var['pd'][$i] = $item->getDescription();
            $i++;
        }
    }

    $var['pa'] = $var['pt'] = $var['pu'] = $var['pq'] = array();
    if (isset($_POST['invoiceitem_amount']) && is_array($_POST['invoiceitem_amount'])) {
        $var['pa'] = $_POST['invoiceitem_amount'];
        $var['pt'] = $_POST['invoiceitem_tax'] ?? array();
        $var['pu'] = $_POST['invoiceitem_unit'] ?? array();
        $var['pq'] = $_POST['invoiceitem_qty'] ?? array();
    } else {
        $i = 1;
        foreach ($inv->getItems() as $item) {
            $var['pa'][$i] = $nfo->format($item->getAmount());
            if ($item->getTax()) {
                $var['pt'][$i] = true;
            }

            $var['pu'][$i] = $item->getUnit();
            $var['pq'][$i] = $nfo->format(floatval($item->getQty()));

            $i++;
        }
    }

    $var['currencies'] = $currencies;
    alog("einvoice", "viewed", $_GET['id']);

    $var['edithint'] = intval($inv->getStatus()) !== 3 && !empty($inv->getClientData());
} else {
    alog("general", "insufficient_page_rights", "einvoice");
    $tpl = "error";
}
