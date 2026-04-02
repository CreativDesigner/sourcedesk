<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain']) || $db->query("SELECT 1 FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows != 1) {
	die(json_encode(Array("code" => "803", "message" => "Domain not found.", "data" => Array())));
}

$reg = $db->query("SELECT registrar FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "'")->fetch_object()->registrar;
$regs = DomainHandler::getRegistrars();
if (!array_key_exists($reg, $regs) || !is_object($reg = $regs[$reg]) || !$reg->isActive()) {
	die(json_encode(Array("code" => "804", "message" => "Error occured.", "data" => Array())));
}

if (!isset($_REQUEST['type']) || !in_array($_REQUEST['type'], Array("0", "1", "2"))) {
	die(json_encode(Array("code" => "805", "message" => "Invalid type.", "data" => Array())));
}

$reg->setUser($user);
if (!method_exists($reg, "deleteDomain") || true !== $reg->deleteDomain($_REQUEST['domain'], $_REQUEST['type'])) {
	die(json_encode(Array("code" => "806", "message" => "Deletion/Returnation failed.", "data" => Array())));
}

$user->log("[API] [" . $_REQUEST['domain'] . "] Domain " . ($_REQUEST['type'] == "0" ? "gelöscht" : "in Transit gegeben"));
$db->query("UPDATE domains SET status = '" . ($_REQUEST['type'] == "0" ? "DELETED" : "TRANSIT") . "' WHERE domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND user = " . $user->get()['ID']);

die(json_encode(Array("code" => "100", "message" => "Domain deleted/returned to registry.", "data" => Array())));