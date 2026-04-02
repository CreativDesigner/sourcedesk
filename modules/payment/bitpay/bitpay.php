<?php
class BitPayPG extends PaymentGateway {
	public static $shortName = "bitpay";

	public function __construct($language) {
		parent::__construct(self::$shortName);
		$this->language = $language;

		if (!include (__DIR__ . "/language/$language.php")) {
			throw new ModuleException();
		}

		if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
			throw new ModuleException();
		}

		$this->lang = $addonlang;
		$this->options = Array(
			"api" => Array("type" => "text", "name" => $this->getLang('api')),
		);
		$this->log = true;
		$this->payment_handler = true;
		$this->cashbox = true;
	}

	public function getPaymentForm($amount = null) {
		global $cur, $CFG, $nfo, $lang, $var;

		$fees = $addon = "";
		if ($this->settings['excl'] == 1) {
			$addon = "_EXCL";
		}

		if ($this->settings['fix'] != 0 && $this->settings['percent'] != 0) {
			$fees = " " . str_replace(Array("%p", "%a"), Array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_BOTH' . $addon]);
		} else if ($this->settings['fix'] != 0) {
			$fees = " " . str_replace(Array("%p", "%a"), Array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_FIX' . $addon]);
		} else if ($this->settings['percent'] != 0) {
			$fees = " " . str_replace(Array("%p", "%a"), Array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_PERCENT' . $addon]);
		}

		$myCur = $var['currencyObj'];

		$code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/bitpay\">
				<div class=\"input-group\">";

		if (!empty($myCur->getPrefix())) {
			$code .= "<span class=\"input-group-addon\">{$myCur->getPrefix()}</span>";
		}

		$code .= "<input type=\"text\" name=\"amount\" placeholder=\"{$this->getLang('amount')}\" value=\"" . ($amount !== null ? $amount : '') . "\" style=\"max-width:80px\" class=\"form-control\">";

		if (!empty($myCur->getSuffix())) {
			$code .= "<span class=\"input-group-addon\">{$myCur->getSuffix()}</span>";
		}

		$code .= "</div>
  				<input type=\"submit\" class=\"btn btn-primary\" value=\"{$this->getLang('submit')}$fees\">
    			</form></p>";

		return $code;
	}

	public function getPaymentHandler() {
		global $var, $user, $CFG, $nfo, $lang, $cur, $nfo, $title, $tpl, $raw_cfg;

		if (!$this->canPay($user)) {
			return;
		}

		// Convert the amount into PHP readable number
		if (isset($_POST['amount'])) {
			$amount = $this->addFees($cur->convertBack($nfo->phpize($_POST['amount'])));
		}

		try {
			// Amount should be greater than zero
			if (!isset($amount) || !is_numeric($amount) || $amount <= 0) {
				throw new Exception($this->getLang('invalid_amount'));
			}
			
			require_once __DIR__ . "/bp_lib.php";

			$res = bpCreateInvoice($user->get()['ID'] . "-" . time(), $amount, $user->get()['ID'], [
				"itemDesc" => $CFG['PAGENAME'] . " " . $user->get()['ID'],
				"currency" => $cur->getBaseCurrency(),
				"redirectURL" => $CFG['PAGEURL'] . "credit",
				"apiKey" => $this->settings['api'],
				"notificationURL" => $CFG['PAGEURL'] . "ipn/bitpay",
			]);

			if (!is_array($res)) {
				throw new Exception($this->getLang('unreachable'));
			}

			if (!empty($res["error"]["message"])) {
				throw new Exception($res["error"]["message"]);
			}

			if (empty($res["url"])) {
				throw new Exception($this->getLang('unreachable'));
			}

			header('Location: ' . $res["url"]);
			exit;
		} catch (Exception $ex) {
			$title = $lang['ERROR']['TITLE'];
			$tpl = "error";
			$var['error'] = $ex->getMessage();
		}
	}

	public function makeCashboxPayment($hash) {
		global $var, $user, $CFG, $nfo, $lang, $cur, $nfo, $title, $tpl;

		// Convert the amount into PHP readable number
		if (isset($_POST['amount'])) {
			$amount = $this->addFees($cur->convertBack($nfo->phpize($_POST['amount']), true));
		}

		try {
			// Amount should be greater than zero
			if (!isset($amount) || !is_numeric($amount) || $amount <= 0) {
				throw new Exception($this->getLang('invalidAmount'));
			}

			require_once __DIR__ . "/bp_lib.php";

			$res = bpCreateInvoice($hash . "-" . time(), $amount, "C" . $hash, [
				"itemDesc" => $CFG['PAGENAME'] . " Cashbox-" . $hash,
				"currency" => $cur->getBaseCurrency(),
				"apiKey" => $this->settings['api'],
				"notificationURL" => $CFG['PAGEURL'] . "ipn/bitpay",
			]);

			if (!is_array($res)) {
				throw new Exception($this->getLang('unreachable'));
			}

			if (!empty($res["error"]["message"])) {
				throw new Exception($res["error"]["message"]);
			}

			if (empty($res["url"])) {
				throw new Exception($this->getLang('unreachable'));
			}

			header('Location: ' . $res["url"]);
			exit;
		} catch (Exception $ex) {
			$title = $lang['ERROR']['TITLE'];
			$tpl = "error";
			$var['error'] = $ex->getMessage();
		}
	}

	public function getIpnHandler() {
		global $CFG, $db, $transactions, $cur, $nfo, $maq;

		$log = "System gestartet\n";

		try {
			require_once __DIR__ . "/bp_lib.php";

			$res = bpVerifyNotification($this->settings['api']);
			if (!$res || is_string($res) || empty($res)) {
				throw new Exception($log . "Inkorrekte Signatur");
			}

			$log .= "Transaktion verifiziert\n";

			if (!in_array($res['status'], ["confirmed", "complete"])) {
				throw new Exception($log . "Inkorrekter Status");
			}

			$log .= "Status verifiziert\n";

			$cashbox = false;
			if (empty($res['posData'])) {
				throw new Exception($log . "Inkorrekte Daten");
			}

			$u = $res['posData'];

			if (substr($u, 0, 1) == "C") {
				$cashbox = true;
				$log .= "Cashbox-Transaktion\n";
			} else {
				$uid = $u;
				if (!User::getInstance($uid, "ID")) {
					throw new Exception($log . "Benutzer nicht gefunden");
				}

				$log .= "Benutzer gefunden\n";
			}
		
			if ($cashbox && !$CFG['CASHBOX_ACTIVE']) {
				throw new Exception($log . "Cashbox inaktiv");
			}

			$oid = $res['id'];
			
			// Check if the transaction is already in the system
			if (count($transactions->get(Array("subject" => "bitpay|" . $oid))) > 0) {
				throw new Exception($log . "Transaktions-ID bereits eingebucht");
			}

			// Check for Cashbox transaction
			if ($cashbox) {
				$sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string(substr($u, 1)) . "'");
				if ($sql->num_rows != 1) {
					throw new Exception($log . "Transaktion nicht gefunden");
				}

				$cashboxInfo = $sql->fetch_object();
				$uid = $cashboxInfo->user;

				if (!User::getInstance($uid, "ID")) {
					throw new Exception($log . "Benutzer nicht gefunden");
				}
			}

			if (!$this->canPay(User::getInstance($uid, "ID"))) {
				throw new Exception($log . "Benutzer nicht berechtigt");
			}

			if ($res['currency'] != $cur->getBaseCurrency()) {
				throw new Exception($log . "Falsche Devise");
			}

			$payment_amount = $res['amount'];
			$fees = $this->getFees($payment_amount);

			// Update the user credit and insert the transaction in the database
			$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($uid) . "' LIMIT 1");
			$userInfo = $db->query("SELECT ID, firstname, lastname, mail, language, currency FROM clients WHERE ID = '" . $db->real_escape_string($uid) . "' LIMIT 1")->fetch_object();
			$transactions->insert("bitpay", $oid, $payment_amount, $userInfo->ID, $cashbox ? trim($cashboxInfo->subject) : "", 1);

			$log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

			// Send a notification email
			$uI = User::getInstance($userInfo->ID, "ID");
			$sendLang = $uI->getLanguage();
			$userCurrency = $uI->getCurrency();

			try {
				$curObj = new Currency($userCurrency);
			} catch (CurrencyException $ex) {
				if ($userCurrency == $cur->getBaseCurrency()) {
					throw new Exception($log . "W&auml;hrungsfehler");
				}

				$userCurrency = $cur->getBaseCurrency();
			}

			$mtObj = new MailTemplate("Guthabenaufladung");
			$title = $mtObj->getTitle($sendLang);
			$mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
			
			$maq->enqueue([
				"amount" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $payment_amount, $userCurrency)), $userCurrency),
				"processor" => $this->getLang("name"),
			], $mtObj, $userInfo->mail, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userInfo->ID, true, 0, 0, $mtObj->getAttachments($sendLang));

			// Send admin notification
			if (($ntf = AdminNotification::getInstance("IPN-Gutschrift")) !== false) {
				$ntf->set("amount", $cur->infix($nfo->format($payment_amount), $cur->getBaseCurrency()));
				$ntf->set("fees", $cur->infix($nfo->format($fees), $cur->getBaseCurrency()));
				$ntf->set("gateway", $this->getLang("name"));
				$ntf->set("customer", $userInfo->firstname . " " . $userInfo->lastname);
				$ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $userInfo->ID);
				$ntf->send();
			}

			// Invoice fees
			if ($fees > 0) {
				$uI->invoiceFees($fees);
				$log .= "Geb&uuml;hren gebucht\n";
			}

			$uI->applyCredit();

			// Throw an exception for insert the log into the database
			throw new Exception($log . "E-Mail gesendet");
		} catch (Exception $ex) {
			// Insert the log until this moment into the database
			unset($_GET['h']);
			unset($_GET['p']);
			$_GET['userid'] = $_GET['id'];
			$_GET['btc'] = $_GET['value'] / 100000000;
			unset($_GET['id']);
			$data = print_r($_GET, true);

			$log = $db->real_escape_string($ex->getMessage());

			$db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'bitcoin')");
		}
	}
}