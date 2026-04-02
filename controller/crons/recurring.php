<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$ia = intval($CFG['INVOICE_ADVANCE']);
if ($ia < 0 || $ia > 25) {
    $ia = 0;
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

// Get all later invoice items by user
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Getting later invoice items by user\n", FILE_APPEND);
$sql = $db->query("SELECT user FROM invoicelater GROUP BY user");
while ($row = $sql->fetch_object()) {
    $user = new User($row->user, "ID");
    $user->invoiceNow();
    $user->tryToClear();
}

// Get all recurring tasks
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Getting recurring invoice items\n", FILE_APPEND);
$sql = $db->query("SELECT ID, user FROM invoice_items_recurring WHERE status = 1");

// Group by users
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Grouping recurring jobs by user\n", FILE_APPEND);
$users = array();
while ($row = $sql->fetch_object()) {
    if (array_key_exists($row->user, $users)) {
        array_push($users[$row->user], $row->ID);
    } else {
        $users[$row->user] = array($row->ID);
    }

}

// Bill per user
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Billing users\n", FILE_APPEND);
foreach ($users as $uid => $ids) {
    $inv = 0;
    foreach ($ids as $id) {
        $obj = new RecurringInvoice($id);
        $res = $obj->bill(false, $inv != 0 ? $inv : null);
        if ($res !== false) {
            $inv = $res;
        }
    }

    if ($inv != 0) {
        $obj = new Invoice;
        $obj->load($inv);

        $invoice->save();
        $invoice->applyCredit(false);

        $obj->send();

        $uI = User::getInstance($uid, "ID");
        if ($uI) {
            $uI->tryToClear();
        }
    }
}

// Function to create invoice
function createInvoiceFromRecurring(User $uI, array $items)
{
    $invoice = new Invoice;
    $invoice->setDate(date("Y-m-d"));
    $invoice->setClient($uI->get()['ID']);
    $invoice->setDueDate();
    foreach ($items as $item) {
        $invoice->addItem($item);
    }

    $invoice->save();
    $invoice->applyCredit(false);
    $invoice->save();
    $invoice->send();

    $uI->tryToClear();
}

// Get all recurring products
while (true) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Getting recurring products\n", FILE_APPEND);
    $sql = $db->query("SELECT ID, user FROM client_products WHERE last_billed <= '" . date("Y-m-d", strtotime("+$ia days")) . "' AND last_billed != '0000-00-00' AND (cancellation_date = '0000-00-00' OR cancellation_date > last_billed) AND active != -2 AND price > 0 AND billing != '' AND billing != 'onetime' AND prepaid = 0");
    if ($sql->num_rows == 0) {
        break;
    }

    // Group by users
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Grouping recurring jobs by user\n", FILE_APPEND);
    $users = array();
    while ($row = $sql->fetch_object()) {
        if (array_key_exists($row->user, $users)) {
            array_push($users[$row->user], $row->ID);
        } else {
            $users[$row->user] = array($row->ID);
        }

    }

    // Bill per user
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Billing users\n", FILE_APPEND);
    foreach ($users as $uid => $ids) {
        $uI = User::getInstance($uid, "ID");
        if (!$uI) {
            continue;
        }

        $sum = 0;
        $items = array();
        foreach ($ids as $id) {
            $i = $db->query("SELECT * FROM client_products WHERE ID = {$id}")->fetch_object();

            $billarr = array(
                "monthly" => "1 month",
                "quarterly" => "3 months",
                "semiannually" => "6 months",
                "annually" => "1 year",
                "biennially" => "2 years",
                "trinnially" => "3 years",
            );

            $pInfo = $db->query("SELECT * FROM products WHERE ID = {$i->product}")->fetch_object();
            $invDesc = "<b>" . unserialize($pInfo->name)[$uI->getLanguage()] . "</b>";
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
                $invDesc .= "<br />" . (@unserialize($pInfo->description) ? unserialize($pInfo->description)[$uI->getLanguage()] : $pInfo->description);
            }

            if (count($invCf)) {
                $invDesc .= "<br />";
                foreach ($invCf as $fieldName => $fieldVal) {
                    $invDesc .= "<br />" . $fieldName . ": " . $fieldVal;
                }
            }

            if ($i->billing == "minutely" || $i->billing == "hourly") {
                if (date("d") != date("t")) {
                    continue;
                }

                $from = $i->paid_until;
                $to = time();

                $invDesc .= "<br /><br />" . $dfo->format($from, true, true, "-", $uI->getDateFormat()) . " - " . $dfo->format($to, true, true, "-", $uI->getDateFormat());

                $timeSlice = 60;
                if ($i->billing == "hourly") {
                    $timeSlice *= 60;
                }

                $slices = ceil(($to - $from) / $timeSlice);
                $amount = $slices * $i->price;

                $db->query("UPDATE client_products SET paid_until = $to WHERE ID = {$i->ID} LIMIT 1");
            } else {
                $from = strtotime($i->last_billed);
                $to = strtotime("-1 day, +" . $billarr[$i->billing], strtotime($i->last_billed));
                $amount = $i->price;

                if ($i->cancellation_date != "0000-00-00" && date("Y-m-d", $to) > $i->cancellation_date) {
                    $origTo = $to;
                    $to = strtotime($i->cancellation_date);

                    $periodBefore = floor(($origTo - $from) / 86400);
                    $periodNow = floor(($to - $from) / 86400);
                    $factor = $periodNow / $periodBefore;
                    $amount *= $factor;

                    if ($periodNow <= 0) {
                        continue;
                    }
                }

                $invDesc .= "<br /><br />" . $dfo->format($from, false, false, "", $uI->getDateFormat()) . " - " . $dfo->format($to, false, false, "", $uI->getDateFormat());
            }

            $db->query("UPDATE client_products SET last_billed = '" . date("Y-m-d", strtotime("+1 day", $to)) . "' WHERE ID = {$i->ID} LIMIT 1");

            $item = new InvoiceItem;
            $item->setAmount($amount);
            $item->setDescription($invDesc);
            $item->setRelid($i->ID);
            $item->save();
            array_push($items, $item);
            $sum += $amount;

            if (!$uI->get()['group_recurring']) {
                createInvoiceFromRecurring($uI, $items);
                $items = [];
                $sum = 0;
            }
        }

        if ($sum > 0) {
            createInvoiceFromRecurring($uI, $items);
        }

        $uI->tryToClear();
    }
}

