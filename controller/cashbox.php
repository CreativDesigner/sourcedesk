<?php
// File for cashbox
global $db, $CFG, $var, $gateways, $session, $nfo, $title, $tpl, $cur, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if ($var['ca_disabled']) {

	$title = $lang['ERROR']['TITLE'];
	$tpl = "error";
	$var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

	// Insert necessary payment JS code
	$gateways->insertJavaScript();

	$_GET['user'] = $pars[0];
	$_GET['hash'] = $pars[1];

	$sql = $db->query("SELECT * FROM clients WHERE `cashbox_active` = 1 AND `ID` = " . intval($_GET['user']));

	if (isset($_GET['user']) && is_numeric($_GET['user']) && $sql->num_rows == 1 && isset($_GET['hash']) && $_GET['hash'] == substr(hash("sha512", $_GET['user'] . $CFG['HASH']), 0, 10)) {
		$title = $lang['CASHBOX']['TITLE'];
		$tpl = "cashbox";

		if (isset($_REQUEST['action']) && $_REQUEST['action'] == "ajax" && isset($_REQUEST['gateway']) && isset($_REQUEST['id'])) {
			$gateways->getActivated($_GET['user'], true)[$_REQUEST['gateway']]->cashboxAjax();
			exit;
		} else if (isset($_POST['make_payment'])) {
			try {
				if (!isset($_POST['token']) || empty($session->get('token')) || $_POST['token'] != $session->get('token')) {
					throw new Exception($lang['CASHBOX']['TEMP_ERROR']);
				}

				$hash = $db->real_escape_string("C" . strtoupper(substr(hash("sha512", uniqid("cb", true)), rand(4, 100), 7)));
				if ($db->query("SELECT ID FROM cashbox WHERE `hash` = 'hash'")->num_rows > 0) {
					throw new Exception($lang['CASHBOX']['TEMP_ERROR']);
				}

				$amount = $nfo->phpize($_POST['amount']);
				if (!isset($_POST['amount']) || !is_numeric($amount) || $amount <= 0) {
					throw new Exception($lang['CASHBOX']['INVALID_AMOUNT']);
				}

				$subject = !empty($_POST['subject']) ? $db->real_escape_string($_POST['subject']) : "";
				if (strlen($subject) > 255) {
					throw new Exception($lang['CASHBOX']['LONG_SUBJECT']);
				}

				$list = $gateways->getActivated($_GET['user'], true);
				if (!isset($_POST['payment_method']) || !isset($list[$_POST['payment_method']])) {
					throw new Exception($lang['CASHBOX']['NO_PM']);
				}

				$gateway = $list[$_POST['payment_method']];
				if (!$gateway->cashbox() || !method_exists($gateway, 'makeCashboxPayment')) {
					throw new Exception($lang['CASHBOX']['INACTIVE_PM']);
				}

				$db->query("INSERT INTO cashbox (`time`, `user`, `hash`, `subject`) VALUES (" . time() . ", " . intval($_GET['user']) . ", '$hash', '$subject')");

				$session->set('token', md5(uniqid()));
				$gateway->makeCashboxPayment($hash);
				$outsourced = true;
			} catch (Exception $ex) {
				$var['msg'] = "<div class=\"alert alert-danger\">" . $ex->getMessage() . "</div>";
			}
		} else {
			if (in_array("cancel", array_keys($_GET))) {
				$var['msg'] = "<div class=\"alert alert-danger\">" . $lang['CASHBOX']['PAYMENT_FAILED'] . "</div>";
			}

		}

		if (!isset($outsourced)) {
			$var['cbuser'] = $sql->fetch_assoc();
			$var['curObj'] = new Currency($var['myCurrency']);
			$var['token'] = md5(uniqid());
			$var['gateways'] = $gateways->getActivated($_GET['user'], true);
			$session->set('token', $var['token']);
		}
	} else if (in_array("okay", array_keys($_GET)) && isset($_GET['i']) && isset($_GET['a']) && isset($_GET['c']) && isset($_GET['g']) && isset($_GET['h']) && substr(hash("sha512", $_GET['i'] . $_GET['a'] . $_GET['c'] . $_GET['g'] . $CFG['HASH']), 0, 10) == $_GET['h'] && ($sql = $db->query("SELECT * FROM cashbox WHERE `hash` = '" . $db->real_escape_string($_GET['i']) . "'")) && $sql->num_rows == 1 && is_array($info = $sql->fetch_assoc()) && ($userSql = $db->query("SELECT * FROM clients WHERE `ID` = " . intval($info['user']) . "")) && $userSql->num_rows == 1) {
		$title = $lang['CASHBOX']['TITLE'];
		$tpl = "cashbox";

		$var['cbInfo'] = $info;
		$var['cbuser'] = $userSql->fetch_assoc();
		$var['amount'] = $cur->infix($nfo->format($_GET['a']), $_GET['c']);
		$var['gateway'] = isset($gateways->getActivated($_GET['user'], true)[$_GET['g']]) ? $gateways->get()[$_GET['g']] : $_GET['g'];
		if (is_object($var['gateway'])) {
			$var['gateway'] = is_string($var['gateway']->getLang("frontend_name")) ? $var['gateway']->getLang("frontend_name") : $var['gateway']->getLang("name");
		}

	} else {
		$title = $lang['ERROR']['TITLE'];
		$tpl = "error";
	}

}

?>