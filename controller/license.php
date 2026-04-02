<?php
// Global some variables for security reasons
global $_GET, $db, $CFG, $var, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$_GET['type'] = isset($pars[0]) ? $pars[0] : "e";

// Select all license texts from database
$t = Array();
$sql = $db->query("SELECT * FROM license_texts");
while ($r = $sql->fetch_object()) {
	$t[$r->type] = Array("text" => $r->text, "title" => $r->name);
}

// If license type is not defined, set it to single
if (!isset($t[$_GET['type']])) {
	$_GET['type'] = "e";
}

// Set template variables
$var['licenseName'] = unserialize($t[$_GET['type']]['title'])[$CFG['LANG']];
$var['licenseText'] = nl2br(unserialize($t[$_GET['type']]['text'])[$CFG['LANG']]);

$title = $var['licenseName'];
$tpl = "license";