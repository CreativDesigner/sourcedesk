<?php
// Global some variables for security reasons
global $var, $db, $_GET, $user, $lang, $CFG, $raw_cfg;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

User::status();

$tpl = "mails";
$title = $lang['NAV']['MAILS'];

// Select mails from database
$mails = Array();
$sql = $db->query("SELECT * FROM client_mails WHERE sent >= time AND user = " . $user->get()['ID'] . " AND resend = 0 ORDER BY sent DESC");
if ($sql->num_rows > 0) {
	while ($mail = $sql->fetch_array()) {
		$mail['url'] = $raw_cfg['PAGEURL'] . "email/" . $mail['ID'] . "/" . substr(hash("sha512", "email_view" . $mail['ID'] . $CFG['HASH']), 0, 10);
		$mails[$mail['ID']] = $mail;
	}
}
$var['mails'] = $mails;