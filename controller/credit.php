<?php
// Global a few variables for security reasons
global $var, $db, $user, $CFG, $nfo, $lang, $transactions, $dfo, $nfo, $cur, $gateways, $title, $tpl, $pars;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Insert necessary payment JS code
$gateways->insertJavaScript();

// Make payments
$gateways->makePayment();

// Make an credit transfer if it is requested
if (isset($_POST['action']) && $_POST['action'] == "transfer_credit") {
    if (!$var['logged_in']) {
        die("failed");
    }

    if (!isset($_POST['amount']) || !is_numeric($nfo->phpize($_POST['amount']))) {
        die("amount");
    }

    if ($cur->convertBack($nfo->phpize($_POST['amount'])) > $user->get()['credit'] || $cur->convertBack($nfo->phpize($_POST['amount'])) <= 0) {
        die("amount");
    }

    $amount = $cur->convertBack($nfo->phpize($_POST['amount']));
    $recipient = isset($_POST['recipient']) ? $_POST['recipient'] : false;
    if (!$recipient) {
        die("recipient");
    }

    $sql = $db->query("SELECT `ID` FROM clients WHERE mail = '" . $db->real_escape_string($recipient) . "' AND ID != " . $user->get()['ID'] . " AND locked = 0");
    if ($sql->num_rows != 1) {
        die("recipient");
    }

    $recipientId = $sql->fetch_object()->ID;

    // Do transfer
    $db->query("UPDATE clients SET credit = credit - $amount WHERE ID = " . $user->get()['ID'] . " LIMIT 1");
    $db->query("UPDATE clients SET credit = credit + $amount WHERE ID = $recipientId LIMIT 1");
    $transactions->insert('credit_transfer_out', $recipientId, $amount / -1);
    $transactions->insert('credit_transfer_in', $user->get()['ID'], $amount, $recipientId);

    die("ok");
}

User::status();

