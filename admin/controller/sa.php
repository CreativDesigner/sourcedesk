<?php
global $db, $CFG, $adminInfo, $sec;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$ex = explode("/", "http" . ($_SERVER['HTTPS'] ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
do {
	$e = array_pop($ex);
	if ($e == "admin") {
		break;
	}

} while (count($ex) > 0);
$from = implode("/", $ex);

$sat = $sec->generatePassword(64, false, "lud");
$db->query("UPDATE admins SET sat = '" . $db->real_escape_string($sat) . "' WHERE ID = " . $adminInfo->ID);

alog("general", "sa", $_GET['to']);
$url = rtrim($_GET['to'], "/") . "/admin/?sa_user=" . urlencode($_GET['user']) . "&sa_user2=" . urlencode($adminInfo->username) . "&sa_from=" . urlencode($from) . "&sa_token=" . urlencode($sat);
header('Location: ' . $url);
exit;