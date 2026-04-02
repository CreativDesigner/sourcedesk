<?php
// Global some variables for security reasons
global $db, $var, $CFG, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$tpl = "stop_newsletter";
$title = $lang['STOP_NEWSLETTER']['TITLE'];

try {
	if (empty($pars[0]) || empty($pars[1])) {
		throw new Exception(2);
	}

	$sql = $db->query("SELECT * FROM clients WHERE locked = 0 AND newsletter != '' AND ID = " . intval($pars[0]));
	if ($sql->num_rows != 1) {
		throw new Exception(2);
	}

	$user = $sql->fetch_object();
	$var['newsuser'] = (array) $user;

	// Fill array with all users newsletter categories
	$currentLists = explode("|", $user->newsletter);
	$sql = $db->query("SELECT ID, name FROM newsletter_categories ORDER BY name ASC");
	$var['nl'] = Array();
	while ($row = $sql->fetch_object()) {
		if (in_array($row->ID, $currentLists)) {
			$var['nl'][$row->ID] = $row->name;
		}
	}

	$hash = substr(hash("sha512", $CFG['HASH'] . $user->ID . $user->mail), 0, 10);
	if ($pars[1] != $hash) {
		throw new Exception(2);
	}

	if (empty($_POST['stop']) || empty($_POST['nl']) || !is_array($_POST['nl']) || count($_POST['nl']) == 0) {
		throw new Exception(0);
	}

	foreach ($_POST['nl'] as $id => $null) {
		unset($var['nl'][$id]);
	}

	$userInstance = new User($user->mail);
	$userInstance->set(Array("newsletter" => implode("|", array_keys($var['nl']))));
	$userInstance->saveChanges();
	$userInstance->log("Newsletter abbestellt (Per Link)");

	throw new Exception(1);
} catch (Exception $ex) {
	$var['step'] = $ex->getMessage();
}

?>