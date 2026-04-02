<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain']) || $db->query("SELECT 1 FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows != 1) {
	die(json_encode(Array("code" => "803", "message" => "Domain not found.", "data" => Array())));
}

$dns = DNSHandler::getDriver($_REQUEST['domain']);
if (!($z = $dns->getZone($_REQUEST['domain']))) {
	die(json_encode(Array("code" => "804", "message" => "Zone not found.", "data" => Array())));
}

if (empty($_REQUEST['record']) || !is_numeric($_REQUEST['record'])) {
	die(json_encode(Array("code" => "805", "message" => "No valid record specified.", "data" => Array())));
}

if (!array_key_exists($_REQUEST['record'], $z)) {
	die(json_encode(Array("code" => "806", "message" => "Record not found.", "data" => Array())));
}

if (!$dns->editRecord($_REQUEST['domain'], $_REQUEST['record'], Array(
	$_REQUEST['name'],
	$_REQUEST['type'],
	$_REQUEST['content'],
	$_REQUEST['ttl'],
	$_REQUEST['priority'],
))) {
	die(json_encode(Array("code" => "807", "message" => "Error at editing record.", "data" => Array())));
}

$user->log("[API] [" . $_REQUEST['domain'] . "] DNS-Eintrag bearbeitet");

die(json_encode(Array("code" => "100", "message" => "Record edited.", "data" => Array())));

?>