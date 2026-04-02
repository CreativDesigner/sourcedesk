<?php
// Global some variables for security reasons
global $db, $user, $CFG, $nfo, $var, $lang, $dfo, $cur, $pars, $provisioning, $maq, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$im = $user->impersonate("products");
array_push($im, $user->get()['ID']);
$im = array_unique($im);

$userIds = implode(",", $im);

if (!isset($pars[0]) || !is_numeric($pars[0]) || !is_object($sql = $db->query("SELECT * FROM client_products WHERE ID = " . intval($pars[0]) . " AND type = 'h' AND active = 1 AND user IN ($userIds)")) || $sql->num_rows != 1) {
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
} else {
    $title = $lang['HOSTING']['TITLE'];
    $tpl = "hosting";

    $var['runtime'] = $var['minruntime'] = $var['notper'] = "";

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

    if ($info->ct) {
        $var['runtime'] = format_ctime($info->ct);
    }

    if ($info->mct) {
        $var['minruntime'] = format_ctime($info->mct);
    }

    if ($info->np) {
        $var['notper'] = format_ctime($info->np);
    }

    $var['price'] = $cur->infix($nfo->format($cur->convertAmount(null, $info->price)));

    $onetime = $var['onetime'] = empty($info->billing) || $info->billing == "onetime";

    $var['nextinv'] = "";
    if (!$onetime && $info->last_billed && $info->last_billed != "0000-00-00") {
        $var['nextinv'] = $dfo->format($info->last_billed, false, false, false);
    }

    $cfVal = @unserialize($info->cf);
    if (!is_array($cfVal)) {
        $cfVal = [];
    }

    $var['cf'] = [];

    $sql = $db->query("SELECT * FROM products_cf WHERE product = " . intval($info->product));
    while ($row = $sql->fetch_assoc()) {
        $o = unserialize($row['options']);
        unset($row['options']);
        foreach ($o as $k => $v) {
            $row[$k] = $v;
        }

        switch ($row['type']) {
            case "text":
                if (array_key_exists($row['ID'], $cfVal)) {
                    $row['default'] = $cfVal[$row['ID']];
                }
                break;

            case "number":
                if (array_key_exists($row['ID'], $cfVal)) {
                    $row['default'] = intval($cfVal[$row['ID']]);
                }

                $row['defcost'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $row['default'] * $row['amount'])));

                if (isset($_POST['field_id']) && $_POST['field_id'] == $row['ID']) {
                    $onetime = "";
                    if (array_key_exists("onetime", $row) && $row['onetime']) {
                        $onetime = " " . $lang['CONFIGURE']['ONETIME_ONLY'];
                    }

                    die($cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $_POST['field_val'] * $row['amount']))) . $onetime);
                }
                break;

            case "select":
            case "radio":
                $onetime = "";
                if (array_key_exists("onetime", $row) && $row['onetime']) {
                    $onetime = " " . $lang['CONFIGURE']['ONETIME_ONLY'];
                }

                $ex = explode("|", $row['values']);

                $row['defcost'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), array_shift(explode("|", $row['costs'])))));

                if (array_key_exists($row['ID'], $cfVal)) {
                    $index = array_search($cfVal[$row['ID']], $ex) ?: 0;
                    $row['defcost'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), explode("|", $row['costs'])[$index]))) . $onetime;
                }

                if (isset($_POST['field_id']) && $_POST['field_id'] == $row['ID']) {
                    $index = array_search($_POST['field_val'], $ex) ?: 0;

                    die($cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), explode("|", $row['costs'])[$index]))) . $onetime);
                }
                break;

            case "check":
                $row['defcost'] = $cur->infix($nfo->format(0));

                $onetime = "";
                if (array_key_exists("onetime", $row) && $row['onetime']) {
                    $onetime = " " . $lang['CONFIGURE']['ONETIME_ONLY'];
                }

                if (array_key_exists($row['ID'], $cfVal) && $cfVal[$row['ID']]) {
                    $row['defcost'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $row['costs']))) . $onetime;
                    $row['checked'] = true;
                }

                if (isset($_POST['field_id']) && $_POST['field_id'] == $row['ID']) {
                    die($cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $_POST['field_val'] ? $row['costs'] : 0))) . $onetime);
                }
                break;
        }

        if (array_key_exists("onetime", $row) && $row['onetime']) {
            $row['defcost'] .= " " . $lang['CONFIGURE']['ONETIME_ONLY'];
        }

        $var['cf'][$row['ID']] = $row;
    }

    $var['daysLeft'] = floor((strtotime($info->last_billed) - time()) / 60 / 60 / 24);
    $var['ppDays'] = [];

    $breakdown = [
        "monthly" => "30",
        "quarterly" => "90",
        "semiannually" => "180",
        "annually" => "360",
        "biennially" => "720",
        "trinnially" => "1080",
    ];

    if ($info->prepaid) {
        $ppDaySql = $db->query("SELECT ID, days, bonus FROM products_prepaid WHERE product = {$info->product}");
        while ($ppDay = $ppDaySql->fetch_object()) {
            $price = $info->price;

            if (!array_key_exists($info->billing, $breakdown)) {
                continue;
            }

            $price /= $breakdown[$info->billing];

            $var['ppDays'][$ppDay->ID] = [$ppDay->days, $cur->convertAmount($cur->getBaseCurrency(), round($price * $ppDay->days * (1 - $ppDay->bonus / 100), 2))];
        }
    }

    $ppCol = 12;
    $c = count($var['ppDays']);

    if ($c >= 4 && $c % 4 == 0) {
        $ppCol = 3;
    } else if ($c >= 3 && $c % 3 == 0) {
        $ppCol = 4;
    } else if ($c >= 2 && $c % 2 == 0) {
        $ppCol = 6;
    }

    $var['ppCol'] = $ppCol;

    $m = null;
    $modules = $provisioning->get();
    if (array_key_exists($info->module, $modules)) {
        $m = $modules[$info->module];
    }

    $billingDays = [
        "monthly" => "30",
        "quarterly" => "90",
        "semiannually" => "180",
        "annually" => "360",
        "biennially" => "720",
        "trinnially" => "1080",
    ];

    $bonusDays = 0;
    $liSql = $db->query("SELECT invoice FROM invoiceitems WHERE relid = " . $info->ID . " ORDER BY ID DESC LIMIT 1");
    if ($liSql->num_rows && array_key_exists($info->billing, $billingDays)) {
        $inv = new Invoice;
        if ($inv->load($liSql->fetch_object()->invoice)) {
            $date = strtotime($inv->getDate());
            $bonusDays = max(min(floor((time() - $date) / 86400), $billingDays[$info->billing]), 0);
        }
    }

    $switchingProducts = [];
    if ($info->billing != "onetime" && !empty($info->billing)) {
        $ex = explode(",", $pInfo->product_change);
        foreach ($ex as $id) {
            $id = intval($id);
            $spSql = $db->query("SELECT * FROM products WHERE type = 'HOSTING' AND ID = $id");
            if ($spSql->num_rows != 1) {
                continue;
            }

            $spInfo = $spSql->fetch_object();

            if ($spInfo->billing != $info->billing) {
                continue;
            }

            if (!array_key_exists($spInfo->billing, $billingDays)) {
                continue;
            }

            if ($spInfo->module != $info->module) {
                continue;
            }

            if (empty($m) || !method_exists($m, "ChangePackage")) {
                continue;
            }

            $spInfo->price = Product::getClientPrice($spInfo->price, $spInfo->tax);

            $priceDifference = $spInfo->price - $info->price;
            $perDay = $priceDifference / $billingDays[$spInfo->billing];
            $toPay = round($perDay * ($billingDays[$spInfo->billing] - $bonusDays), 2);

            $switchingProducts[$spInfo->ID] = [
                "toPay" => $toPay,
                "fToPay" => $cur->infix($nfo->format($cur->convertAmount(null, abs($toPay))), $cur->getBaseCurrency()),
                "price" => $spInfo->price,
                "fPrice" => $cur->infix($nfo->format($cur->convertAmount(null, $spInfo->price)), $cur->getBaseCurrency()),
                "name" => unserialize($spInfo->name)[$CFG['LANG']],
            ];
        }
    }

    $var['switchingProducts'] = $switchingProducts;

    if (isset($_POST['switch_product'])) {
        try {
            if (!array_key_exists($_POST['switch_product'], $switchingProducts)) {
                throw new Exception("SWITCH_ERR1");
            }

            $id = intval($_POST['switch_product']);
            $toPay = $switchingProducts[$id]["toPay"];
            $price = $switchingProducts[$id]["price"];

            if ($toPay != 0) {
                if ($toPay > 0 && $user->getLimit() < $toPay && !$user->autoPayment($toPay - $user->getLimit())) {
                    throw new Exception("SWITCH_ERR2");
                }

                $inv = new Invoice;
                $inv->setClient($user->get()['ID']);
                $inv->setDate(date("Y-m-d"));
                $inv->setDueDate(date("Y-m-d"));
                $inv->setStatus(0);

                $item = new InvoiceItem;
                $item->setDescription($lang['HOSTING']['SWITCH_INV'] . " #" . $info->ID);
                $item->setAmount($toPay);
                $inv->addItem($item);
                $inv->save();

                $inv->applyCredit();
            }

            $ms = [];

            $sql = $db->query("SELECT setting, value FROM product_provisioning WHERE module = '" . $db->real_escape_string($info->module) . "' AND pid = $id");
            while ($row = $sql->fetch_object()) {
                $ms[$row->setting] = decrypt($row->value);
            }

            $ms = $db->real_escape_string(encrypt(serialize($ms)));

            $db->query("UPDATE client_products SET price = " . $price . ", module_settings = '$ms', server_id = -1, product = $id WHERE ID = {$info->ID} LIMIT 1");

            $user->log("Produkt-Wechsel durchgeführt (#" . $info->ID . ")");

            if (!$m->ChangePackage($info->ID)) {
                throw new Exception("SWITCHING_ERR3");
            }

            die("<div class=\"alert alert-success\">" . $lang['HOSTING']['SWITCH_SUC'] . "</div>");
        } catch (Exception $ex) {
            die("<div class=\"alert alert-danger\">" . $lang['HOSTING'][$ex->getMessage()] . "</div>");
        }
    }

    if (isset($_POST['note'])) {
        $_POST['note'] = substr(trim($_POST['note']), 0, 25);
        $db->query("UPDATE client_products SET `description` = '" . $db->real_escape_string($_POST['note']) . "' WHERE ID = {$info->ID}");
        $info->description = $_POST['note'];
        $var['suc'] = $lang['HOSTING']['NOTE_OK'];
    }

    if (isset($_POST['domain']) && method_exists($m, "AssignDomain")) {
        $domain = $_POST['domain'];

        function is_valid_domain_name($domain_name)
        {
            return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name)
                && preg_match("/^.{1,253}$/", $domain_name)
                && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name));
        }

        if (!is_valid_domain_name($domain)) {
            $var['err'] = $lang['HOSTING']['DOMAIN_FAIL1'];
        } else {
            if (!$m->AssignDomain($info->ID, $domain)[0]) {
                $var['err'] = $lang['HOSTING']['DOMAIN_FAIL2'];
            } else {
                $var['suc'] = $lang['HOSTING']['DOMAIN_OK'];
            }

        }
    }

    $var['pname'] = unserialize($pInfo->name)[$CFG['LANG']];
    if (!$info->prepaid && isset($_POST['cancel_date']) && $info->cancellation_date == "0000-00-00" && !$onetime && $info->cancellation_allowed) {
        $dates = $provisioning->getCancellationDates($info->ID);
        if (!is_array($dates)) {
            $dates = [$dates];
        }

        if (!in_array($_POST['cancel_date'], $dates)) {
            $var['err'] = $lang['HOSTING']['CANCEL_FAIL'];
        } else {
            $_POST['cancel_date'] = date("Y-m-d", strtotime($_POST['cancel_date']));
            $db->query("UPDATE client_products SET `cancellation_date` = '" . $db->real_escape_string($_POST['cancel_date']) . "' WHERE ID = {$info->ID}");
            $var['suc'] = $lang['HOSTING']['CANCEL_OK'];
            $info->cancellation_date = $_POST['cancel_date'];
            $user->log("Kündigung zum " . date("d.m.Y", strtotime($_POST['cancel_date'])) . " (#" . $info->ID . ")");

            if (($ntf = AdminNotification::getInstance("Neue Kündigung")) !== false) {
                $ntf->set("name", $user->get()['name']);
                $ntf->set("product", $var['pname']);
                $ntf->set("cid", $user->get()['ID']);
                $ntf->set("hid", $info->ID);
                $ntf->send();
            }

            if (is_object($m) && method_exists($m, "Cancellation")) {
                $m->Cancellation($info->ID, $_POST['cancel_date']);
            }

            $mt = new MailTemplate("Kündigungsbestätigung");
            $title = $mt->getTitle($user->getLanguage());
            $mail = $mt->getMail($user->getLanguage(), $user->get()['name']);

            $vars = array(
                "date" => $dfo->format($_POST['cancel_date'], false),
                "product" => $var['pname'],
            );

            $maq->enqueue($vars, $mt, $user->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $user->get()['ID'], false, 0, 0, $mt->getAttachments($user->getLanguage()));

            $addons->runHook("CancellationRequest", [
                "id" => $info->ID,
                "source" => "clientarea",
            ]);

            if (in_array($info->billing, ["minutely", "hourly"])) {
                $from = $info->paid_until;
                $to = time();

                $timeSlice = 60;
                if ($info->billing == "hourly") {
                    $timeSlice *= 60;
                }

                $slices = ceil(($to - $from) / $timeSlice);
                $amount = $slices * $info->price;

                $pInfo = $db->query("SELECT * FROM products WHERE ID = {$info->product}")->fetch_object();
                $invDesc = "<b>" . unserialize($pInfo->name)[$CFG['LANG']] . "</b>";
                if (!empty($info->name)) {
                    $invDesc = "<b>" . $info->name . "</b>";
                }

                if (!empty($info->description)) {
                    $invDesc .= " (" . htmlentities($info->description) . ")";
                }

                $myCf = unserialize($info->cf);

                $invCf = [];
                foreach ($myCf as $fieldId => $fieldVal) {
                    $cfSql = $db->query("SELECT name FROM products_cf WHERE ID = " . intval($fieldId));
                    if ($cfSql->num_rows) {
                        $invCf[$cfSql->fetch_object()->name] = $fieldVal;
                    }
                }

                if ($pInfo->desc_on_invoice) {
                    $invDesc .= "<br />" . (@unserialize($pInfo->description) ? unserialize($pInfo->description)[$CFG['LANG']] : $pInfo->description);
                }

                if (count($invCf)) {
                    $invDesc .= "<br />";
                    foreach ($invCf as $fieldName => $fieldVal) {
                        $invDesc .= "<br />" . $fieldName . ": " . $fieldVal;
                    }
                }

                $invDesc .= "<br /><br />" . $dfo->format($from, true, true, "-") . " - " . $dfo->format($to, true, true, "-");

                $item = new InvoiceItem;
                $item->setAmount($amount);
                $item->setDescription($invDesc);
                $item->setRelid($info->ID);
                $item->save();

                $invoice = new Invoice;
                $invoice->setDate(date("Y-m-d"));
                $invoice->setClient($user->get()['ID']);
                $invoice->setDueDate();
                $invoice->addItem($item);
                $invoice->save();
                $invoice->applyCredit(false);
                $invoice->save();
                $invoice->send();

                $db->query("UPDATE client_products SET `active` = -2, paid_until = $to WHERE ID = {$info->ID}");

                if (is_object($m) && method_exists($m, "Delete")) {
                    $m->Delete($info->ID);
                }

                header('Location: ' . $CFG['PAGEURL'] . 'products');
                exit;
            }
        }
    }

    if (!$info->prepaid && isset($_POST['cancel_sure']) && $_POST['cancel_sure'] == "yes" && $info->cancellation_date > "0000-00-00" && !$onetime && !$pInfo->autodelete && $info->cancellation_allowed) {
        $db->query("UPDATE client_products SET `cancellation_date` = '0000-00-00' WHERE ID = {$info->ID}");
        $var['suc'] = $lang['HOSTING']['CANCEL_REVOKED'];
        $info->cancellation_date = "0000-00-00";
        $user->log("Kündigung storniert (#" . $info->ID . ")");
        if (method_exists($m, "Cancellation")) {
            $m->Cancellation($info->ID, "0000-00-00");
        }

        $addons->runHook("CancellationRevoke", [
            "id" => $info->ID,
            "source" => "clientarea",
        ]);
    }

    $var['h'] = (array) $info;
    $var['p'] = (array) $pInfo;
    $var['actions'] = is_object($m) ? $m->OwnFunctions($info->ID) : [];
    $var['url'] = $CFG['PAGEURL'] . "hosting/" . $info->ID;
    $var['output'] = is_object($m) ? $m->Output($info->ID, isset($pars[1]) && array_key_exists($pars[1], $var['actions']) ? $pars[1] : "") : "";
    $var['add_domains'] = is_object($m) ? method_exists($m, "AssignDomain") : false;
    $var['canceldates'] = $provisioning->getCancellationDates($info->ID);
    $var['maxincldomains'] = intval($pInfo->incldomains);

    $sql = $db->query("SELECT domain FROM domains WHERE inclusive_id = {$info->ID}");
    $var['incldomains'] = $sql->num_rows;
    $var['incldomainlist'] = [];

    while ($row = $sql->fetch_object()) {
        list($sld, $tld) = explode(".", $row->domain, 2);

        $link = $CFG['PAGEURL'] . "domain/$sld/$tld";
        $var['incldomainlist'][$link] = $row->domain;
    }

    $sql = $db->query("SELECT domain FROM domains WHERE addon_id = {$info->ID}");
    $var['addondomains'] = $sql->num_rows;
    $var['addondomainlist'] = [];

    while ($row = $sql->fetch_object()) {
        list($sld, $tld) = explode(".", $row->domain, 2);

        $link = $CFG['PAGEURL'] . "domain/$sld/$tld";
        $var['addondomainlist'][$link] = $row->domain;
    }

    $var['user_limit'] = User::getInstance($var['user']['ID'], "ID")->getLimit();
}
