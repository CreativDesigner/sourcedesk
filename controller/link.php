<?php
global $db, $CFG, $var, $pars, $lang;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

// Check if the link exists
if (empty($pars[0]) || !is_object($sql = $db->query("SELECT target FROM cms_links WHERE slug = '" . $db->real_escape_string($pars[0]) . "' AND status = 1")) || $sql->num_rows != 1) {
	$title = $lang['ERROR']['TITLE'];
	$tpl = "error";
} else {
	// Count link call
	$db->query("UPDATE cms_links SET calls = calls + 1 WHERE slug = '" . $db->real_escape_string($pars[0]) . "' LIMIT 1");

	// Redirect
	header('Location: ' . $sql->fetch_object()->target);
	exit;
}