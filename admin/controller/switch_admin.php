<?php
global $ari, $CFG, $db, $session;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

// Check admin rights
if ($ari->check(63) || $session->get('admin_session_switch_allowed')) {
	if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0 && $ari->check(1, $_GET['id'])) {
		$session->set('admin_session_switch_allowed', '1');

		$adminInfo = $db->query("SELECT * FROM admins WHERE ID = " . intval($_GET['id']))->fetch_object();
		$tfaCdt = "";
		if (!empty($adminInfo->tfa)) {
			$tfaCdt = ":" . $adminInfo->tfa;
		}

		$session->set("credentials", $adminInfo->username . ":" . $adminInfo->password . $tfaCdt);
		$session->set("admin", "1");
		$session->set("admin_login", "1");

		alog("general", "admin_switched", $adminInfo->username);

		header('Location: ./');
		exit;
	} else {
		$tpl = "error";
	}
} else {
	alog("general", "insufficient_page_rights", "switch_admin");
	$tpl = "error";
}