// Usage billing
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Usage billing\n", FILE_APPEND);

$sql = $db->query("SELECT ID, usage_billing, module, `name` FROM products WHERE `usage_billing` != ''");
while ($row = $sql->fetch_object()) {
    $modules = $provisioning->get();
    if (!array_key_exists($row->module, $modules)) {
        continue;
    }

    $module = $modules[$row->module];

    $cid = 0;
    $date = date("Y-m-d");
    $parameter = "";
    $utilization = 0;

    $stmt = $db->prepare("INSERT INTO product_usage (`cid`, `date`, `parameter`, `utilization`) VALUES (?,?,?,?)");
    $stmt->bind_param("issi", $cid, $date, $parameter, $utilization);

    $del = $db->prepare("DELETE FROM product_usage WHERE cid = ?");
    $del->bind_param("i", $cid);

    $fetch = $db->prepare("SELECT utilization FROM product_usage WHERE cid = ? AND parameter = ?");
    $fetch->bind_param("is", $cid, $parameter);

    $row->module = $db->real_escape_string($row->module);
    $cSql = $db->query("SELECT ID, `name`, user, `description` FROM client_products WHERE active >= 0 AND type = 'h' AND product = {$row->ID} AND module = '{$row->module}'");
    while ($con = $cSql->fetch_object()) {
        $res = $module->usageFetch($cid = $con->ID);
        if (!is_array($res) || !count($res)) {
            continue;
        }

        foreach ($res as $key => $usage) {
            $parameter = $db->real_escape_string($key);
            $utilization = intval($usage);

            $stmt->execute();
        }

        if (gmdate('t') == gmdate('d')) {
            $usage = @unserialize($row->usage_billing);
            if (!is_array($usage)) {
                continue;
            }

            $invoiceitems = [];

            foreach ($usage as $key => $invoicing) {
                $util = 0;
                $parameter = $key;
                $fetch->execute();
                $fetch->bind_result($util);

                $sum = 0;

                while ($fetch->fetch()) {
                    $sum += $util;
                }

                $average = floor($sum / gmdate('d'));

                $paid = $average - $invoicing[0];

                if ($paid <= 0) {
                    continue;
                }

                $amount = $paid;
                $paid = ceil($paid / $invoicing[2]);
                $paid = round($paid * $invoicing[1], 2);

                if ($paid == 0) {
                    continue;
                }

                $name = $con->name ?: unserialize($row->name)[$CFG['LANG']];
                $usageName = array_key_exists($key, $module->usagePars()) ? $module->usagePars()[$key] : "";

                $extraDesc = "";
                if (!empty($con->description)) {
                    $extraDesc = " | " . htmlentities($con->description);
                }

                $item = new InvoiceItem;
                $item->setDescription("<b>$name</b> (#$cid$extraDesc)<br />$usageName: " . round($amount));
                $item->setAmount($paid);
                $item->setTax(true);
                $item->setRelid($cid);
                array_push($invoiceitems, $item);
            }

            $inv = new Invoice;
            $inv->setDate(date("Y-m-d"));
            $inv->setClient($con->user);
            $inv->setDueDate();
            $inv->setStatus(0);

            foreach ($invoiceitems as $item) {
                $inv->addItem($item);
            }

            $inv->save();
            $inv->send();

            if ($uI = User::getInstance($con->user, "ID")) {
                $uI->tryToClear();
            }

            $del->execute();
        }
    }

    $stmt->close();
    $del->close();
    $fetch->close();
}

// Expiration warning for prepaid products
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Expiration warning (prepaid products)\n", FILE_APPEND);

foreach (["1", "3", "7"] as $in) {
    $sql = $db->query("SELECT * FROM client_products WHERE type = 'h' AND active != -2 AND active != -1 AND error = '' AND prepaid = 1 AND last_billed = '" . date("Y-m-d", strtotime("+$in days")) . "' AND last_billed > '0000-00-00'");
    while ($row = $sql->fetch_object()) {
        if (!($user = User::getInstance($row->user, "ID"))) {
            continue;
        }

        $pSql = $db->query("SELECT `name` FROM products WHERE ID = {$row->product}");
        if ($pName = ($pSql->num_rows ? $pSql->fetch_object()->name : "")) {
            $pName = @unserialize($pName) ?: $pName;
            if (is_array($pName) && array_key_exists($user->getLanguage(), $pName)) {
                $pName = $pName[$user->getLanguage()];
            }
        }

        $data = [
            "expiration" => $dfo->format($row->last_billed, false, false, "", $user->getDateFormat()),
            "link" => $raw_cfg["PAGEURL"] . "hosting/" . $row->ID,
            "product" => $row->name ?: $pName,
        ];

        $mtObj = new MailTemplate("Ablauf-Warnung");
        $titlex = $mtObj->getTitle($user->getLanguage());
        $mail = $mtObj->getMail($user->getLanguage(), $user->get()['name']);

        $maq->enqueue($data, $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], false, 0, 0, $user->getLanguage());
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
