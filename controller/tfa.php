<?php
global $var, $db, $tfa, $_POST, $user, $session, $maq, $CFG, $_COOKIE, $lang, $f2b;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['TFA']['TITLE'];
$tpl = "tfa";

// User should have two-factor authentication activated
$tcode = trim($user->get()['tfa']);
if ($tcode == "none" || $tcode == "") {
	header('Location: ' . $CFG['PAGEURL']);
	exit;
}

// User submitted a two-factor code
if (isset($_POST['check'])) {
	$code = $_POST['code'];

	if (!$tfa->verifyCode($user->get()['tfa'], $code, 2, null, false) || $db->query("SELECT * FROM client_tfa WHERE user = " . $user->get()['ID'] . " AND code = '" . $db->real_escape_string($_POST['code']) . "'")->num_rows != 0) {
		// Code is wrong or already used
		$var['alert'] = '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $lang['TFA']['WRONG'] . '</div>';

		// Send notification email
		$mtObj = new MailTemplate("Zwei-Faktor-Code fehlerhaft");

		$titlex = $mtObj->getTitle($CFG['LANG']);
		$mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

		if ($user->get()['login_notify']) {
			$maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));
		}

		// Log action
		$user->log("Falschen 2FA-Code eingegeben");

		// Fail2Ban log
		$f2b->failedLogin();
	} else {
		// Code is correct, mark it as used and do not ask for further codes this session
		$db->query("INSERT INTO client_tfa (user, code, time) VALUES (" . $user->get()['ID'] . ", '$code', " . time() . ")");
		$session->set('tfa', true);

		// Send notification email
		$mtObj = new MailTemplate("Zwei-Faktor-Code richtig");

		$titlex = $mtObj->getTitle($CFG['LANG']);
		$mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

		if ($user->get()['login_notify']) {
			$maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));
		}

		// Log action
		$user->log("Richtigen 2FA-Code eingegeben");

		// Save two-factor secret to database provided cookie session, if any cookie is set
		if (isset($_COOKIE['auth'])) {
			$db->query("UPDATE client_cookie SET auth = CONCAT(auth, ':" . $user->get()['tfa'] . "') WHERE string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['auth'])) . "' LIMIT 1");
		}

		// Redirect the user to the start page
		header('Location: ' . $CFG['PAGEURL']);
		exit;
	}
}