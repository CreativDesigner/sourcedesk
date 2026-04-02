<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain']) || $db->query("SELECT 1 FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows != 1) {
    die(json_encode(array("code" => "803", "message" => "Domain not found.", "data" => array())));
}

if ($user->get()['auth_lock'] == "0" && !$CFG['CUSTOMER_AUTHCODE']) {
    die(json_encode(array("code" => "805", "message" => "Authcode generation not allowed.", "data" => array())));
}

if ($user->get()['auth_lock'] == "1") {
    die(json_encode(array("code" => "805", "message" => "Authcode generation not allowed.", "data" => array())));
}

$reg = $db->query("SELECT registrar FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "'")->fetch_object()->registrar;
$regs = DomainHandler::getRegistrars();
if (!array_key_exists($reg, $regs) || !is_object($reg = $regs[$reg]) || !$reg->isActive() || !$reg->setUser($user) || !method_exists($reg, "getAuthCode") || substr($c = $reg->getAuthCode($_REQUEST['domain']), 0, 5) != "AUTH:") {
    die(json_encode(array("code" => "804", "message" => "Error occured.", "data" => array())));
}

$user->log("[API] [" . $_REQUEST['domain'] . "] AuthCode abgefragt");

$c = (String) $c;
die(json_encode(array("code" => "100", "message" => "Authcode fetched.", "data" => array("code" => substr($c, 5)))));