if (isset($pars[0]) && $pars[0] == "receipt" && isset($pars[1]) && is_numeric($pars[1]) && $db->query("SELECT 1 FROM client_transactions WHERE ID = " . intval($pars[1]) . " AND user = " . intval($user->get()['ID']) . " AND deposit = 1")->num_rows == 1) {
    $r = new PDFReceipt($db->query("SELECT * FROM client_transactions WHERE ID = " . intval($pars[1]) . " AND user = " . intval($user->get()['ID']) . " AND deposit = 1")->fetch_object(), $user->get());
    $r->output();
    exit;
} else if (isset($pars[0]) && $pars[0] == "pay" && isset($pars[1]) && isset($gateways->getActivated($user->get()['ID'])[$pars[1]]) && $gateways->get()[$pars[1]]->havePaymentHandler() && $gateways->get()[$pars[1]]->isActive()) {
    $gateways->get()[$pars[1]]->getPaymentHandler();
} else {
    $title = $lang['CREDIT']['TITLE'];
    $tpl = "credit";

    // Get the payment gateways
    $var['gateways'] = $gateways->getActivated($user->get()['ID']);
    $var['global'] = $gateways->getGlobalNotification();

    // Parse amount
    $amount = null;
    if (isset($pars[0]) && $pars[0] == "amount" && isset($pars[1]) && (is_double($pars[1]) || is_numeric($pars[1])) && $pars[1] >= 0) {
        $amount = $cur->convertAmount($cur->getBaseCurrency(), $pars[1]);
    }

    $var['amount'] = $nfo->format($amount);

    // Select all transactions the customer have done and iterate through it
    $transArr = $transactions->get(array("user" => $user->get()['ID']), 0, "time", 1, $CFG['LANG']);
    $trans = array();

    $user = new User($user->get()['ID'], "ID");
    $var['user'] = $user->get();
    $saldo = $user->get()['credit'];
    foreach ($transArr as $row) {
        $row = (object) $row;

        $af = $sf = "";
        if ($row->amount < 0) {
            $af = '<font color="' . ($row->waiting ? "orange" : "red") . '">- ' . $cur->infix($nfo->format($cur->convertAmount(null, $row->amount / -1, null), 2, 0)) . '</font>';
        } else if ($row->amount > 0) {
            $af = '<font color="' . ($row->waiting ? "orange" : "green") . '">+ ' . $cur->infix($nfo->format($cur->convertAmount(null, $row->amount, null), 2, 0)) . '</font>';
        } else {
            $af = $cur->infix($nfo->format(0));
        }

        if ($saldo < 0) {
            $sf = '<font color="red">- ' . $cur->infix($nfo->format($cur->convertAmount(null, $saldo / -1, null), 2, 0)) . '</font>';
        } else if ($saldo > 0) {
            $sf = '<font color="green">+ ' . $cur->infix($nfo->format($cur->convertAmount(null, $saldo, null), 2, 0)) . '</font>';
        } else {
            $sf = $cur->infix($nfo->format(0));
        }

        $trans[] = array("ID" => $row->ID, "time" => $dfo->format($row->time), "subject" => $row->subject, "amount" => $row->amount, "saldo" => $saldo, "amount_f" => $af, "saldo_f" => $sf, "cashbox" => $row->cashbox_subject, "deposit" => $row->deposit, "waiting" => $row->waiting);
        if ($row->waiting != 1) {
            $saldo -= $row->amount;
        }

    }

    // Pass the array to template engine
    $var['trans'] = $trans;

    if (isset($pars[0]) && $pars[0] == "cashbox" && isset($pars[1]) && $pars[1] == "1" && $user->get()['cashbox_active'] == "0") {
        $user->set(array('cashbox_active' => "1"));
    }

    if (isset($pars[0]) && $pars[0] == "cashbox" && isset($pars[1]) && $pars[1] == "0" && $user->get()['cashbox_active'] == "1") {
        $user->set(array('cashbox_active' => "0"));
    }

    // Get the cashbox link
    if ($CFG['CASHBOX_ACTIVE']) {
        if ($user->get()['cashbox_active'] == "2") {
            $var['cashbox'] = "locked";
        } else if ($user->get()['cashbox_active'] == "1") {
            $var['cashbox'] = $CFG['PAGEURL'] . "cashbox/" . $user->get()['ID'] . "/" . substr(hash("sha512", $user->get()['ID'] . $CFG['HASH']), 0, 10);
        } else {
            $var['cashbox'] = "inactive";
        }

    }
}

$var['currencyObj'] = $cur->getCurrent();
$var['user_credit_f'] = $nfo->format($cur->convertAmount(null, $user->get()['credit'], null));
$var['credit_f'] = $cur->infix($var['user_credit_f']);
$var['special_f'] = $cur->infix($nfo->format($cur->convertAmount(null, $user->get()['special_credit'], null)));
$var['normal_f'] = $cur->infix($nfo->format($cur->convertAmount(null, $user->get()['credit'] - $user->get()['special_credit'], null)));
$var['additionalJS'] = "$('[data-toggle=\"popover\"]').popover();";
$var['cart_link'] = '<a href="' . $CFG['PAGEURL'] . "cart/payment" . '">';
$var['automated_payments'] = $user->autoPaymentStatus();
$var['automated_gateways'] = $gateways->get(true);

foreach ($var['automated_gateways'] as $k => $obj) {
    if (!$obj->isActive() || !$obj->canPay($user)) {
        unset($var['automated_gateways'][$k]);
    }
}

$var['apcode'] = "";
if ($var['automated_payments'] && array_key_exists($user->get()['auto_payment_provider'], $var['automated_gateways'])) {
    $var['apcode'] = $var['automated_gateways'][$user->get()['auto_payment_provider']]->setupAutoPayment($user);
} else if (!empty($_REQUEST['automated_gateway']) && array_key_exists($_REQUEST['automated_gateway'], $var['automated_gateways'])) {
    $var['apcode'] = $var['automated_gateways'][$_REQUEST['automated_gateway']]->setupAutoPayment($user);
}
