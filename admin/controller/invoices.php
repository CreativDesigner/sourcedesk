<?php
global $ari, $lang, $CFG;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(13)) {
    alog("general", "insufficient_page_rights", "invoices");
    $tpl = "error";
} else if ((!isset($_GET['id']) || !is_numeric($_GET['id'])) && (!isset($_POST['invoices']) || !is_array($_POST['invoices']) || count($_POST['invoices']) == 0)) {
    $tpl = "error";
} else {
    $inv = new Invoice;
    $d = 0;
    if (!isset($_POST['invoices'])) {
        $_POST['invoices'] = array($_GET['id']);
    }

    asort($_POST['invoices']);
    foreach ($_POST['invoices'] as $id) {
        if (!$inv->load($id)) {
            continue;
        }

        if ($inv->getClient() != "0") {
            if (!($uI instanceof User) && !($uI = User::getInstance($inv->getClient(), "ID"))) {
                continue;
            }

            require __DIR__ . "/../../languages/" . basename($uI->getLanguage()) . ".php";
        } else {
            $il = $CFG['LANG'];
            $cd = unserialize($inv->getClientData());
            $cl = isset($cd['language']) ? $cd['language'] : $il;
            if (file_exists(__DIR__ . "/../../languages/" . basename($cl) . ".php")) {
                $il = $cl;
            }

            require __DIR__ . "/../../languages/" . basename($il) . ".php";
        }

        if (!isset($pdf)) {
            $pdf = new PDFInvoice;
        }

        if (!$pdf->add($inv)) {
            continue;
        }

        alog("invoice", $output, $id);
        $d++;
    }

    if ($d == 0) {
        $tpl = "error";
    } else {
        if ($d == 1) {
            $pdf->output($inv->getInvoiceNo(), "I");
        } else {
            $pdf->output("Rechnungen-" . $inv->getClient(), "I");
        }

    }
}
