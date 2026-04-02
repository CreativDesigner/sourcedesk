<?php
global $db, $CFG, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$domain = strtolower($_REQUEST['domain']);
if (empty($domain)) {
    die(json_encode(array("code" => "803", "message" => "No domain specified.", "data" => array())));
}

$ex = explode(".", $domain);
$sld = array_shift($ex);
$tld = implode(".", $ex);

if (count($ex) < 1) {
    die(json_encode(array("code" => "804", "message" => "Invalid domain specified.", "data" => array())));
}

$reg = DomainHandler::getRegistrarByTld($tld);
if (!$reg || !$reg->isActive()) {
    die(json_encode(array("code" => "805", "message" => "TLD not available.", "data" => array())));
}

if (null === ($a = $reg->availibilityStatus($domain))) {
    die(json_encode(array("code" => "806", "message" => "Domain status not available.", "data" => array())));
}

if (false === ($a = $reg->availibilityStatus($domain))) {
    die(json_encode(array("code" => "807", "message" => "Domain not available.", "data" => array())));
}

$price = $user->addTax($user->getDomainPrice($tld, "register"));
if ($price > $user->getLimit()) {
    die(json_encode(array("code" => "808", "message" => "Not enough money.", "data" => array())));
}

$countries = array();
$sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
while ($row = $sql->fetch_object()) {
    $countries[$row->alpha2] = $row->name;
}

$tech = unserialize($CFG['WHOIS_DATA']);
$zone = unserialize($CFG['WHOIS_DATA']);

$hs = array("owner", "admin");
if ($user->get()['domain_contacts']) {
    array_push($hs, "tech", "zone");
}

