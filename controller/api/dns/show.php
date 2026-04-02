<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (empty($_REQUEST['domain']) || $db->query("SELECT 1 FROM domains WHERE user = " . $user->get()['ID'] . " AND domain = '" . $db->real_escape_string($_REQUEST['domain']) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows != 1) {
    die(json_encode(array("code" => "803", "message" => "Domain not found.", "data" => array())));
}

$dns = DNSHandler::getDriver($_REQUEST['domain']);
if (!($z = $dns->getZone($_REQUEST['domain']))) {
    die(json_encode(array("code" => "804", "message" => "Zone not found.", "data" => array())));
}

$records = array();
foreach ($z as $i => $r) {
    if ($r[0] == "_domainconnect") {
        continue;
    }

    $records[$i] = array("name" => $r[0], "type" => $r[1], "content" => $r[2], "ttl" => $r[3], "priority" => $r[4]);
}

die(json_encode(array("code" => "100", "message" => "Zone fetched.", "data" => $records)));
