<?php
global $ari, $lang, $CFG, $db, $var, $dfo, $_POST, $val, $_GET, $languages;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

menu("customers");
title($lang['BIRTHDAYS']['TITLE']);

// Check admin rights
if ($ari->check(57)) {
	$tpl = "birthdays";
	$var["tab"] = isset($_GET['tab']) ? $_GET['tab'] : "soon";

	if ($var["tab"] == "soon") {
		$sql = "SELECT * FROM clients WHERE DATE_FORMAT(birthday, '%m-%d') > '" . date("m-d") . "'";
		$table = new Table($sql, []);
		$var['th'] = $table->getHeader();
		$var['tf'] = $table->getFooter();
		$sql = $table->qry("DATE_FORMAT(birthday, '%m-%d') ASC");
	} else if ($var["tab"] == "done") {
		$sql = "SELECT * FROM clients WHERE DATE_FORMAT(birthday, '%m-%d') <= '" . date("m-d") . "' AND birthday != '0000-00-00'";
		$table = new Table($sql, []);
		$var['th'] = $table->getHeader();
		$var['tf'] = $table->getFooter();
		$sql = $table->qry("DATE_FORMAT(birthday, '%m-%d') DESC");
	}

	if (isset($sql)) {
		$birthdays = Array();
		while ($row = $sql->fetch_object()) {
			$uI = User::getInstance($row->ID, "ID");
			$birthdays[] = Array("customer" => "<a href='./?p=customers&edit=" . $row->ID . "'>" . $uI->getfName() . "</a>", "date" => $dfo->format(strtotime($row->birthday), false), "years" => date("Y") - date("Y", strtotime($row->birthday)));
		}
		$var['birthdays'] = $birthdays;
	}

	if ($var["tab"] == "settings") {
		$vouchers = Array();
		$sql = $db->query("SELECT ID, code FROM vouchers ORDER BY code ASC");
		while ($row = $sql->fetch_object()) {
			$vouchers[$row->ID] = $row->code;
		}

		$var['vouchers'] = $vouchers;
		$var['languages'] = $languages;
		$var['cronjob_active'] = $db->query("SELECT ID FROM cronjobs WHERE `key` = 'birthday' AND `active` = 1")->num_rows;

		if (isset($_POST['birthday_save'])) {
			try {
				foreach ($_POST as $k => $v) {
					$vlb = "post" . ucfirst(strtolower($k));
					$$vlb = is_array($v) ? $v : $db->real_escape_string(trim($v));
				}

				$cronjob = isset($postCronjob_active) && $postCronjob_active == "1" ? 1 : 0;
				$subjects = isset($postSubject) && is_array($postSubject) ? serialize($postSubject) : $CFG['BIRTHDAY_TITLE'];
				$text = isset($postText) && is_array($postText) ? serialize($postText) : $CFG['BIRTHDAY_TEXT'];

				if (!isset($postBirthday_voucher) || ($postBirthday_voucher != 0 && $db->query("SELECT ID FROM vouchers WHERE ID = " . intval($postBirthday_voucher))->num_rows == 0)) {
					throw new Exception($lang['BIRTHDAYS']['WRONG_VOUCHER']);
				}

				$db->query("UPDATE cronjobs SET active = $cronjob WHERE `key` = 'birthday' LIMIT 1");
				$db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($subjects) . "' WHERE `key` = 'birthday_title' LIMIT 1");
				$db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($text) . "' WHERE `key` = 'birthday_text' LIMIT 1");
				$db->query("UPDATE settings SET `value` = '$postBirthday_voucher' WHERE `key` = 'birthday_voucher' LIMIT 1");

				$CFG['BIRTHDAY_TITLE'] = $subjects;
				$CFG['BIRTHDAY_TEXT'] = $text;
				$CFG['BIRTHDAY_VOUCHER'] = $postBirthday_voucher;
				$var['cronjob_active'] = $cronjob;
				$var['cfg'] = $CFG;
				$var['birthday_msg'] = "<div class='alert alert-success'>" . $lang['BIRTHDAYS']['SAVED'] . "</div>";
				alog("birthdays", "settings_saved");
				unset($_POST);
			} catch (Exception $ex) {
				$var['birthday_msg'] = "<div class='alert alert-danger'>" . $ex->getMessage() . "</div>";
			}
		}

		$var['text'] = unserialize($CFG['BIRTHDAY_TEXT']) !== false ? unserialize($CFG['BIRTHDAY_TEXT']) : Array();
		$var['subject'] = unserialize($CFG['BIRTHDAY_TITLE']) !== false ? unserialize($CFG['BIRTHDAY_TITLE']) : Array();
	}
} else {
	alog("general", "insufficient_page_rights", "birthdays");
	$tpl = "error";
}