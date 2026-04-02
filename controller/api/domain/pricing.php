<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['tld'])) {
	die(json_encode(Array("code" => "803", "message" => "No TLD specified.")));
}

$p = Array();
$p[0] = $user->getDomainPrice($_REQUEST['tld'], "register");

if ($p[0] === false) {
	die(json_encode(Array("code" => "804", "message" => "TLD not found.")));
}

$p[1] = $user->getDomainPrice($_REQUEST['tld'], "transfer");
$p[2] = $user->getDomainPrice($_REQUEST['tld'], "renew");

die(json_encode(Array("code" => "100", "message" => "Pricing fetched.", "data" => $p)));

?>