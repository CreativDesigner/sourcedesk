<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain'])) {
	die(json_encode(Array("code" => "803", "message" => "Domain not given.", "data" => Array())));
}

$ex = explode(".", $_REQUEST['domain']);
$sld = array_shift($ex);
$tld = implode(".", $ex);
$r = DomainHandler::getRegistrarByTld($tld);

if (!$r || !$r->isActive()) {
	die(json_encode(Array("code" => "804", "message" => "TLD not known.", "data" => Array())));
}

$status = DomainHandler::availibilityStatus($_REQUEST['domain'], $r);
if ($status === true) {
	$status = "free";
} else if ($status === false) {
	$status = "taken";
} else {
	$status = "fail";
}

die(json_encode(Array("code" => "100", "message" => "Status fetched.", "data" => Array("status" => $status))));