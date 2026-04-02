<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $transactions, $adminInfo, $lang, $cur, $gateways;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['TRANSACTIONS']['TITLE']);
menu("customers");

// Check admin rights
if ($ari->check(15)) {
	$tpl = "transactions";

	// Add transaction to stem (one click)
	if (isset($_GET['stem']) && ($sql = $db->query("SELECT * FROM client_transactions WHERE stem = 0 AND ID = '" . $db->real_escape_string($_GET['stem']) . "' AND amount < 0 LIMIT 1")) && $sql->num_rows == 1 && is_numeric($CFG['STEM_AUTO']) && $CFG['STEM_AUTO'] > 0) {
		$info = $sql->fetch_object();
		$add = ceil($info->amount * $CFG['STEM_AUTO'] * 100) / -10000;
		$db->query("UPDATE settings SET `value` = " . ($CFG['STEM'] + $add) . " WHERE `key` = 'stem' LIMIT 1");
		$db->query("UPDATE client_transactions SET stem = 1 WHERE ID = $info->ID LIMIT 1");
		$var['success'] = $lang['TRANSACTIONS']['STEM_OK'];
		alog("transactions", "stem_made", $info->ID);
	}

	if (isset($_GET['ignore_stem']) && ($sql = $db->query("SELECT * FROM client_transactions WHERE stem = 0 AND amount < 0 AND ID = '" . $db->real_escape_string($_GET['ignore_stem']) . "' LIMIT 1")) && $sql->num_rows == 1 && is_numeric($CFG['STEM_AUTO']) && $CFG['STEM_AUTO'] > 0) {
		$info = $sql->fetch_object();
		$db->query("UPDATE client_transactions SET stem = -1 WHERE stem = 0 AND ID = $info->ID LIMIT 1");
		$var['success'] = $lang['TRANSACTIONS']['STEM_IGNORED'];
		alog("transactions", "ignored", $info->ID);
	}

	// Do refund if requested
	if (isset($_GET['refund']) && ($sql = $db->query("SELECT * FROM client_transactions WHERE deposit = 1 AND amount > 1 AND ID = '" . $db->real_escape_string($_GET['refund']) . "' LIMIT 1")) && $sql->num_rows == 1 && is_object($info = $sql->fetch_object())) {
		$ex = explode("|", $info->subject);
		if (is_array($gateways->get()) && array_key_exists($ex[0], $gateways->get()) && method_exists($gateways->get()[$ex[0]], "refundPayment")) {
			if (!$gateways->get()[$ex[0]]->refundPayment($ex[1])) {
				$var['danger'] = $lang['TRANSACTIONS']['REFUND_FAILED'];
			} else {
				$usr = User::getInstance($info->user, "ID");
				$usr->set(Array("credit" => $usr->get()['credit'] - $info->amount));
				$db->query("DELETE FROM client_transactions WHERE ID = " . $info->ID . " LIMIT 1");
				$var['success'] = $lang['TRANSACTIONS']['REFUND_OK'];
				alog("transactions", "refund", $info->ID);
			}
		}
	}

	// Mass actions
	if (isset($_POST['stem_selected']) && is_array($_POST['trans'])) {
		$d = 0;
		foreach ($_POST['trans'] as $id) {
			$sql = $db->query("SELECT * FROM client_transactions WHERE stem = 0 AND ID = '" . $db->real_escape_string($id) . "' AND amount < 0 LIMIT 1");
			if ($sql->num_rows != 1) {
				continue;
			}

			$info = $sql->fetch_object();
			$add = ceil($info->amount * $CFG['STEM_AUTO'] * 100) / -10000;
			$arr[$info->ID] = Array("amount" => $info->amount, "stem" => $add);
			$db->query("UPDATE settings SET `value` = " . floatval(floatval($CFG['STEM']) + $add) . " WHERE `key` = 'stem' LIMIT 1");
			$CFG['STEM'] += $add;
			$db->query("UPDATE client_transactions SET stem = 1 WHERE ID = {$info->ID} LIMIT 1");
			if ($db->affected_rows > 0) {
				alog("transactions", "stem_made", intval($id));
				$d++;
			}
		}

		if ($d == 1) {
			$var['success'] = $lang['TRANSACTIONS']['STEM_ONE'];
		} else if ($d > 0) {
			$var['success'] = str_replace("%x", $d, $lang['TRANSACTIONS']['STEM_X']);
		}

	}

	if (isset($_POST['ignore_selected']) && is_array($_POST['trans'])) {
		$d = 0;
		foreach ($_POST['trans'] as $id) {
			$db->query("UPDATE client_transactions SET stem = -1 WHERE ID = " . intval($id) . " AND stem = 0 AND amount < 0 LIMIT 1");
			if ($db->affected_rows > 0) {
				alog("transactions", "ignored", intval($id));
				$d++;
			}
		}

		if ($d == 1) {
			$var['success'] = $lang['TRANSACTIONS']['IGNORE_ONE'];
		} else if ($d > 0) {
			$var['success'] = str_replace("%x", $d, $lang['TRANSACTIONS']['IGNORE_X']);
		}

	}

	// Write transactions into an Array
	$count = $var['count'] = count($transactions->get(Array(), 0, "time", 1, $adminInfo->language, (!isset($_GET['filter']) || $_GET['filter'] != "stem" || $CFG['STEM_AUTO'] <= 0 ? 0 : 1)));
	$var['pages'] = max(1, ceil($count / 50));
	$page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
	if ($page < 1) {
		$page = 1;
	}

	if ($page > $var['pages']) {
		$page = $var['pages'];
	}

	$offset = max(0, ($page - 1) * 50);
	$var['apage'] = $page;
	$var['filter'] = !empty($_GET['filter']) ? $_GET['filter'] : "";

	$transArr = $transactions->get(Array(), 50, "time", 1, $adminInfo->language, (!isset($_GET['filter']) || $_GET['filter'] != "stem" || $CFG['STEM_AUTO'] <= 0 ? 0 : 1), $offset);

	$var['transactions'] = Array();
	foreach ($transArr as $trans) {
		$trans = (object) $trans;

		$cusSql = $db->query("SELECT ID FROM clients WHERE ID = " . $trans->user);
		if ($cusSql->num_rows == 1) {
			$cusInfo = User::getInstance($cusSql->fetch_object()->ID, "ID");
		}

		if (isset($cusInfo)) {
			$cus = "<a href=\"?p=customers&edit=" . $trans->user . "\">" . $cusInfo->getfName() . "</a>";
		} else {
			$cus = "<i>" . $lang['LOGS']['NOT_AVAILABLE'] . "</i>";
		}

		if ($trans->amount > 0) {
			$amo = "<font color=\"green\">" . $cur->infix($nfo->format($trans->amount, 2, 0), $cur->getBaseCurrency()) . "</font>";
		} else if ($trans->amount < 0) {
			$amo = "<font color=\"red\">" . $cur->infix($nfo->format($trans->amount, 2, 0), $cur->getBaseCurrency()) . "</font>";
		} else {
			$amo = $cur->infix($nfo->format($trans->amount, 2, 0), $cur->getBaseCurrency());
		}

		$refundable = false;
		if ($trans->deposit && $trans->amount > 0 && !$b->waiting) {
			$ex = explode("|", $trans->raw_subject);
			if (is_array($gateways->get()) && array_key_exists($ex[0], $gateways->get()) && $gateways->get()[$ex[0]]->canRefund()) {
				$refundable = true;
			}

		}

		$var['transactions'][$trans->ID] = Array(
			'ID' => $trans->ID,
			'date' => $dfo->format($trans->time),
			'customer' => $cus,
			'subject' => $trans->subject,
			'amount' => $amo,
			'stem' => $trans->amount < 0 ? $trans->stem : 1,
			'cashbox' => $trans->cashbox_subject,
			'deposit' => $trans->deposit,
			'waiting' => $trans->waiting,
			'refundable' => $refundable,
		);
	}
} else {
	alog("general", "insufficient_page_rights", "transactions");
	$tpl = "error";
}