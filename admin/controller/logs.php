<?php
global $ari, $var, $db, $CFG, $dfo, $lang;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['LOGS']['TITLE']);
menu("customers");

// Check admin rights
if ($ari->check(49)) {
	$tpl = "logs";

	$var['count'] = $db->query("SELECT * FROM `client_log`")->num_rows;
	$var['pages'] = max(1, ceil($var['count'] / 50));
	$page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
	if ($page < 1) {
		$page = 1;
	}

	if ($page > $var['pages']) {
		$page = $var['pages'];
	}

	$offset = max(0, ($page - 1) * 50);
	$var['apage'] = $page;

	// Write log entries into an Array
	$logSql = $db->query("SELECT * FROM client_log ORDER BY time DESC LIMIT 50 OFFSET $offset");
	$var['logs'] = Array();
	while ($log = $logSql->fetch_object()) {
		$cusSql = $db->query("SELECT firstname, lastname FROM clients WHERE ID = " . $log->user);

		if ($cusSql->num_rows == 1) {
			$cusInfo = $cusSql->fetch_object();
			$uI = User::getInstance($log->user, "ID");
		}
		if (isset($cusInfo)) {
			$cus = "<a href=\"?p=customers&edit=" . $log->user . "\">" . $uI->getfName() . "</a>";
		} else {
			$cus = "<i>" . $lang['LOGS']['NOT_AVAILABLE'] . "</i>";
		}

		$ipLogSql = $db->query("SELECT country, city FROM ip_logs WHERE ip = '" . $db->real_escape_string($log->ip) . "' LIMIT 1");
		if ($ipLogSql->num_rows == 1) {
			$location = $ipLogSql->fetch_assoc();
			$var['logs'][] = Array(
				'date' => $dfo->format($log->time),
				'customer' => $cus,
				'action' => $log->action,
				'ip' => $log->ip,
				'ua' => $log->ua,
				'location' => $location,
			);
		} else {
			$var['logs'][] = Array(
				'date' => $dfo->format($log->time),
				'customer' => $cus,
				'action' => $log->action,
				'ip' => $log->ip,
				'ua' => $log->ua,
			);
		}
	}
} else {
	alog("general", "insufficient_page_rights", "logs");
	$tpl = "error";
}
