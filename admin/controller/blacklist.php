<?php
global $ari, $lang, $CFG, $db, $var, $dfo, $_POST, $val, $_GET;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

menu("customers");
title($lang['BLACKLIST']['NAME']);

// Check admin rights
if ($ari->check(53)) {
	$tpl = "blacklist";
	$var["tab"] = isset($_GET['tab']) ? $_GET['tab'] : "ips";

	// User wants to save the Fail2Ban settings
	if (isset($_POST['f2b_save'])) {
		try {
			// Get elements from POST
			foreach ($_POST as $k => $v) {
				$val = "post_" . strtolower($k);
				$$val = $db->real_escape_string($v);
			}

			// Get checkbox value
			if (!isset($post_fail2ban_active) || $post_fail2ban_active != "1") {
				$post_active = 0;
			} else {
				$post_active = 1;
			}

			// Check numeric values
			if (!isset($post_fail2ban_failed) || !is_numeric($post_fail2ban_failed) || $post_fail2ban_failed < 1) {
				throw new Exception($lang['BLACKLIST']['F2B_ERROR1']);
			}

			if (!isset($post_fail2ban_locked) || !is_numeric($post_fail2ban_locked) || $post_fail2ban_locked < 1) {
				throw new Exception($lang['BLACKLIST']['F2B_ERROR2']);
			}

			$db->query("UPDATE `settings` SET `value` = '$post_fail2ban_locked' WHERE `key` = 'fail2ban_locked' LIMIT 1");
			$db->query("UPDATE `settings` SET `value` = '$post_fail2ban_failed' WHERE `key` = 'fail2ban_failed' LIMIT 1");
			$db->query("UPDATE `settings` SET `value` = '$post_active' WHERE `key` = 'fail2ban_active' LIMIT 1");
			$CFG['FAIL2BAN_LOCKED'] = $post_fail2ban_locked;
			$CFG['FAIL2BAN_FAILED'] = $post_fail2ban_failed;
			$CFG['FAIL2BAN_ACTIVE'] = $post_active;
			$var['cfg'] = $CFG;
			alog("blacklist", "f2b_changed");

			unset($_POST);
			$var['f2b_msg'] = "<div class=\"alert alert-success\">" . $lang['BLACKLIST']['F2B_SAVED'] . "</div>";
		} catch (Exception $ex) {
			$var['f2b_msg'] = "<div class=\"alert alert-danger\">" . $ex->getMessage() . "</div>";
		}
	}

	// Add a new IP address to blacklist
	if (isset($_POST['add_ip'])) {
		try {
			// Check IP
			if (!$val->ip($_POST['new_ip'])) {
				throw new Exception($lang['BLACKLIST']['WRONG_IP']);
			}

			// Check reason
			if (!isset($_POST['reason']) || trim($_POST['reason']) == "") {
				throw new Exception($lang['BLACKLIST']['NO_REASON']);
			}

			// Try to insert
			$postIp = $db->real_escape_string($_POST['new_ip']);
			$postReason = $db->real_escape_string($_POST['reason']);
			if (!$db->query("INSERT INTO blacklist_ip (`ip`, `reason`, `inserted`) VALUES ('$postIp', '$postReason', " . time() . ")")) {
				throw new Exception($lang['BLACKLIST']['INSERT_ERROR']);
			}

			$var['ip_msg'] = "<div class=\"alert alert-success\">" . $lang['BLACKLIST']['IP_INSERTED'] . "</div>";
			alog("blacklist", "ip_added", $_POST['new_ip']);
			unset($_POST);
		} catch (Exception $ex) {
			$var['ip_msg'] = "<div class=\"alert alert-danger\">" . $ex->getMessage() . "</div>";
		}
	}

	// Delete selected IP addresses
	else if (isset($_POST['delete_ips'])) {
		// Check if any is selected
		if (is_array($_POST['delete_ip']) && count($_POST['delete_ip']) > 0) {
			$deleted = 0;

			foreach ($_POST['delete_ip'] as $k => $v) {
				// Content should be correct
				if ($v != "true") {
					continue;
				}

				$ex = explode("_", $k);
				if (!$ex || count($ex) != 2) {
					continue;
				}

				if ($ex[0] == "f2b") {
					// Delete IP address after logins failed
					$id = $db->real_escape_string($ex[1]);
					if ($db->query("DELETE FROM fail2ban WHERE failed >= 10 AND until >= " . time() . " AND ID = '$id' LIMIT 1")) {
						$deleted++;
						alog("blacklist", "f2b_ip_deleted", intval($id));
					}
				}

				if ($ex[0] == "black") {
					// Delete IP address that was manually locked
					$id = $db->real_escape_string($ex[1]);
					if ($db->query("DELETE FROM blacklist_ip WHERE ID = '$id' LIMIT 1")) {
						$deleted++;
						alog("blacklist", "ip_deleted_log", intval($id));
					}
				}
			}

			if ($deleted == 1) {
				$msg = $lang['BLACKLIST']['IP_DELETED'];
			} else {
				$msg = str_replace("%c", $deleted, $lang['BLACKLIST']['IPS_DELETED']);
			}

			$var['ip_msg'] = "<div class=\"alert alert-success\">$msg</div>";
		}
	}

	// Delete all IP addresses
	else if (isset($_POST['delete_all_ips'])) {
		$db->query("DELETE FROM fail2ban WHERE failed >= 10 AND until >= " . time());
		$db->query("DELETE FROM blacklist_ip");
		$var['ip_msg'] = "<div class=\"alert alert-success\">" . $lang['BLACKLIST']['ALL_IPS_DELETED'] . "</div>";
		alog("blacklist", "aips_deleted");
	}

	// Delete selected mail addresses
	else if (isset($_POST['delete_mails'])) {
		// Check if any is selected
		if (is_array($_POST['delete_mail']) && count($_POST['delete_mail']) > 0) {
			$deleted = 0;

			foreach ($_POST['delete_mail'] as $k => $v) {
				// Content should be correct
				if ($v != "true") {
					continue;
				}

				// Delete from database
				$id = $db->real_escape_string($k);
				if ($db->query("DELETE FROM blacklist_mail WHERE ID = '$id' LIMIT 1")) {
					$deleted++;
					alog("blacklist", "maildeleted", intval($id));
				}
			}

			if ($deleted == 1) {
				$msg = $lang['BLACKLIST']['MAIL_DELETED'];
			} else {
				$msg = str_replace("%c", $deleted, $lang['BLACKLIST']['MAILS_DELETED']);
			}

			$var['mail_msg'] = "<div class=\"alert alert-success\">$msg</div>";
		}
	}

	// Delete all mail addresses
	else if (isset($_POST['delete_all_mails'])) {
		$db->query("DELETE FROM blacklist_mail");
		$var['mail_msg'] = "<div class=\"alert alert-success\">" . $lang['BLACKLIST']['ALL_MAILS_DELETED'] . "</div>";
		alog("blacklist", "amails_deleted");
	}

	// Add a new mail address
	else if (isset($_POST['add_mail'])) {
		// Check length
		if (!isset($_POST['new_mail']) || strlen(trim($_POST['new_mail'])) < 3) {
			$var['mail_msg'] = "<div class=\"alert alert-danger\">" . $lang['BLACKLIST']['MAIL_THREE'] . "</div>";
		} else {
			// Try to insert
			$postMail = $db->real_escape_string($_POST['new_mail']);
			if (!$db->query("INSERT INTO blacklist_mail (`email`, `inserted`) VALUES ('$postMail', " . time() . ")")) {
				$var['mail_msg'] = "<div class=\"alert alert-danger\">" . $lang['BLACKLIST']['INSERT_ERROR_MAIL'] . "</div>";
			} else {
				$var['mail_msg'] = "<div class=\"alert alert-success\">" . $lang['BLACKLIST']['MAIL_INSERTED'] . "</div>";
				alog("blacklist", "mail_added", $postMail);
				unset($_POST);
			}
		}
	}

	// Select locked IP addresses for printing
	$ip_arr = Array();

	$black_ips = $db->query("SELECT * FROM blacklist_ip ORDER BY inserted DESC");
	if ($black_ips->num_rows > 0) {
		while ($entry = $black_ips->fetch_object()) {
			$ip_arr[] = Array("type" => "black", "id" => $entry->ID, "ip" => $entry->ip, "since" => $dfo->format($entry->inserted), "until" => $lang['BLACKLIST']['UNLIMITED'], "reason" => $entry->reason);
		}
	}

	$f2b_ips = $db->query("SELECT * FROM fail2ban WHERE failed >= 10 AND until >= " . time() . " ORDER BY until DESC");
	if ($f2b_ips->num_rows > 0) {
		while ($entry = $f2b_ips->fetch_object()) {
			$ip_arr[] = Array("type" => "f2b", "id" => $entry->ID, "ip" => $entry->ip, "since" => $dfo->format($entry->until - 60 * 30), "until" => $dfo->format($entry->until), "reason" => "Failed login attempts");
		}
	}

	$var['locked_ips'] = $ip_arr;

	// Select locked mail addresses for printing
	$mail_arr = Array();

	$black_mails = $db->query("SELECT * FROM blacklist_mail ORDER BY inserted DESC");
	if ($black_mails->num_rows > 0) {
		while ($entry = $black_mails->fetch_object()) {
			$mail_arr[] = Array("id" => $entry->ID, "email" => $entry->email, "since" => $dfo->format($entry->inserted));
		}
	}

	$var['locked_mails'] = $mail_arr;
} else {
	alog("general", "insufficient_page_rights", "blacklist");
	$tpl = "error";
}