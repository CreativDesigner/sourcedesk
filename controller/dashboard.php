<?php
// Global some variables for security reasons
global $db, $user, $CFG, $nfo, $var, $lang, $dfo, $cur, $sec, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['NAV']['DASHBOARD'];
$tpl = "dashboard";

$var['tickets'] = [];
$sql = $db->query("SELECT * FROM support_tickets WHERE customer = " . $user->get()['ID'] . " AND customer_access = 1 ORDER BY ID DESC LIMIT 5");
while ($row = $sql->fetch_array()) {
    $row['url'] = $CFG['PAGEURL'] . "ticket/" . $row['ID'] . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $row['ID'] . "ticketview" . $CFG['HASH']), -16);
    $row['t'] = new Ticket($row['ID']);
    array_push($var['tickets'], $row);
}

$var['contracts'] = [];
$products = unserialize($user->get()['products_info']);
$var['hadContracts'] = count($products) > 0;

foreach ($products as $product) {
    if (count($var['contracts']) >= 5) {
        break;
    }

    if ($product['active'] == 1) {
        $product['info'] = $dfo->format($product['date'], "", false, false);

        if ($product['price']) {
            $product['info'] .= " // " . $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency, $product['price'])));
            $bill = strtoupper($product['billing']) ?: "ONETIME";
            $product['info'] .= " " . $lang['CART'][$bill];
        }

        array_push($var['contracts'], $product);
    }
}

$var['domains'] = [];
$sql = $db->query("SELECT * FROM domains WHERE user = " . $user->get()['ID'] . " AND status IN ('REG_OK', 'KK_OK') ORDER BY expiration ASC LIMIT 5");
while ($row = $sql->fetch_object()) {
    $t = $user->getVAT();
    if (is_array($t) && count($t) == 2 && doubleval($t[1]) == $t[1]) {
        $row->recurring = $row->recurring * (1 + $t[1] / 100);
    }

    $row->inclusive = false;
    if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
        $row->inclusive = true;
    }

    $row->expires = $dfo->format($row->expiration, "", false, false);

    $var['domains'][] = (array) $row;
}

if ($user->get()['telephone_pin_set'] < time() - 300) {
    $user->set(array("telephone_pin" => rand(111111, 999999), "telephone_pin_set" => time()));
}

$var['user'] = $user->get();

$var['overdue'] = [
    "num" => 0,
    "amount" => 0,
];
$sql = $db->query("SELECT ID FROM invoices WHERE client = " . $user->get()['ID'] . " AND status = 0");

$inv = new Invoice;
while ($row = $sql->fetch_object()) {
    if ($inv->load($row->ID)) {
        $var['overdue']['num']++;
        $var['overdue']['amount'] += $inv->getOpenAmount();
    }
}

$var['overdue']['amount'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $var['overdue']['amount'])));
