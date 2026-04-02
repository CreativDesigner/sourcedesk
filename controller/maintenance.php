<?php
// Global @var lang for security reasons
global $lang, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['MAINTENANCE']['TITLE'];
$tpl = "maintenance";

$reasons = unserialize($CFG['MAINTENANCE_MSG']);
if (false !== $reasons && is_array($reasons) && !empty($reasons[$CFG['LANG']])) {
	$var['reason'] = nl2br($reasons[$CFG['LANG']]);
}