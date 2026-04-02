<?php
global $db, $CFG, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain']) || $db->query("SELECT 1 FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows != 1) {
    die(json_encode(array("code" => "803", "message" => "Domain not found.", "data" => array())));
}

$info = $db->query("SELECT * FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->fetch_object();

$reg = $db->query("SELECT registrar FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "'")->fetch_object()->registrar;
$regs = DomainHandler::getRegistrars();
if (!array_key_exists($reg, $regs) || !is_object($reg = $regs[$reg]) || !$reg->isActive()) {
    die(json_encode(array("code" => "804", "message" => "Error occured.", "data" => array())));
}
$reg->setUser($user);

$countries = array();
$sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
while ($row = $sql->fetch_object()) {
    $countries[$row->alpha2] = $row->name;
}

$tech = $zone = unserialize($CFG['WHOIS_DATA']);

$hs = array("owner", "admin");
if ($user->get()['domain_contacts']) {
    array_push($hs, "tech", "zone");
}

foreach ($hs as $h) {
    if (!isset($_REQUEST[$h . "_firstname"]) && in_array($h, array("tech", "zone"))) {
        continue;
    }

    if (empty($_REQUEST[$h . "_firstname"])) {
        die(json_encode(array("code" => "805", "message" => "Firstname invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_lastname"])) {
        die(json_encode(array("code" => "805", "message" => "Lastname invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_street"])) {
        die(json_encode(array("code" => "805", "message" => "Street invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_country"]) || !array_key_exists($_REQUEST[$h . "_country"], $countries)) {
        die(json_encode(array("code" => "805", "message" => "Country invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_postcode"])) {
        die(json_encode(array("code" => "805", "message" => "Postcode invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_city"])) {
        die(json_encode(array("code" => "805", "message" => "City invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_telephone"])) {
        die(json_encode(array("code" => "805", "message" => "Telephone invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_telefax"]) && in_array($h, array("tech", "zone"))) {
        die(json_encode(array("code" => "805", "message" => "Telefax invalid ($h).", "data" => array())));
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
        die(json_encode(array("code" => "805", "message" => "Email missing ($h).", "data" => array())));
    }

    if (!filter_var($_REQUEST[$h . "_email"], FILTER_VALIDATE_EMAIL)) {
        die(json_encode(array("code" => "805", "message" => "Email invalid ($h).", "data" => array())));
    }

    $$h = array(
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
        $_REQUEST[$h . "_remarks"],
    );
}

$ns = array();
for ($i = 1; $i <= 2; $i++) {
    if (empty($_REQUEST['ns' . $i])) {
        die(json_encode(array("code" => "806", "message" => "Nameserver missing ($i).", "data" => array())));
    }

    array_push($ns, $_REQUEST['ns' . $i]);
}
for ($i = 3; $i <= 5; $i++) {
    if (empty($_REQUEST['ns' . $i])) {
        continue;
    }

    array_push($ns, $_REQUEST['ns' . $i]);
}

$status = $_REQUEST['transfer_lock'] == "1" ? true : false;
$renew = $_REQUEST['auto_renew'] == "1" ? true : false;
$privacy = $_REQUEST['privacy'] == "1" ? true : false;

if ($ns[0] == $CFG['NS1'] && $ns[1] == $CFG['NS2'] && !DNSHandler::getDriver($_REQUEST['domain'])->getZone($_REQUEST['domain']) && empty($_REQUEST['async'])) {
    if (empty($_REQUEST['ip']) || !filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        die(json_encode(array("code" => "808", "message" => "Invalid IP.", "data" => array())));
    }

    $driver = DNSHandler::getDriver($dom = $_REQUEST['domain']);
    $driver->addZone($dom, $ns = $user->getNS());
    $driver->applyTemplate($dom, $ns, $_REQUEST['ip']);

    $addons->runHook("DnsZoneCreated", [
        "driver" => $driver,
        "domain" => $dom,
        "client" => $user,
    ]);
    sleep(5);
}

if (empty($_REQUEST['async']) && true !== ($r = $reg->changeAll($dom, $owner, $admin, $tech, $zone, $ns, $status, $renew, $privacy))) {
    die(json_encode(array("code" => "809", "message" => "Domain update failed.", "data" => array())));
}

$info = array(
    "domain" => $_REQUEST['domain'],
    "owner" => $owner,
    "admin" => $admin,
    "tech" => $tech,
    "zone" => $zone,
    "ns" => $ns,
);

$lock = $status ? "1" : "0";
$renew = $renew ? "1" : "0";
$privacy = $privacy ? "1" : "0";

$db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info)) . "', changed = " . (empty($_REQUEST['async']) ? '0' : '1') . ", error = '', transfer_lock = $lock, auto_renew = $renew, privacy = $privacy WHERE domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND user = " . $user->get()['ID']);

$user->log("[API] [" . $_REQUEST['domain'] . "] Domain bearbeitet");

die(json_encode(array("code" => "100", "message" => "Domain updated.", "data" => array())));
