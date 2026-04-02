<?php
global $adminInfo, $telephone;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$ex = explode("|", $adminInfo->call_info);
$module = array_shift($ex);
$data = implode("|", $ex);
$number = str_replace(Array("/", " ", "-"), "", $_GET['number']);

$modules = $telephone->get();
if (!array_key_exists($module, $modules)) {
	die("fail");
}

if (!$modules[$module]->call($number, $data)) {
	die("fail");
}

alog("call", "called", $number);
die("ok");