<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain']) || $db->query("SELECT 1 FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows != 1) {
	die(json_encode(Array("code" => "803", "message" => "Domain not found.", "data" => Array())));
}

$price = $user->addTax($db->query("SELECT trade FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "'")->fetch_object()->trade);
if ($price <= 0) {
	die(json_encode(Array("code" => "806", "message" => "Trade not possible for this TLD.", "data" => Array())));
}

if ($price > $user->getLimit()) {
	die(json_encode(Array("code" => "807", "message" => "Not enough money.", "data" => Array())));
}

$reg = $db->query("SELECT registrar FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "'")->fetch_object()->registrar;
$regs = DomainHandler::getRegistrars();
if (!array_key_exists($reg, $regs) || !is_object($reg = $regs[$reg]) || !$reg->isActive()) {
	die(json_encode(Array("code" => "804", "message" => "Error occured.", "data" => Array())));
}
$reg->setUser($user);

$countries = Array();
$sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
while ($row = $sql->fetch_object()) {
	$countries[$row->alpha2] = $row->name;
}

$hs = Array("owner", "admin");
if ($user->get()['domain_contacts']) {
	array_push($hs, "tech", "zone");
}

foreach ($hs as $h) {
	if (!isset($_REQUEST[$h . "_firstname"]) && in_array($h, Array("tech", "zone"))) {
		continue;
	}

	if (empty($_REQUEST[$h . "_firstname"])) {
		die(json_encode(Array("code" => "805", "message" => "Firstname invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_lastname"])) {
		die(json_encode(Array("code" => "805", "message" => "Lastname invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_street"])) {
		die(json_encode(Array("code" => "805", "message" => "Street invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_country"]) || !array_key_exists($_REQUEST[$h . "_country"], $countries)) {
		die(json_encode(Array("code" => "805", "message" => "Country invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_postcode"])) {
		die(json_encode(Array("code" => "805", "message" => "Postcode invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_city"])) {
		die(json_encode(Array("code" => "805", "message" => "City invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_telephone"])) {
		die(json_encode(Array("code" => "805", "message" => "Telephone invalid ($h).", "data" => Array())));
	}

	if (empty($_REQUEST[$h . "_telefax"])) {
		die(json_encode(Array("code" => "805", "message" => "Telefax invalid ($h).", "data" => Array())));
	}

	$_REQUEST[$h . "_telephone"] = str_replace(".", "", $_REQUEST[$h . "_telephone"]);
	if (substr($_REQUEST[$h . "_telephone"], 0, 2) == "00") {
		$_REQUEST[$h . "_telephone"] = "+" . ltrim($_REQUEST[$h . "_telephone"], "0");
		$_REQUEST[$h . "_telephone"] = substr($_REQUEST[$h . "_telephone"], 0, 3) . "." . substr($_REQUEST[$h . "_telephone"], 3);
	} else if (substr($_REQUEST[$h . "_telephone"], 0, 1) == "0") {
		$_REQUEST[$h . "_telephone"] = "+49." . ltrim($_REQUEST[$h . "_telephone"], "0");
	} else if (substr($_REQUEST[$h . "_telephone"], 0, 1) == "+") {
		$_REQUEST[$h . "_telephone"] = substr($_REQUEST[$h . "_telephone"], 0, 3) . "." . substr($_REQUEST[$h . "_telephone"], 3);
	} else {
		$_REQUEST[$h . "_telephone"] = "+49.0" . $_REQUEST[$h . "_telephone"];
	}

	if (!empty($_REQUEST[$h . "_telefax"])) {
		$_REQUEST[$h . "_telefax"] = str_replace(".", "", $_REQUEST[$h . "_telefax"]);
		if (substr($_REQUEST[$h . "_telefax"], 0, 2) == "00") {
			$_REQUEST[$h . "_telefax"] = "+" . ltrim($_REQUEST[$h . "_telefax"], "0");
			$_REQUEST[$h . "_telefax"] = substr($_REQUEST[$h . "_telefax"], 0, 3) . "." . substr($_REQUEST[$h . "_telefax"], 3);
		} else if (substr($_REQUEST[$h . "_telefax"], 0, 1) == "0") {
			$_REQUEST[$h . "_telefax"] = "+49." . ltrim($_REQUEST[$h . "_telefax"], "0");
		} else if (substr($_REQUEST[$h . "_telefax"], 0, 1) == "+") {
			$_REQUEST[$h . "_telefax"] = substr($_REQUEST[$h . "_telefax"], 0, 3) . "." . substr($_REQUEST[$h . "_telefax"], 3);
		} else {
			$_REQUEST[$h . "_telefax"] = "+49.0" . $_REQUEST[$h . "_telefax"];
		}
	}

	if (empty($_REQUEST[$h . "_email"])) {
		die(json_encode(Array("code" => "805", "message" => "Email missing ($h).", "data" => Array())));
	}

	if (!filter_var($_REQUEST[$h . "_email"], FILTER_VALIDATE_EMAIL)) {
		die(json_encode(Array("code" => "805", "message" => "Email invalid ($h).", "data" => Array())));
	}

	$$h = Array(
		$_REQUEST[$h . "_firstname"],
		$_REQUEST[$h . "_lastname"],
		$_REQUEST[$h . "_company"],
		$_REQUEST[$h . "_street"],
		$_REQUEST[$h . "_country"],
		$_REQUEST[$h . "_postcode"],
		$_REQUEST[$h . "_city"],
		$_REQUEST[$h . "_telephone"],
		$_REQUEST[$h . "_telefax"],
		$_REQUEST[$h . "_email"],
	);
}

if (empty($_REQUEST['async']) && true !== $reg->trade($_REQUEST['domain'], $owner, $admin, $tech, $zone)) {
	die(json_encode(Array("code" => "808", "message" => "Trade failed.", "data" => Array())));
}

$info = unserialize($db->query("SELECT reg_info FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->fetch_object()->reg_info);

$info['owner'] = $owner;
$info['admin'] = $admin;
$info['tech'] = $tech;
$info['zone'] = $zone;

$db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info)) . "', trade_waiting = " . (empty($_REQUEST['async']) ? '0' : '1') . " WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')");

$user->billDomain("<b>" . $_REQUEST['domain'] . "</b><br />TRADE", $price, $_REQUEST['domain']);

$user->log("[API] [" . $_REQUEST['domain'] . "] Kostenpflichtiger Inhaberwechsel");

die(json_encode(Array("code" => "100", "message" => "Trade successful.", "data" => Array())));