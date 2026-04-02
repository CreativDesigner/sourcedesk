<?php
global $db, $CFG, $lang, $cur, $nfo, $user, $transactions, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$tpl = "affiliate";
$title = $lang['AFFILIATE']['TITLE'];

function fill_variables()
{
    global $var, $nfo, $cur, $user, $db, $CFG;

    $var['credit'] = $user->get()['affiliate_credit'];
    $var['credit_f'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $user->get()['affiliate_credit'])));

    $waiting = 0;
    $sql = $db->query("SELECT `time`, amount FROM client_affiliate WHERE cancelled = 0 AND affiliate = " . $user->get()['ID']);
    while ($row = $sql->fetch_object()) {
        if ($row->time >= strtotime("-" . intval($CFG['AFFILIATE_DAYS']) . " days")) {
            $waiting += $row->amount;
        }
    }

    $var['waiting'] = $waiting;
    $var['waiting_f'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $waiting)));

    $var['free'] = $user->get()['affiliate_credit'] - $waiting;
    $var['free_f'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $user->get()['affiliate_credit'] - $waiting)));

    $var['min'] = str_replace(",", ".", $CFG['AFFILIATE_MIN']);
    $var['min_f'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), str_replace(",", ".", $CFG['AFFILIATE_MIN']))));
}

fill_variables();

if (isset($_POST['action']) && $_POST['action'] == "withdraw" && $var['free'] > 0) {
    if ($_POST['withdraw_method'] == "credit") {
        $user->set(array("affiliate_credit" => $user->get()['affiliate_credit'] - $var['free'], "credit" => $user->get()['credit'] + $var['free'], "special_credit" => $user->get()['special_credit'] + $var['free']));
        $transactions->insert("affiliate", 0, $var['free'], $user->get()['ID']);
        $user->applyCredit();
        fill_variables();
        $var['suc'] = $lang['AFFILIATE']['WS1'];
    } else if ($_POST['withdraw_method'] == "cashout" && $var['free'] >= str_replace(",", ".", $CFG['AFFILIATE_MIN'])) {
        $user->set(array("affiliate_credit" => $user->get()['affiliate_credit'] - $var['free']));
        $transactions->insert("affiliate", 0, $var['free'], $user->get()['ID']);
        $transactions->insert("affiliate_withdrawal", 0, $var['free'] / -1, $user->get()['ID'], "", 0, 2);
        fill_variables();
        $var['suc'] = $lang['AFFILIATE']['WS2'];
    }
}

$var['products'] = array();
$sql = $db->query("SELECT ID, name, affiliate FROM products WHERE status = 1");
while ($row = $sql->fetch_object()) {
    $percent = $row->affiliate >= 0 ? $row->affiliate : str_replace(",", ".", $CFG['AFFILIATE_COMMISSION']);

    if (should_show_product($row->ID) && $percent > 0) {
        $var['products'][$row->ID] = unserialize($row->name)[$CFG['LANG']];
    }
}

asort($var['products']);

if (isset($_POST['product'])) {
    $sql = $db->query("SELECT ID, name, affiliate, price FROM products WHERE ID = " . intval($_POST['product']));
    if ($sql->num_rows == 1 && should_show_product(intval($_POST['product']))) {
        $info = $sql->fetch_object();
        $percent = $info->affiliate >= 0 ? $info->affiliate : str_replace(",", ".", $CFG['AFFILIATE_COMMISSION']);

        $var['ptext'] = str_replace(array("%n", "%p", "%a", "%u"), array(unserialize($info->name)[$CFG['LANG']], $nfo->format($percent), $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $info->price * $percent / 100))), $CFG['PAGEURL'] . "cart?add_product={$info->ID}&ref={$user->get()['ID']}"), $lang['AFFILIATE']['PTEXT']);
    }
}

$var['clients'] = array();
$sql = $db->query("SELECT * FROM clients WHERE affiliate = {$user->get()['ID']} ORDER BY registered DESC, ID DESC");
while ($u = $sql->fetch_object()) {
    $waiting = $db->query("SELECT SUM(amount) AS s FROM client_affiliate WHERE user = {$u->ID} AND affiliate = {$user->get()['ID']} AND cancelled = 0 AND `time` >= " . strtotime("-" . $CFG['AFFILIATE_DAYS'] . " days"))->fetch_object()->s;
    $cancelled = $db->query("SELECT SUM(amount) AS s FROM client_affiliate WHERE user = {$u->ID} AND affiliate = {$user->get()['ID']} AND cancelled = 1")->fetch_object()->s;
    $available = $db->query("SELECT SUM(amount) AS s FROM client_affiliate WHERE user = {$u->ID} AND affiliate = {$user->get()['ID']} AND cancelled = 0 AND `time` < " . strtotime("-" . $CFG['AFFILIATE_DAYS'] . " days"))->fetch_object()->s;

    $var['clients'][] = array(
        $dfo->format($u->registered),
        $u->firstname . " " . substr($u->lastname, 0, 1) . ".",
        $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $waiting))),
        $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $cancelled))),
        $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $available))),
    );
}
