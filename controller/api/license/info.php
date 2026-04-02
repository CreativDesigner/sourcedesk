<?php
global $db, $CFG, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$key = $_REQUEST['key'];
if (empty($key)) {
    die(json_encode(array("code" => "803", "message" => "No key specified.", "data" => array())));
}

$sql = $db->query("SELECT * FROM client_products WHERE active = 1 AND `key` = '" . $db->real_escape_string($key) . "'");
if ($sql->num_rows != 1) {
    die(json_encode(array("code" => "804", "message" => "Invalid key specified.", "data" => array())));
}

$info = $sql->fetch_object();

$dir = $_REQUEST['dir'];
$ip = ip();
$host = $_SERVER['REMOTE_HOST'];

$psql = $db->query("SELECT * FROM products WHERE ID = " . $info->product);
if ($psql->num_rows != 1) {
    die(json_encode(array("code" => "805", "message" => "Technical error occured.", "data" => array())));
}

$pi = $psql->fetch_object();

if (empty($_REQUEST['dir']) || $_REQUEST['dir'] == "all") {
    die(json_encode(array("code" => "806", "message" => "Directory missing.", "data" => array())));
}

$obj = $provisioning->get()['software'];
$obj->loadOptions($info->ID);
$la = $obj->getOption("licensing_active");

if (!$la || $la == "no") {
    die(json_encode(array("code" => "810", "message" => "Licensing not active.", "data" => array())));
}

$valid = date("Y-m-d", strtotime("+" . $obj->getOption("licensing_cache") . " days"));
$secret = $obj->getOption("licensing_secret");

$cacheKey = $info->product . "|";
$cacheKey .= $host . "|";
$cacheKey .= $ip . "|";
$cacheKey .= $dir . "|";
$cacheKey .= $valid;
$cacheKey .= "|";

if (empty($info->key_dir)) {
    $db->query("UPDATE client_products SET key_dir = '" . $db->real_escape_string($dir) . "' WHERE ID = " . $info->ID);
} else if ($info->key_dir != $dir && $info->key_dir != "all") {
    die(json_encode(array("code" => "807", "message" => "Wrong directory.", "required" => $info->key_dir)));
}

if (empty($info->key_host)) {
    $db->query("UPDATE client_products SET key_host = '" . $db->real_escape_string($host) . "' WHERE ID = " . $info->ID);
} else if ($info->key_host != $host && $info->key_host != "all") {
    die(json_encode(array("code" => "808", "message" => "Wrong host.", "required" => $info->key_host)));
}

if (empty($info->key_ip)) {
    $db->query("UPDATE client_products SET key_ip = '" . $db->real_escape_string($ip) . "' WHERE ID = " . $info->ID);
} else if ($info->key_ip != $ip && $info->key_ip != "all") {
    die(json_encode(array("code" => "809", "message" => "Wrong IP.", "required" => $info->key_ip)));
}

if ($obj->getOption("key_additional")) {
    $cacheKey .= "|" . $obj->getOption("key_additional");
}

$ex = explode("|", $cacheKey);
$hashVal = implode("|", array_slice($ex, 0, 5));

if (count($ex) > 6) {
    $hashVal .= "|" . implode("|", array_slice($ex, 6));
}

$ex[5] = hash("sha512", $secret . $hashVal);
$cacheKey = implode("|", $ex);

if (isset($_POST['data']) && is_array($_POST['data'])) {
    $db->query("UPDATE client_products SET `data` = '" . $db->real_escape_string(encrypt(serialize($_POST['data']))) . "' WHERE ID = " . $info->ID);
}

die(json_encode(array("code" => "100", "message" => "License validation successful.", "data" => array("cacheKey" => $cacheKey))));
