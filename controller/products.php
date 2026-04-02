<?php
// Global some variables for security reasons
global $db, $user, $CFG, $nfo, $var, $lang, $dfo, $cur, $sec, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['PRODUCTS']['PTITLE'];
$tpl = "products";

// Get products from user
$var['products'] = $p = unserialize($user->get()['products_info']);

foreach ($var['products'] as &$v) {
    $v['recurring'] = $cur->infix($nfo->format($v['price']));
    $v['cancellation'] = false;

    if (!empty($v['billing']) && $v['billing'] != "onetime") {
        $v['recurring'] .= " " . $lang['CART'][strtoupper($v['billing'])];
        $v['cancellation'] = true;

        if ($v['cancellation_date'] == "0000-00-00") {
            $v['cancellation_date'] = false;
        }
    }
}
unset($v);

// Get domains for user
$var['domains'] = array();
$sql = $db->query("SELECT * FROM domains WHERE user = " . $user->get()['ID'] . " ORDER BY domain ASC");
while ($row = $sql->fetch_object()) {
    $t = $user->getVAT();
    if (is_array($t) && count($t) == 2 && doubleval($t[1]) == $t[1]) {
        $row->recurring = $row->recurring * (1 + $t[1] / 100);
    }

    $row->inclusive = false;
    if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
        $row->inclusive = true;
    }

    $var['domains'][$row->ID] = $row;
}
$var['cur'] = $cur;
$var['nfo'] = $nfo;

// Get hosting for user
$var['hosting'] = array();
$sql = $db->query("SELECT * FROM client_products WHERE user = " . $user->get()['ID'] . " AND `type` = 'h' ORDER BY active = -2 ASC, active ASC, date DESC, ID DESC");
while ($row = $sql->fetch_object()) {
    $pSql = $db->query("SELECT name FROM products WHERE ID = {$row->product}");
    if ($pSql->num_rows != 1) {
        continue;
    }

    $pInfo = $pSql->fetch_object();

    $var['hosting'][$row->ID] = array(
        $row->date,
        $row->name ?: unserialize($pInfo->name)[$CFG['LANG']],
        $row->description,
        $cur->infix($nfo->format($cur->convertAmount(null, $row->price, null))),
        $row->billing,
        !empty($row->error) ? -3 : $row->active,
        $row->cancellation_date,
        "payment" => $row->payment,
        "prepaid" => $row->prepaid,
    );
}

// Get recurring products
function niceInterval($i)
{
    global $lang;

    $ex = explode(" ", $i);
    $int1 = $ex[0];
    $int2 = $ex[1];

    if ($int1 == 1) {
        $key = strtoupper($int2);
    } else {
        $key = strtoupper($int2) . "S";
    }

    return $int1 . " " . $lang['PRODUCTS'][$key];
}

$var["recurring"] = [];
$sql = $db->query("SELECT * FROM invoice_items_recurring WHERE user = " . $user->get()["ID"] . " AND `status` = 1 ORDER BY `last` DESC, `ID` DESC");
while ($row = $sql->fetch_object()) {
    $inv = new RecurringInvoice($row->ID);

    if ($inv->hasExpired()) {
        continue;
    }

    $var["recurring"][] = [
        $dfo->format($row->first, false),
        $dfo->format($row->last, false),
        $dfo->format(strtotime("+" . $row->period, strtotime($row->last)), false),
        niceInterval($row->period),
        $row->description,
        $cur->infix($nfo->format($cur->convertAmount(null, $row->amount))),
    ];
}
