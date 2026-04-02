<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['LATERINVOICE'];

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($l['TITLE']);
menu("customers");

$uI = User::getInstance($_GET['user'], "ID");

if ($ari->check(13) && $uI) {
	if (isset($_POST['description'])) {
		try {
			if (empty($_POST['description'])) {
				throw new Exception($l['ERR1']);
			}

			$a = $nfo->phpize($_POST['amount']);
			if (!isset($a) || (doubleval(($a)) != ($a) && intval(($a)) != ($a))) {
				throw new Exception($l['ERR2']);
			}

			if (isset($_POST['net']) && $_POST['net'] == "yes") {
				$a = $uI->addTax($a);
			}

			$uI->invoiceLater($_POST['description'], ($a), $_POST['paid'] == "yes" ? true : false);

			alog("laterinvoice", "created", $uI->get()['ID']);
			$var['success'] = $l['SUC'];
			unset($_POST);
		} catch (Exception $ex) {
			$var['error'] = $ex->getMessage();
		}
	}

	$tpl = "new_laterinvoice";

	$var['cur_prefix'] = $cur->getPrefix();
	$var['cur_suffix'] = $cur->getSuffix();
	$var['user'] = $uI->get();
} else {
	alog("general", "insufficient_page_rights", "new_laterinvoice");
	$tpl = "error";
}