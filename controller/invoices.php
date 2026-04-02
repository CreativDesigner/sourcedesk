<?php
// Global variables for security reasons
global $var, $lang, $user, $pars, $cur, $nfo, $db, $CFG, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['INVOICES']['TITLE'];
$tpl = "invoices";
$inv = new Invoice;

if (isset($pars[0]) && is_numeric($pars[0]) && $inv->load($pars[0]) && $inv->getClient() == $user->get()['ID'] && $inv->getStatus() != "3") {
    $pdf = new PDFInvoice;
    $pdf->add($inv);
    $pdf->output($inv->getInvoiceNo(), "I");
} else if (isset($pars[0]) && $pars[0] == "pay" && isset($pars[1]) && is_numeric($pars[1]) && $inv->load($pars[1]) && $inv->getClient() == $user->get()['ID'] && $inv->getStatus() == 0 && $inv->getStatus() != "3") {
    $credit = round($user->get()['credit'], 2);
    $amount = round($inv->getAmount(), 2);
    if ($amount <= $credit || $amount <= 0) {
        $inv->applyCredit();
        $var['suc'] = $lang['INVOICES']['PAID2'];
    } else {
        header('Location: ' . $CFG['PAGEURL'] . 'credit/amount/' . ($amount - $credit));
        exit;
    }
} else if (isset($pars[0]) && $pars[0] == "accept" && isset($pars[1]) && is_numeric($pars[1]) && $db->query("SELECT * FROM client_quotes WHERE client = " . $user->get()['ID'] . " AND status = 0 AND valid >= '" . date("Y-m-d") . "' AND ID = " . intval($pars[1]))->num_rows) {
    $user->log("Angebot #{$pars[1]} angenommen");

    $pdf = new PDFQuote($pars[1]);
    $items = $pdf->getItems();

    $stages = [];
    $stageSql = $db->query("SELECT * FROM client_quote_stages WHERE quote = " . intval($pars[1]) . " ORDER BY days ASC");

    if ($stageSql->num_rows == 0) {
        $stages = [
            [$CFG['INVOICE_DUEDATE'], 100],
        ];
    } else {
        while ($stageRow = $stageSql->fetch_object()) {
            $stages[] = [$stageRow->days, $stageRow->percent];
        }
    }

    foreach ($stages as $stage) {
        $inv = new Invoice;
        $inv->setClient($user->get()['ID']);
        $inv->setDate(date("Y-m-d"));
        $inv->setDueDate(date("Y-m-d", strtotime("+" . $stage[0] . " days")));

        foreach ($items as $i) {
            if (!$pdf->getVat()) {
                $i[2] = $user->addTax($i[2]);
            }

            $item = new InvoiceItem;
            $item->setDescription($i[0]);
            $item->setAmount(round($i[2] * $stage[1] / 100, 2));
            $item->save();
            $inv->addItem($item);
        }

        $inv->save();
    }

    $db->query("UPDATE client_quotes SET status = 2 WHERE ID = " . intval($pars[1]));

    $var['suc'] = $lang['INVOICES']['CONFIRMED'];
} else if (isset($pars[0]) && $pars[0] == "quote" && isset($pars[1]) && is_numeric($pars[1]) && $db->query("SELECT * FROM client_quotes WHERE client = " . $user->get()['ID'] . " AND status = 0 AND valid >= '" . date("Y-m-d") . "' AND ID = " . intval($pars[1]))->num_rows) {
    $user->log("Angebot #{$pars[1]} heruntergeladen");
    $pdf = new PDFQuote($pars[1]);
    $pdf->output("", true);
    exit;
} else if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
    if (isset($_POST['send'])) {
        $d = 0;
        foreach ($_POST['invoices'] as $id) {
            if (!$inv->load($id)) {
                continue;
            }

            if ($inv->getClient() != $user->get()['ID'] || $inv->getStatus() == "3") {
                continue;
            }

            $inv->send("send");
            $d++;
        }

        if ($d == 1) {
            $var['suc'] = $lang['INVOICES']['SENT'];
        } else if ($d > 0) {
            $var['suc'] = str_replace("%x", $d, $lang['INVOICES']['SENT_X']);
        }

    } else if (isset($_POST['download'])) {
        $pdf = new PDFInvoice;
        $d = 0;
        foreach ($_POST['invoices'] as $id) {
            if (!$inv->load($id)) {
                continue;
            }

            if ($inv->getClient() != $user->get()['ID'] || $inv->getStatus() == "3") {
                continue;
            }

            $pdf->add($inv);
            $d++;
        }
        if ($d == 1) {
            $pdf->output($inv->getInvoiceNo());
        } else if ($d > 0) {
            $pdf->output($lang['INVOICES']['TITLE']);
        }

    } else if (isset($_POST['pay'])) {
        $amount = 0;
        $invoices = array();

        $d = 0;
        foreach ($_POST['invoices'] as $id) {
            if (!$inv->load($id)) {
                continue;
            }

            if ($inv->getClient() != $user->get()['ID'] || $inv->getStatus() == "3") {
                continue;
            }

            if ($inv->getStatus() != 0) {
                continue;
            }

            $amount += round($inv->getAmount(), 2);
            array_push($invoices, $inv->getId());
        }

        $credit = round($user->get()['credit'], 2);

        if ($amount > $credit && $amount > 0) {
            header('Location: ' . $CFG['PAGEURL'] . 'credit/amount/' . ($amount - $credit));
            exit;
        } else {
            foreach ($invoices as $id) {
                $inv->load($id);
                $inv->applyCredit();
                $d++;
            }

            if ($d == 1) {
                $var['suc'] = $lang['INVOICES']['PAID2'];
            } else if ($d > 0) {
                $var['suc'] = str_replace("%x", $d, $lang['INVOICES']['PAID_X']);
            }

        }
    }
}

$var['invoices'] = $user->getInvoices();

$var['waiting_amount'] = $db->query("SELECT SUM(amount) AS s FROM invoicelater WHERE user = " . $user->get()['ID'])->fetch_object()->s;
$var['waiting_amount_f'] = $cur->infix($nfo->format($var['waiting_amount']));
$var['waiting_amount_d'] = $dfo->format(strtotime($user->invoiceDue()), false);

$user = new User($user->get()['ID'], "ID");
$var['user'] = $user->get();

$var['quotes'] = [];
$sql = $db->query("SELECT * FROM client_quotes WHERE client = " . $user->get()['ID'] . " AND status = 0 AND valid >= '" . date("Y-m-d") . "' ORDER BY ID DESC");
while ($row = $sql->fetch_object()) {
    $pdf = new PDFQuote($row->ID);
    $sum = $pdf->getSum();

    $prefix = $CFG['OFFER_PREFIX'];
    $date = strtotime($row->date);
    $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
    $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
    $prefix = str_replace("{DAY}", date("d", $date), $prefix);

    $var['quotes'][] = [
        $row->ID,
        $prefix . str_pad($row->ID, $CFG['MIN_QUOLEN'], "0", STR_PAD_LEFT),
        $dfo->format($row->date, "", false, false),
        $dfo->format($row->valid, "", false, false),
        $cur->infix($nfo->format($cur->convertAmount(null, $sum))),
    ];
}
