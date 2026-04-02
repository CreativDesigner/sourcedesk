<?php
global $ari;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (!empty($_GET['user'])) {
	$u = User::getInstance($_GET['user'], "ID");
}

if (!$ari->check(10) || empty($u)) {
	$tpl = "error";
} else {
	alog("bdsg", "generated", $u->get()['ID']);
	$u->getBDSG()->Output("BDSG-Auskunft-" . str_replace(" ", "-", $u->get()['name']), "I");
}
