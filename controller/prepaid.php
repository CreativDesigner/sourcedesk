<?php
global $CFG, $db, $user, $pars, $nfo, $cur, $dfo;

User::status();

try {
    if (!isset($pars[0]) || !is_numeric($pars[0]) || !is_object($sql = $db->query("SELECT * FROM client_products WHERE ID = " . intval($pars[0]) . " AND type = 'h' AND active = 1 AND user = " . $user->get()['ID'] . " AND prepaid = 1")) || $sql->num_rows != 1) {
        throw new Exception;
    } else {
        $info = $sql->fetch_object();

        $var['ppDays'] = [];

        $breakdown = [
            "monthly" => "30",
            "quarterly" => "90",
            "semiannually" => "180",
            "annually" => "360",
            "biennially" => "720",
            "trinnially" => "1080",
        ];

        $ppDaySql = $db->query("SELECT ID, days, bonus FROM products_prepaid WHERE product = {$info->product}");
        while ($ppDay = $ppDaySql->fetch_object()) {
            $price = $info->price;

            if (!array_key_exists($info->billing, $breakdown)) {
                continue;
            }

            $price /= $breakdown[$info->billing];

            $var['ppDays'][$ppDay->ID] = [$ppDay->days, $cur->convertAmount($cur->getBaseCurrency(), round($price * $ppDay->days * (1 - $ppDay->bonus / 100), 2))];
        }

        if (empty($pars[1]) || !array_key_exists($pars[1], $var['ppDays'])) {
            throw new Exception;
        }

        $pp = $var['ppDays'][$pars[1]];

        if ($user->getLimit() < $pp[1]) {
            throw new Exception;
        }

        $var['pp'] = $pp;
        $var['cid'] = $pars[0];
        $var['invoice'] = "";

        $var['oldd'] = $dfo->format($info->last_billed, false, false);
        $var['newd'] = $dfo->format(strtotime("+{$pp[0]} days", strtotime($info->last_billed)), false, false);

        if (isset($_POST['do'])) {
            $i = $info;

            $pInfo = $db->query("SELECT * FROM products WHERE ID = {$i->product}")->fetch_object();
            $invDesc = "<b>" . unserialize($pInfo->name)[$CFG['LANG']] . "</b>";
            if (!empty($i->name)) {
                $invDesc = "<b>" . $i->name . "</b>";
            }

            if (!empty($i->description)) {
                $invDesc .= " (" . htmlentities($i->description) . ")";
            }

            $myCf = unserialize($i->cf);

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

            $from = strtotime($i->last_billed);
            $to = strtotime("-1 day, +" . $pp[0] . " days", strtotime($i->last_billed));

            $invDesc .= "<br /><br />" . $dfo->format($from, false, false) . " - " . $dfo->format($to, false, false);
            $amount = $pp[1];

            $db->query("UPDATE client_products SET last_billed = '" . date("Y-m-d", strtotime("+1 day", $to)) . "' WHERE ID = {$i->ID} LIMIT 1");

            $item = new InvoiceItem;
            $item->setAmount($amount);
            $item->setDescription($invDesc);
            $item->setRelid($i->ID);
            $item->save();

            $invoice = new Invoice;
            $invoice->setDate(date("Y-m-d"));
            $invoice->setClient($user->get()['ID']);
            $invoice->setDueDate(date("Y-m-d"));
            $invoice->addItem($item);

            $invoice->save();
            $invoice->applyCredit();
            $invoice->save();
            $invoice->send();

            $var['invoice'] = $invoice->getInvoiceNo();
            $var['invoice_link'] = $CFG['PAGEURL'] . "invoices/" . $invoice->getId();
        }

        $tpl = "prepaid";
        $title = $lang['PREPAID']['TITLE'];
    }
} catch (Exception $ex) {
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
}
