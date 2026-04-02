<?php
global $lang, $var, $dfo, $f2b, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['LOCKED']['TITLE'];
$tpl = "locked";

$var['ip'] = $f2b->clientIP();

if (false !== $f2b->getUntil($f2b->clientIP()) && $f2b->getFailed($f2b->clientIP()) >= $CFG['FAIL2BAN_FAILED'] && $CFG['FAIL2BAN_ACTIVE']) {
	$var['until'] = $dfo->format($f2b->getUntil($f2b->clientIP()));
} else if (false !== $f2b->getBlackList($f2b->clientIP())) {
	$var['reason'] = $f2b->getBlackList($f2b->clientIP())->reason;
} else {
	header('Location: ' . $CFG['PAGEURL']);
	exit;
}

$var['server'] = $dfo->format(time());