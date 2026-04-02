<?php
// Global some variables for security reasons
global $var, $db, $session, $sec, $CFG, $maq, $lang;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$sql = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($_REQUEST['u']) . "' AND reset_hash != '' AND reset_hash = '" . $db->real_escape_string($_REQUEST['h']) . "' AND locked = 0 AND last_pwreset > " . (time() - 7200));

// User should exist and hash should be correct (there cannot a user be logged in)
if ($var['logged_in'] == 1 || $sql->num_rows == 0) {

	$tpl = "error";
	$title = $lang['ERROR']['TITLE'];

} else if ($var['ca_disabled']) {

	$title = $lang['ERROR']['TITLE'];
	$tpl = "error";
	$var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

	$userInfo = $sql->fetch_object();

	// Check if the password change was requested already
	if (isset($_POST['change'])) {
		try {
			// Check if both passwords are the same
			if (!isset($_POST['newpw']) || !isset($_POST['newpw2']) || $_POST['newpw'] != $_POST['newpw2']) {
				throw new Exception($lang['RESET']['PASSWORD_NS']);
			}

			// Check if password is at least 8 characters long
			if (strlen($_POST['newpw']) < 8) {
				throw new Exception($lang['RESET']['PASSWORD_TS']);
			}

			$userInstance = new User($userInfo->mail);

			// Set new password and init session
			$salt = $sec->generateSalt();
			$userInstance->set(Array("salt" => $salt, "pwd" => $sec->hash($_POST['newpw'], $salt, $_POST['password_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING'] == 1), "reset_hash" => '', "failed_login" => 0));
			$session->set('mail', $userInfo->mail);
			if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
				$session->set('pwd', md5($userInstance->get()['pwd']));
			} else {
				$session->set('pwd', $userInstance->get()['pwd']);
			}

			// Give user information to template
			$var['logged_in'] = 1;
			$var['user'] = $userInstance->get();
			$cart = new Cart($userInstance->get()['ID']);
			$cart->importSession();
			$var['cart'] = $cart->get();
			$var['cart_count'] = $cart->count();

			// Set step to done
			$var['step'] = 2;

			// Send a notification email to the user
			$mtObj = new MailTemplate("Passwort zurückgesetzt");

			$titlex = $mtObj->getTitle($CFG['LANG']);
			$mail = $mtObj->getMail($CFG['LANG'], $userInstance->get()['name']);

			$maq->enqueue([], $mtObj, $userInstance->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userInstance->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

			// Log the reset
			$userInstance->log("Passwort zurückgesetzt");
		} catch (Exception $ex) {
			// Give out the error in template
			$var['step'] = 1;
			$var['error'] = "<div class=\"alert alert-danger\"><b>" . $lang['GENERAL']['ERROR'] . "</b> " . $ex->getMessage() . "</div>";
		}

	} else {

		// Set step to begin
		$var['step'] = 1;

	}

	$var['ruser'] = (array) $userInfo;

	$title = $lang['RESET']['TITLE'];
	$tpl = "reset_password";
}

?>