foreach ($hs as $h) {
    if (empty($_REQUEST[$h . "_firstname"]) && in_array($h, array("tech", "zone"))) {
        continue;
    }

    if (empty($_REQUEST[$h . "_firstname"])) {
        die(json_encode(array("code" => "809", "message" => "Firstname invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_lastname"])) {
        die(json_encode(array("code" => "809", "message" => "Lastname invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_street"])) {
        die(json_encode(array("code" => "809", "message" => "Street invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_country"]) || !array_key_exists($_REQUEST[$h . "_country"], $countries)) {
        die(json_encode(array("code" => "809", "message" => "Country invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_postcode"])) {
        die(json_encode(array("code" => "809", "message" => "Postcode invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_city"])) {
        die(json_encode(array("code" => "809", "message" => "City invalid ($h).", "data" => array())));
    }

    if (empty($_REQUEST[$h . "_telephone"])) {
        die(json_encode(array("code" => "809", "message" => "Telephone invalid ($h).", "data" => array())));
    }

    if (in_array($h, array("tech", "zone")) && empty($_REQUEST[$h . "_telefax"])) {
        die(json_encode(array("code" => "809", "message" => "Telefax invalid ($h).", "data" => array())));
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
        die(json_encode(array("code" => "809", "message" => "Email missing ($h).", "data" => array())));
    }

    if (!filter_var($_REQUEST[$h . "_email"], FILTER_VALIDATE_EMAIL)) {
        die(json_encode(array("code" => "809", "message" => "Email invalid ($h).", "data" => array())));
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
        die(json_encode(array("code" => "810", "message" => "Nameserver missing ($i).", "data" => array())));
    }

    array_push($ns, $_REQUEST['ns' . $i]);
}
for ($i = 3; $i <= 5; $i++) {
    if (empty($_REQUEST['ns' . $i])) {
        continue;
    }

    array_push($ns, $_REQUEST['ns' . $i]);
}

$provns = false;
$uns = $user->getNS();
if ($ns[0] == $CFG['NS1'] && $ns[1] == $CFG['NS2']) {
    $provns = true;
} else if ($ns[0] == $uns[0] && $ns[1] == $uns[1]) {
    $provns = true;
}

if ($provns) {
    if (empty($_REQUEST['ip'])) {
        $_REQUEST['ip'] = $CFG['DEFAULT_IP'];
    }

    if (!filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        die(json_encode(array("code" => "811", "message" => "Invalid IP.", "data" => array())));
    }

    if (empty($_REQUEST['async']) && !$reg->delayDns) {
        $driver = DNSHandler::getDriver(strtolower($dom = $_REQUEST['domain']));
        $driver->addZone(strtolower($_REQUEST['domain']), $ns = $user->getNS());
        $driver->applyTemplate($dom, $ns, $_REQUEST['ip'], filter_var($_REQUEST['ip6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $_REQUEST['ip6'] : false);
        $addons->runHook("DnsZoneCreated", [
            "driver" => DNSHandler::getDriver($_REQUEST['domain']),
            "domain" => $_REQUEST['domain'],
            "client" => $user,
        ]);
        sleep(5);
    }
}

if ($db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string($domain) . "' AND status = 'REG_WAITING'")->num_rows > 0) {
    die(json_encode(array("code" => "812", "message" => "Domain registration already in system.", "data" => array())));
}

$db->query("DELETE FROM domains WHERE domain = '" . $db->real_escape_string($domain) . "' AND user = " . $user->get()['ID'] . " AND status NOT IN ('KK_OK', 'REG_OK')");

$privacy = $_REQUEST['privacy'] == "1" ? true : false;

$reg->setUser($user);
if (empty($_REQUEST['async']) && true !== ($r = $reg->registerDomain($domain, $owner, $admin, $tech, $zone, $ns, $privacy))) {
    die(json_encode(array("code" => "813", "message" => "Domain registration failed.", "data" => array("detail" => $r))));
}

if (empty($_REQUEST['async']) && $reg->delayDns) {
    $driver = DNSHandler::getDriver(strtolower($_REQUEST['domain']));
    $driver->addZone($dom = strtolower($_REQUEST['domain']), $ns = $user->getNS());
    $driver->applyTemplate($dom, $ns, $_REQUEST['ip'], (filter_var($_REQUEST['ip6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $_REQUEST['ip6'] : false));
    $addons->runHook("DnsZoneCreated", [
        "driver" => DNSHandler::getDriver($_REQUEST['domain']),
        "domain" => $_REQUEST['domain'],
        "client" => $user,
    ]);
}

if ($provns) {
    $ns = [$_REQUEST['ip']];
    if (filter_var($_REQUEST['ip6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        array_push($ns, $_REQUEST['ip6']);
    }

}

$info = array(
    "domain" => $domain,
    "owner" => $owner,
    "admin" => $admin,
    "tech" => $tech,
    "zone" => $zone,
    "ns" => $ns,
);
$recurring = ($user->getDomainPrice($tld, "renew"));
$trade = ($user->getDomainPrice($tld, "trade"));
$pp = $user->getDomainPrice($tld, "privacy");

$privacy = $privacy ? "1" : "0";
$db->query("INSERT INTO domains (`user`, `domain`, `reg_info`, `recurring`, `status`, `registrar`, `trade`, `created`, `privacy`, `privacy_price`, `expiration`) VALUES (" . $user->get()['ID'] . ", '" . $db->real_escape_string($info['domain']) . "', '" . $db->real_escape_string(serialize($info)) . "', $recurring, '" . (empty($_REQUEST['async']) ? 'REG_OK' : 'REG_WAITING') . "', '" . $db->real_escape_string($reg->getShort()) . "', $trade, '" . date("Y-m-d") . "', $privacy, $pp, '" . date("Y-m-d", strtotime("+1 year")) . "')");

$period = 1;
$periodSql = $db->query("SELECT `period` FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
if ($periodSql->num_rows) {
    $period = max(1, intval($periodSql->fetch_object()->period));
}

$from = $dfo->format(time(), false, false);
$to = $dfo->format(strtotime("+$period year, -1 day") , false, false);

$user->billDomain("<b>" . strtolower($_REQUEST['domain']) . "</b><br />REG<br /><br />$from - $to", $price, strtolower($_REQUEST['domain']));

$user->log("[API] [" . strtolower($_REQUEST['domain']) . "] Domain registriert");

die(json_encode(array("code" => "100", "message" => "Domain registration successful.", "data" => array())));
