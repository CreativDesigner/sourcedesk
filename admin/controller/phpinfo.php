<?php
global $ari, $var, $lang;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['PHPINFO']['TITLE']);
menu("settings");

// Check rights
if (!$ari->check(34)) {

	alog("general", "insufficient_page_rights", "phpinfo");
	$tpl = "error";

} else if (isset($_GET['html'])) {

	phpinfo();
	exit;

} else {

	$tpl = "phpinfo";

}