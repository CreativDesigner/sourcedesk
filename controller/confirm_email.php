<?php
global $var, $db, $user, $session, $CFG, $maq, $lang, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if ($var['ca_disabled']) {

	$title = $lang['ERROR']['TITLE'];
	$tpl = "error";
	$var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

	$tpl = "confirm_email";
	$title = $lang['MAIL_CONFIRM']['TITLE'];

	$_GET['id'] = isset($pars[0]) ? $pars[0] : 0;
	$_GET['hash'] = isset($pars[1]) ? $pars[1] : "";

	// Try to change the email
	try {

		// Gather information about the change request from the database
		$sql = $db->query("SELECT * FROM client_mailchanges WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' AND hash = '" . $db->real_escape_string($_GET['hash']) . "' LIMIT 1");
		if ($sql->num_rows != 1) {
			throw new Exception();
		}

		$requestInfo = $sql->fetch_object();

		// Get information about the assigned user account
		$user = new User($db->query("SELECT mail FROM clients WHERE ID = " . $requestInfo->user . " LIMIT 1")->fetch_object()->mail);
		$myLang = isset($user->get()['language']) && trim($user->get()['language']) != "" && isset($languages[$user->get()['language']]) ? $user->get()['language'] : $CFG['LANG'];

		if (isset($_GET['cancel']) && $_GET['cancel'] == 1) {

			// User wants to cancel the request
			if (!$db->query("DELETE FROM client_mailchanges WHERE ID = " . $requestInfo->ID . " LIMIT 1")) {
				throw new Exception();
			}

			$var['msg'] = $lang['MAIL_CONFIRM']['CANCELLED'];

			// Send a mail
			$mtObj = new MailTemplate("E-Mailänderung storniert");

			$titlex = $mtObj->getTitle($myLang);
			$mail = $mtObj->getMail($myLang, $user->get()['name']);

			$maq->enqueue([], $mtObj, $requestInfo->old, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($myLang));

			$user->log("E-Mailänderung storniert");

		} else {
			// User wants to confirm the request

			// Check if email is not used already
			$muSql = $db->query("SELECT ID FROM clients WHERE ID != " . $requestInfo->user . " AND mail = '" . $requestInfo->new . "'");
			if ($muSql->num_rows > 0) {
				throw new Exception();
			}

			// Update email
			$oldAddr = $user->get()['mail'];
			$arr = Array("mail" => $requestInfo->new);
			$user->set($arr);
			$user->saveChanges();

			// If user is logged in, set new mail address to session
			if ($var['logged_in'] == 1 && $user->get()['ID'] == $requestInfo->user) {
				$session->set('mail', $requestInfo->new);
			}

			// Delete the request from the database
			$db->query("DELETE FROM client_mailchanges WHERE ID = " . $requestInfo->ID . " LIMIT 1");
			$var['msg'] = $lang['CONFIRM_MAIL']['SUCCESS'];

			// Send a mail to the old and the new address
			$mtObj = new MailTemplate("E-Mailadresse geändert");

			$titlex = $mtObj->getTitle($myLang);
			$mail = $mtObj->getMail($myLang, $user->get()['name']);

			$maq->enqueue([
				"old" => $oldAddr,
				"new" => $requestInfo->new,
			], $mtObj, $requestInfo->new, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($myLang));

			$maq->enqueue([
				"old" => $oldAddr,
				"new" => $requestInfo->new,
			], $mtObj, $requestInfo->old, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($myLang));

			$user->log("E-Mailadresse geändert auf " . $requestInfo->new);

		}

		$db->query("UPDATE client_mails SET `user` = " . $user->get()['ID'] . " WHERE `user` = 0 AND `recipient` = '" . $requestInfo->new . "'");

	} catch (Exception $ex) {
		$var['msg'] = "<font color=\"red\">" . $lang['MAIL_CONFIRM']['ERROR'] . "</font>";
	}

}
