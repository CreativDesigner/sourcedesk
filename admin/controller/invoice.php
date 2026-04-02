<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['INVOICE_EDIT'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($l['SUBT']);
menu("payments");

$inv = new Invoice;

if ($ari->check(13) && isset($_GET['id']) && $inv->load($_GET['id'])) {
    if (isset($_POST['invoiceitem_description'])) {
        try {
            if (empty($_POST['client']) || $db->query("SELECT ID FROM clients WHERE ID = " . intval($_POST['client']))->num_rows != 1) {
                throw new Exception($l['ERR1']);
            }

            if (empty($_POST['date']) || strtotime($_POST['date']) === false) {
                throw new Exception($l['ERR2']);
            }

            if (empty($_POST['deliverydate']) || strtotime($_POST['deliverydate']) === false) {
                throw new Exception($l['ERR3']);
            }

            if (empty($_POST['duedate']) || strtotime($_POST['duedate']) === false) {
                throw new Exception($l['ERR4']);
            }

            if (strtotime($_POST['date']) > strtotime($_POST['duedate'])) {
                throw new Exception($l['ERR5']);
            }

            if (!is_array($_POST['invoiceitem_description']) || !is_array($_POST['invoiceitem_amount']) || count($_POST['invoiceitem_description']) != count($_POST['invoiceitem_amount'])) {
                throw new Exception($l['ERR6']);
            }

            $oldItems = $inv->getItems();
            foreach ($_POST['invoiceitem_description'] as $k => $d) {
                $a = $_POST['invoiceitem_amount'][$k];

                if (empty($d)) {
                    throw new Exception($l['ERR7']);
                }

                if (!isset($a) || (doubleval($nfo->phpize($a)) != $nfo->phpize($a) && intval($nfo->phpize($a)) != $nfo->phpize($a))) {
                    throw new Exception($l['ERR8']);
                }

            }

            $items = array();
            foreach ($_POST['invoiceitem_description'] as $k => $d) {
                $a = $nfo->phpize($_POST['invoiceitem_amount'][$k]);

                if (isset($_POST['net']) && $_POST['net'] == "yes" && isset($_POST['invoiceitem_tax'][$k]) && $_POST['invoiceitem_tax'][$k] == "1") {
                    if (!isset($uI)) {
                        $uI = new User($_POST['client'], "ID");
                    }

                    $a = $uI->addTax($a);
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
            $inv->setClient($_POST['client']);
            $inv->setDueDate(date("Y-m-d", strtotime($_POST['duedate'])));
            $inv->setReminders(isset($_POST['no_reminders']) && $_POST['no_reminders'] == "yes" ? false : true);
            foreach ($items as $item) {
                $inv->addItem($item);
                $item->save();
            }
            $inv->save();

            alog("invoice", "saved", $_GET['id']);
            $var['success'] = $l['SUC'];
            unset($_POST);
            $inv->load($_GET['id']);
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    array_push($var['customJSFiles'], "invoice");
    $tpl = "invoice";

    $var['inv'] = $inv;
    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['date'] = $dfo->format(strtotime($inv->getDate()), false);
    $var['deliverydate'] = $dfo->format(strtotime($inv->getDeliveryDate()), false);
    $var['duedate'] = $dfo->format(strtotime($inv->getDueDate()), false);
    $var['positions'] = isset($_POST['invoiceitem_description']) && is_array($_POST['invoiceitem_description']) ? count($_POST['invoiceitem_description']) : count($inv->getItems());
    $var['noReminders'] = !$inv->getReminders();

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

    $path = __DIR__ . "/../../files/invoices/" . $inv->getId();

    if (!empty($_GET['delete_file'])) {
        @unlink($path . "/" . basename($_GET['delete_file']));
        header("Location: ?p=invoice&id=" . $inv->getId());
        exit;
    }

    if (!empty($_GET['download_file']) && file_exists($path . "/" . basename($_GET['download_file']))) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($_GET['download_file']) . "\"");
        readfile($path . "/" . basename($_GET['download_file']));
        exit;
    }

    if (!empty($_FILES['upload_files'])) {
        if (!file_exists($path)) {
            mkdir($path);
        }

        foreach ($_FILES["upload_files"]["name"] as $k => $name) {
            $tmp_name = $_FILES["upload_files"]["tmp_name"][$k];
            move_uploaded_file($tmp_name, $path . "/" . basename($name));
        }

        header("Location: ?p=invoice&id=" . $inv->getId());
        exit;
    }

    alog("invoice", "viewed", $_GET['id']);

    $var['files'] = [];
    foreach (glob($path . "/*") as $f) {
        array_push($var['files'], basename($f));
    }

    $var['ci'] = ci($inv->getClient());
    $var['products'] = [];

    $sql = $db->query("SELECT name, price, description FROM products");
    while ($row = $sql->fetch_object()) {
        array_push($var['products'], [
            unserialize($row->name)[$CFG['LANG']] . " - " . $nfo->format($row->price),
            unserialize($row->description)[$CFG['LANG']],
        ]);
    }

    $var['edithint'] = intval($inv->getStatus()) !== 3 && !empty($inv->getClientData());
} else {
    alog("general", "insufficient_page_rights", "invoice");
    $tpl = "error";
}
