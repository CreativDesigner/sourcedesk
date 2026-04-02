<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Cronjob for getting Geo informations for IPs
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

// Set limit per cronjob call
$limit = 100;

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Looking up client IP addresses\n", FILE_APPEND);

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force temporarily because of server problems

$sql = $db->query("SELECT ip FROM ip_logs WHERE `country` = '' OR `city` = '' AND `ip` != 'System' GROUP BY `ip` ORDER BY ID ASC LIMIT $limit");
while ($row = $sql->fetch_object()) {
    curl_setopt($ch, CURLOPT_URL, "http://free.ipwhois.io/json/" . urlencode($row->ip));
    $res = curl_exec($ch);
    if (!$res) {
        break;
    }

    $info = json_decode($res, true);
    if (!$info) {
        continue;
    }

    $country = $city = "no";
    if (!empty($info['country'])) {
        $country = $db->real_escape_string($info['country']);
    }

    if (!empty($info['city'])) {
        $city = $db->real_escape_string($info['city']);
    }

    $db->query("UPDATE ip_logs SET `country` = '$country', `city` = '$city' WHERE `ip` = '" . $db->real_escape_string($row->ip) . "'");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Looking up visitor IP addresses\n", FILE_APPEND);

$sql = $db->query("SELECT ip FROM visits WHERE `country` = '' GROUP BY ip ORDER BY ID ASC LIMIT $limit");
while ($row = $sql->fetch_object()) {
    curl_setopt($ch, CURLOPT_URL, "http://free.ipwhois.io/json/" . urlencode($row->ip));
    $res = curl_exec($ch);
    if (!$res) {
        break;
    }

    $info = json_decode($res, true);
    if (!$info) {
        continue;
    }

    $country = "-";
    if (!empty($info['country'])) {
        $country = $db->real_escape_string($info['country']);
    }

    $db->query("UPDATE visits SET `country` = '$country' WHERE `ip` = '" . $db->real_escape_string($row->ip) . "'");
}

curl_close($ch);

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
