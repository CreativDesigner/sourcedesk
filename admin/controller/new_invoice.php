<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['INVOICE_EDIT'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title("Rechnung erstellen");
menu("customers");

$uI = User::getInstance($_GET['user'], "ID");

if ($ari->check(13) && $uI) {
    if (isset($_POST['invoiceitem_description'])) {
        try {
            if (empty($_POST['date']) || strtotime($_POST['date']) === false) {
                throw new Exception($l['ERR2']);
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

            if (!isset($_POST['status']) || !in_array($_POST['status'], ["0", "1", "3"])) {
                throw new Exception($l['ERR9']);
            }

            $items = array();
            foreach ($_POST['invoiceitem_description'] as $k => $d) {
                $a = $nfo->phpize($_POST['invoiceitem_amount'][$k]);

                if (empty($d)) {
                    throw new Exception($l['ERR7']);
                }

                if (!isset($a) || (doubleval(($a)) != ($a) && intval(($a)) != ($a))) {
                    throw new Exception($l['ERR8']);
                }

                if (isset($_POST['net']) && $_POST['net'] == "yes" && isset($_POST['invoiceitem_tax'][$k]) && $_POST['invoiceitem_tax'][$k] == "1") {
                    $a = $uI->addTax($a);
                }

                $item = new InvoiceItem;
                $item->setDescription($d);
                $item->setAmount($a);
                $item->setTax(isset($_POST['invoiceitem_tax'][$k]) && $_POST['invoiceitem_tax'][$k] == "1");
                $item->setQty(floatval($nfo->phpize($_POST['invoiceitem_qty'][$k] ?: 1)));
                $item->setUnit($_POST['invoiceitem_unit'][$k] ?: "x");
                array_push($items, $item);
            }

            $invoice = new Invoice;
            $invoice->setDate(date("Y-m-d", strtotime($_POST['date'])));
            $invoice->setClient($uI->get()['ID']);
            $invoice->setDueDate(date("Y-m-d", strtotime($_POST['duedate'])));
            $invoice->setStatus($_POST['status']);
            if (isset($_POST['no_reminders']) && $_POST['no_reminders'] == "yes") {
                $invoice->setReminders(false);
            }

            foreach ($items as $item) {
                $invoice->addItem($item);
            }

            if (isset($_POST['send_invoice']) && $_POST['send_invoice'] == "yes") {
                $invoice->send();
            }

            alog("invoice", "created", $invoice->getId());
            $invoice->applyCredit(true, true);
            $var['success'] = $l['ICREA'];

            if (isset($_POST['projctrl'])) {
                $ex = explode("#", $_POST['projctrl']);
                $pid = intval($_GET['project']);

                foreach ($ex as $line) {
                    $c = explode("|", $line);

                    if ($c[0] == "0") {
                        $db->query("UPDATE projects SET entgelt_done = 1 WHERE ID = $pid AND entgelt_type = 0 LIMIT 1");
                    } else if ($c[0] == strval($pid / -1)) {
                        $time = intval($c[1]);
                        $db->query("UPDATE projects SET entgelt_done = entgelt_done + $time WHERE ID = $pid AND entgelt_type = 1 LIMIT 1");
                    } else {
                        $tid = intval($c[0]);
                        $time = intval($c[1]);

                        if ($time < 0) {
                            $db->query("UPDATE project_tasks SET entgelt_done = 1 WHERE ID = $tid AND entgelt_type = 0 AND project = $pid LIMIT 1");
                        } else {
                            $db->query("UPDATE project_tasks SET entgelt_done = entgelt_done + $time WHERE ID = $tid AND entgelt_type = 1 AND project = $pid LIMIT 1");
                        }
                    }
                }
            }

            unset($_POST);
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    if (isset($_GET['amount']) && !isset($_POST['invoiceitem_amount'])) {
        $_POST['invoiceitem_amount'] = array("1" => $_GET['amount']);
        $var['byUrl'] = 1;
    }
    if (isset($_GET['title']) && !isset($_POST['invoiceitem_description'])) {
        $_POST['invoiceitem_description'] = array("1" => $_GET['title']);
        $var['byUrl'] = 1;
    }

    $var['projctrl'] = "";
    if (isset($_GET['project']) && is_array($arr = Project::invoice($_GET['project'])) && count($arr)) {
        $_POST['invoiceitem_description'] = [];

        $i = 1;
        foreach ($arr as $k => $v) {
            $var['projctrl'] .= "$k|{$v[2]}#";
            $_POST['invoiceitem_description'][$i] = $v[0];
            $_POST['invoiceitem_amount'][$i] = $nfo->format($v[1]);
            $i++;
        }

        $var['projctrl'] = rtrim($var['projctrl'], "#");
    }

    array_push($var['customJSFiles'], "invoice");
    $tpl = "new_invoice";

    $default = $CFG['INVOICE_DUEDATE'];
    if ($uI && $uI->get()['inv_due'] >= 0) {
        $default = $uI->get()['inv_due'];
    }

    $var['duedate'] = $dfo->format(strtotime("+$default days"), false);
    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['user'] = $uI->get();
    $var['positions'] = isset($_POST['invoiceitem_description']) && is_array($_POST['invoiceitem_description']) ? count($_POST['invoiceitem_description']) : 1;
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
