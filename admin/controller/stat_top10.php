<?php
global $ari, $lang, $CFG, $db, $var, $_GET, $nfo, $cur;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['TOP10']['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
	$tpl = "stat_top10";
	$var["tab"] = isset($_GET['tab']) ? $_GET['tab'] : "products";
	alog("top10", "tab_viewed", $var['tab']);

	$tableContents = "";

	// Fill table
	switch ($var["tab"]) {
	case 'credit':
		$sql = $db->query("SELECT ID, firstname, lastname, credit FROM clients WHERE credit > 0 ORDER BY credit DESC LIMIT 10");
		if ($sql->num_rows > 0) {
			while ($row = $sql->fetch_object()) {
				$uI = User::getInstance($row->ID, "ID");
				$tableContents .= "<tr><td><a href=\"./?p=customers&edit=" . $row->ID . "\">" . $uI->getfName() . "</a></td><td>" . $cur->infix($nfo->format($row->credit), $cur->getBaseCurrency()) . "</td></tr>";
			}
		} else {
			$tableContents .= "<tr><td colspan='2'><center>" . $lang['TOP10']['NO_CREDIT'] . "</center></td></tr>";
		}
		break;

	case 'products':
		$sql = $db->query("SELECT product, COUNT(ID) as num FROM client_products GROUP BY product");
		$licenses = Array();
		$win = Array();
		while ($row = $sql->fetch_object()) {
			$licenses[$row->product] = $row->num;
			$win[$row->product] = $db->query("SELECT SUM(i.amount) AS sum FROM invoiceitems i, client_products p WHERE i.relid = p.ID AND p.product = {$row->product}")->fetch_object()->sum;
		}

		$arr = isset($_GET['order']) && $_GET['order'] == "profit" ? $win : $licenses;
		arsort($arr);

		if (count($arr) > 0) {
			$i = 0;
			foreach ($arr as $id => $nim) {
				$i++;if ($i == 10) {
					break;
				}

				$prodSql = $db->query("SELECT name FROM products WHERE ID = " . $id);
				if ($prodSql->num_rows <= 0) {
					$prod = "<i>" . $lang['LOGS']['NOT_AVAILABLE'] . "</i>";
				} else {
					$prod = unserialize($prodSql->fetch_object()->name)[$CFG['LANG']];
				}

				$tableContents .= "<tr><td>" . $prod . "</td><td>" . $licenses[$id] . "</td><td>" . $cur->infix($nfo->format($win[$id]), $cur->getBaseCurrency()) . "</td></tr>";
			}
		} else {
			$tableContents .= "<tr><td colspan='3'><center>" . $lang['TOP10']['NO_LICENSES'] . "</center></td></tr>";
		}
		break;

	case 'licenses':
		$sql = $db->query("SELECT user, COUNT(ID) as num FROM client_products GROUP BY user");
		$licenses = Array();
		$win = Array();
		while ($row = $sql->fetch_object()) {
			$licenses[$row->user] = $row->num;
			$win[$row->user] = 0;

			$sql2 = $db->query("SELECT ID FROM client_products WHERE user = {$row->user}");
			while ($row2 = $sql2->fetch_object()) {
				$win[$row->user] += $db->query("SELECT SUM(amount) as amount FROM invoiceitems WHERE relid = {$row2->ID}")->fetch_object()->amount;
			}

		}

		$arr = isset($_GET['order']) && $_GET['order'] == "profit" ? $win : $licenses;
		arsort($arr);

		if (count($arr) > 0) {
			$i = 0;
			foreach ($arr as $id => $nim) {
				$i++;if ($i == 10) {
					break;
				}

				$usrSql = $db->query("SELECT ID FROM clients WHERE ID = " . $id);
				unset($usrInfo);
				if ($usrSql->num_rows == 1) {
					$usrInfo = User::getInstance($usrSql->fetch_object()->ID, "ID");
				}

				if (!isset($usrInfo)) {
					$usr = "<i>" . $lang['LOGS']['NOT_AVAILABLE'] . "</i>";
				} else {
					$usr = "<a href=\"./?p=customers&edit=" . $id . "\">" . $usrInfo->getfName() . "</a>";
				}

				$tableContents .= "<tr><td>" . $usr . "</td><td>" . $licenses[$id] . "</td><td>" . $cur->infix($nfo->format($win[$id]), $cur->getBaseCurrency()) . "</td></tr>";
			}
		} else {
			$tableContents .= "<tr><td colspan='3'><center>" . $lang['TOP10']['NO_LICENSES'] . "</center></td></tr>";
		}
		break;

	case 'invoices':
		$sql = $db->query("SELECT
				c.`ID` AS userid,
				SUM(p.`amount`) AS sum,
				COUNT(i.`ID`) AS num
			FROM
				clients c,
				invoices i,
				invoiceitems p
			WHERE
				c.ID = i.client AND
				p.invoice = i.ID
			GROUP BY
				c.ID
			ORDER BY
				" . (isset($_GET['order']) && $_GET['order'] == "num" ? "COUNT(i.ID) DESC" : "SUM(p.amount) DESC") . "
			LIMIT
				10");

		if ($sql->num_rows > 0) {
			while ($row = $sql->fetch_object()) {
				$usr = User::getInstance($row->userid, "ID");
				$tableContents .= "<tr><td><a href='?p=customers&edit={$row->userid}'>{$usr->getfName()}</a></td><td>" . $nfo->format($row->num, 0) . "</td><td>" . $cur->infix($nfo->format($row->sum), $cur->getBaseCurrency()) . "</td></tr>";
			}
		} else {
			$tableContents .= "<tr><td colspan='3'><center>" . $lang['TOP10']['NO_INVOICES'] . "</center></td></tr>";
		}
		break;
	}

	$var["tableContents"] = $tableContents;
} else {
	alog("general", "insufficient_page_rights", "stat_top10");
	$tpl = "error";
}