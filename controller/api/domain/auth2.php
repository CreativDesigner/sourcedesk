<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$domain = $_REQUEST['domain'];
if (empty($domain)) {
	die(json_encode(Array("code" => "803", "message" => "No domain specified.", "data" => Array())));
}

$ex = explode(".", $domain);
$sld = array_shift($ex);
$tld = implode(".", $ex);

if (count($ex) < 1) {
	die(json_encode(Array("code" => "804", "message" => "Invalid domain specified.", "data" => Array())));
}

$reg = DomainHandler::getAuthTwoByTld($tld);
if (!$reg || !$reg->isActive()) {
	die(json_encode(Array("code" => "805", "message" => "TLD not available.", "data" => Array())));
}
$reg->setUser($user);

if (false !== ($a = DomainHandler::getRegistrarByTld($tld)->availibilityStatus($domain))) {
	die(json_encode(Array("code" => "806", "message" => "Domain not registered.", "data" => Array())));
}

$rawPrice = $user->getDomainPrice($tld, "auth2");
$price = $user->addTax($rawPrice);
if ($rawPrice === false || $price > $user->getLimit()) {
	die(json_encode(Array("code" => "807", "message" => "Not enough money.", "data" => Array())));
}

if (!$reg->requestAuthTwo($domain)) {
	$user->log("[API] [" . $_REQUEST['domain'] . "] AuthInfo2-Anforderung fehlgeschlagen");
	die(json_encode(Array("code" => "808", "message" => "AuthInfo2 request failed.", "data" => Array())));
}

$user->billDomain("<b>" . $_REQUEST['domain'] . "</b><br />AuthInfo2", $price, $_REQUEST['domain']);

$user->log("[API] [" . $_REQUEST['domain'] . "] AuthInfo2 angefordert");

die(json_encode(Array("code" => "100", "message" => "AuthInfo2 request successful.", "data" => Array())));