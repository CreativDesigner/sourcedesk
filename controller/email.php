<?php
global $db, $CFG, $lang, $raw_cfg, $session, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (isset($pars[0]) && isset($pars[1])) {
	$_GET['id'] = intval($pars[0]);
	$_GET['hash'] = $pars[1];

	if (isset($pars[2])) {
		$_GET['file'] = str_replace(",", ".", $pars[2]);
	}

}

if (isset($_GET['subject']) && isset($_GET['text']) && !empty($_SESSION['admin'])) {
	if (isset($_GET['bg']) && $_GET['bg'] == "0" && file_exists(__DIR__ . "/../templates/email/logo.png")) {
		header("Content-Type: image/png");
		header("Content-Disposition: inline; filename=\"logo.png\"");
		readfile(__DIR__ . "/../templates/email/logo.png");
		exit;
	}

	$title = htmlentities($_GET['subject']);
	$pageurl = $raw_cfg['PAGEURL'];
	$pagetitle = $CFG['PAGENAME'];
	$text = $_GET['text'];

	if (isset($_GET['newsletter'])) {
		$cancel_link = $CFG['PAGEURL'] . "stop_newsletter";
		$cancel_lang = $lang['EMAIL']['CANCEL'] ?: "Abbestellen";
	}

	if (isset($_GET['add'])) {
		$header = new MailTemplate("Header");
		$footer = new MailTemplate("Footer");
		$text = $header->getContent($CFG['LANG']) . "<br /><br />" . $text . "<br /><br />" . $footer->getContent($CFG['LANG']);
	}

	$browser_link = $raw_cfg['PAGEURL'] . "email?subject=x&text=x";
	$browser_lang = $lang['EMAIL']['WEBVERSION'] ?: "Webversion";
	$login_link = $raw_cfg['PAGEURL'] . "login";
	$login_lang = $lang['EMAIL']['LOGIN'] ?: "Einloggen";

	require __DIR__ . "/../templates/email/html.php";

	// Exit the script to prevent Smarty exception
	exit;
}

if (!isset($_GET['id']) || !isset($_GET['hash']) || $_GET['hash'] != substr(hash("sha512", "email_view" . $_GET['id'] . $CFG['HASH']), 0, 10) || !is_object($sql = $db->query("SELECT * FROM client_mails WHERE ID = " . intval($_GET['id']))) || $sql->num_rows != 1) {
	die($lang['EMAIL']['NOT_FOUND']);
}

$info = $sql->fetch_object();

if (isset($_GET['bg']) && is_numeric($_GET['bg'])) {
	if ($session->get('admin_login') === false && $session->get('admin') === false) {
		$db->query("UPDATE client_mails SET seen = 1 WHERE ID = " . intval($_GET['id']) . " LIMIT 1");
		if ($info->user > 0) {
			$uI = new User($info->user, "ID");
			$uI->log("E-Mail \"" . $info->subject . "\" gelesen");
		}
	}

	if ($_GET['bg'] == "0" && file_exists(__DIR__ . "/../templates/email/logo.png")) {
		header("Content-Type: image/png");
		header("Content-Disposition: inline; filename=\"logo.png\"");
		readfile(__DIR__ . "/../templates/email/logo.png");
		exit;
	}

	if (file_exists(__DIR__ . "/../templates/email/bg" . intval($_GET['bg']) . ".jpg")) {
		header("Content-Type: image/jpeg");
		header("Content-Disposition: inline; filename=\"bg" . intval($_GET['bg']) . ".jpg\"");
		readfile(__DIR__ . "/../templates/email/bg" . intval($_GET['bg']) . ".jpg");
	}
	exit;
}

if (isset($_GET['file'])) {
	// Check if file exists
	if (!is_dir(__DIR__ . "/../files/emails/" . basename($_GET['id'])) || substr(basename($_GET['file']), 0, 1) == "." || !file_exists(__DIR__ . "/../files/emails/" . basename($_GET['id']) . "/" . basename($_GET['file']))) {
		die($lang['EMAIL']['FILE_MISSING']);
	}

	// Get the file from download directory
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"" . basename($_GET['file']) . "\"");
	readfile(__DIR__ . '/../files/emails/' . basename($_GET['id']) . "/" . basename($_GET['file']));

	// Exit the script to prevent Smarty exception
	exit;

}

echo MailQueue::getHTMLBody($info, true);

// Exit the script to prevent Smarty exception
exit;
?>
