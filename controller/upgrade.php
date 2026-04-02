<?php
global $lang, $pars, $db, $CFG, $user, $cur, $nfo, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$l = $lang['UPGRADE'];

if (!isset($pars[0]) || !is_numeric($pars[0]) || !is_object($sql = $db->query("SELECT * FROM client_products WHERE ID = " . intval($pars[0]) . " AND type = 'h' AND active = 1 AND user = " . $user->get()['ID'])) || $sql->num_rows != 1) {
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
} else {
    $title = $l['TITLE'];
    $tpl = "upgrade";

    $info = $sql->fetch_object();
    $pSql = $db->query("SELECT * FROM products WHERE ID = {$info->product}");
    if ($pSql->num_rows != 1) {
        $title = $lang['ERROR']['TITLE'];
        $tpl = "error";
        return;
    }
    $pInfo = $pSql->fetch_object();
    if (!empty($info->name)) {
        $pInfo->name = serialize([$CFG['LANG'] => $info->name]);
    }

    $onetime = $var['onetime'] = empty($info->billing) || $info->billing == "onetime";

    $cfVal = @unserialize($info->cf);
    if (!is_array($cfVal)) {
        $cfVal = [];
    }

    $additionalCosts = 0;
    $additionalCostsRecurring = 0;
    $changes = [];

    $var['changes'] = [];

    $sql = $db->query("SELECT * FROM products_cf WHERE product = " . intval($info->product));
    while ($row = $sql->fetch_assoc()) {
        $o = unserialize($row['options']);
        unset($row['options']);
        foreach ($o as $k => $v) {
            $row[$k] = $v;
        }

        switch ($row['type']) {
            case "number":
                if (array_key_exists($row['ID'], $cfVal)) {
                    $row['default'] = $cfVal[$row['ID']];
                }

                if (isset($_POST['cf'][$row['ID']]) && $_POST['cf'][$row['ID']] > $row['maximum']) {
                    $_POST['cf'][$row['ID']] = $row['maximum'];
                }

                if (isset($_POST['cf'][$row['ID']]) && $_POST['cf'][$row['ID']] < $row['minimum']) {
                    $_POST['cf'][$row['ID']] = $row['minimum'];
                }

                if (isset($_POST['cf'][$row['ID']]) && $_POST['cf'][$row['ID']] > $cfVal[$row['ID']]) {
                    $changes[$row['ID']] = $_POST['cf'][$row['ID']];
                    $var['changes'][$row['name']] = $_POST['cf'][$row['ID']];
                    $costs = ($changes[$row['ID']] - $row['default']) * $row['amount'];

                    $additionalCosts += $costs;

                    if (!array_key_exists("onetime", $row) || !$row['onetime']) {
                        $additionalCostsRecurring += $costs;
                    }
                }
                break;

            case "select":
            case "radio":
                $ex = explode("|", $row['values']);

                $row['defcost'] = array_shift(explode("|", $row['costs']));

                if (array_key_exists($row['ID'], $cfVal)) {
                    $index = array_search($cfVal[$row['ID']], $ex) ?: 0;
                    $row['defcost'] = explode("|", $row['costs'])[$index];
                }

                if (isset($_POST['cf'][$row['ID']])) {
                    $index = array_search($_POST['cf'][$row['ID']], $ex) ?: 0;
                    $costs = explode("|", $row['costs'])[$index] - $row['defcost'];

                    if ($costs > 0) {
                        $changes[$row['ID']] = $_POST['cf'][$row['ID']];
                        $var['changes'][$row['name']] = $_POST['cf'][$row['ID']];

                        $additionalCosts += $costs;

                        if (!array_key_exists("onetime", $row) || !$row['onetime']) {
                            $additionalCostsRecurring += $costs;
                        }
                    }
                }
                break;

            case "check":
                if (array_key_exists($row['ID'], $cfVal) && $cfVal[$row['ID']]) {
                    continue;
                }

                if (!empty($_POST['cf'][$row['ID']])) {
                    $costs = $row['costs'];

                    $changes[$row['ID']] = "1";
                    $var['changes'][$row['name']] = $l['YES'];

                    $additionalCosts += $costs;

                    if (!array_key_exists("onetime", $row) || !$row['onetime']) {
                        $additionalCostsRecurring += $costs;
                    }
                }
                break;
        }
    }

    if ($additionalCosts <= 0) {
        $title = $lang['ERROR']['TITLE'];
        $tpl = "error";
        $var['error'] = $l['NOUP'];
        return;
    }

    $var['addcost'] = $cur->convertAmount($cur->getBaseCurrency(), $additionalCosts); // Onetime costs
    $var['addcost_recur'] = $cur->convertAmount($cur->getBaseCurrency(), $additionalCostsRecurring); // Recurring costs

    $dueNow = $additionalCosts;

    $billingDays = [
        "monthly" => "30",
        "quarterly" => "90",
        "semiannually" => "180",
        "annually" => "360",
        "biennially" => "720",
        "trinnially" => "1080",
    ];

    if (!empty($info->billing) && array_key_exists($info->billing, $billingDays)) {
        $perDay = $additionalCostsRecurring / $billingDays[$info->billing];
        $daysLeft = ceil((strtotime($info->last_billed) - time()) / 60 / 60 / 24);
        $toPay = round($perDay * $daysLeft, 2);

        $dueNow += $toPay;
    }

    $var['due_now'] = $cur->convertAmount($cur->getBaseCurrency(), $dueNow); // Amount due now

    $var['can_pay'] = $user->getLimit() >= $dueNow;

    if (!empty($_POST['apply']) && $var['can_pay']) {
        $inv = new Invoice;
        $inv->setClient($user->get()['ID']);
        $inv->setDate(date("Y-m-d"));
        $inv->setDueDate(date("Y-m-d"));
        $inv->setStatus(0);

        $item = new InvoiceItem;
        $item->setDescription($l['TITLE'] . " #" . $info->ID);
        $item->setAmount($dueNow);
        $item->setRelid($info->ID);
        $inv->addItem($item);

        $inv->applyCredit();

        $cf = $cfVal;
        foreach ($changes as $k => $v) {
            $cf[$k] = $v;
        }

        $additionalCostsRecurring = doubleval($additionalCostsRecurring);
        $cf = $db->real_escape_string(serialize($cf));
        $db->query("UPDATE client_products SET price = price + $additionalCostsRecurring, cf = '$cf' WHERE ID = " . $info->ID);

        $modules = $provisioning->get();
        if (array_key_exists($info->module, $modules)) {
            $modules[$info->module]->ChangePackage($info->ID);
        }
        
        $var['suc'] = true;
    }
}