<?php
// Global some variables for security reasons
global $db, $user, $CFG, $nfo, $var, $lang, $dfo, $cur, $pars, $provisioning, $maq;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['PREORDER']['TITLE'];
$tpl = "preorder";

$pid = isset($pars[0]) ? $pars[0] : 0;
if (substr($pid, 0, 1) == "r") {
    $pid = substr($pid, 1);
}

$sql = $db->query("SELECT * FROM products WHERE ID = " . intval($pid));
if ($sql->num_rows == 0) {
    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];
    return;
}

$info = $sql->fetch_object();
if ($info->available != 0) {
    header('Location: ' . $CFG['PAGEURL'] . 'cart?add_product=' . $info->ID);
    exit;
}

if ($info->incldomains > 0 || $db->query("SELECT 1 FROM products_cf WHERE product = " . intval($_GET['add_product']))->num_rows > 0 || !$info->preorder) {
    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];
    return;
}

if ($info->only_verified == 1 && !$user->get()['verified']) {
    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];
    return;
}

if (trim($info->customer_groups) != "") {
    if (!$var['logged_in']) {
        $tpl = "error";
        $title = $lang['ERROR']['TITLE'];
        return;
    } else if ($var['logged_in'] && !in_array($user->get()['cgroup'], explode(",", $info->customer_groups))) {
        $tpl = "error";
        $title = $lang['ERROR']['TITLE'];
        return;
    }
}

$var['ident'] = $pars[0];
$var['done'] = isset($pars[1]) && $pars[1] == "done";
$var['recurring'] = $info->type == "HOSTING" && $info->billing != "onetime" && $info->billing != "";
$var['product'] = unserialize($info->name)[$CFG['LANG']];
$var['raw_amount'] = $info->price;
$var['amount'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $var['raw_amount'], null, true, true)));
$var['topup'] = $CFG['PAGEURL'] . "credit/amount/" . ($var['raw_amount'] - $user->get()['credit']);

if (isset($pars[1]) && $pars[1] == "do" && $user->get()['credit'] >= $var['raw_amount']) {
    $subject = $title . " (" . $db->real_escape_string($var['product']) . ")";

    $user->set(array("credit" => $user->get()['credit'] - $cur->realAmount($var['raw_amount'])));
    $db->query("INSERT INTO client_transactions (user, time, subject, amount, waiting) VALUES ({$user->get()['ID']}, " . time() . ", '$subject', -{$cur->realAmount($var['raw_amount'])}, 2)");

    header('Location: ' . $CFG['PAGEURL'] . 'preorder/' . $pars[0] . '/done');
    exit;
}
