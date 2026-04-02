<?php
global $var, $db, $CFG, $adminInfo;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$tpl = "index";

$var['widgets'] = $w = Widgets::get();

if (isset($_POST['action']) && $_POST['action'] == "widgets") {
	$mw = unserialize(ltrim($adminInfo->widgets, "1|"));
	$m = Array();
	if (is_array($_POST['widgets'])) {
		foreach ($_POST['widgets'] as $k) {
			if (array_key_exists($k, $w)) {
				$m[$k] = array_key_exists($k, $mw) ? $mw[$k] : true;
			}
		}
	}

	$adminInfo->widgets = $m = serialize($m);
	if (!empty($_POST['twocols'])) {
		$adminInfo->widgets = $m = "1|" . $adminInfo->widgets;
	}

	$db->query("UPDATE admins SET widgets = '" . $db->real_escape_string($m) . "' WHERE ID = " . $adminInfo->ID);
	alog("general", "widgets_saved");
}

$var['myWidgets'] = Array();
$var['hiddenWidgets'] = Array();
$var['twocols'] = false;

$tc = "";
if (substr($adminInfo->widgets, 0, 2) == "1|") {
	$adminInfo->widgets = substr($adminInfo->widgets, 2);
	$var['twocols'] = true;
	$tc = "1|";
}

$w = unserialize($adminInfo->widgets);

if (isset($_POST['hide']) && array_key_exists($_POST['hide'], $w)) {
	$w[$_POST['hide']] = false;
	$db->query("UPDATE admins SET widgets = '$tc" . $db->real_escape_string(serialize($w)) . "' WHERE ID = " . $adminInfo->ID);
	alog("general", "widget_hidden", $w);
	exit;
}

if (isset($_POST['show']) && array_key_exists($_POST['show'], $w)) {
	$w[$_POST['show']] = true;
	$db->query("UPDATE admins SET widgets = '$tc" . $db->real_escape_string(serialize($w)) . "' WHERE ID = " . $adminInfo->ID);
	alog("general", "widget_shown", $w);
	exit;
}

if (is_array($w)) {
	foreach ($w as $k => $h) {
		array_push($var['myWidgets'], $k);
		if (!$h) {
			array_push($var['hiddenWidgets'], $k);
		}

	}
}