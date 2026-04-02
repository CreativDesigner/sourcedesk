<?php
global $ari, $var, $lang, $adminInfo, $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['TOP']['SETTINGS']);
$tpl = "my_settings";

if (isset($_POST['per_page'])) {
    $pp = max(10, intval($_POST['per_page']));
    $hs = (int) !empty($_POST['hide_sidebar']);
    $om = (int) !empty($_POST['open_menu']);
    $nt = (int) !empty($_POST['next_ticket']);

    $db->query("UPDATE admins SET per_page = $pp, hide_sidebar = $hs, open_menu = $om, next_ticket = $nt WHERE ID = {$adminInfo->ID}");
    $var['suc'] = 1;
}

$adminInfo = $db->query("SELECT * FROM admins WHERE ID = {$adminInfo->ID} LIMIT 1")->fetch_object();

$var['hide_sidebar'] = boolval($adminInfo->hide_sidebar);
$var['open_menu'] = boolval($adminInfo->open_menu);
$var['next_ticket'] = boolval($adminInfo->next_ticket);
$var['per_page'] = intval($adminInfo->per_page);