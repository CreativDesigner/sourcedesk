<?php
// Class for making sofort bank transfers

class SofortPG extends PaymentGateway {
	public static $shortName = "sofort";

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
			"api_key" => Array("type" => "text", "name" => $this->getLang('api_key')),
			"prefix" => Array("type" => "text", "name" => $this->getLang('prefix'), "help" => $this->getLang('prefix_hint'), "default" => $this->getLang('prefix_default')),
			"incoming" => Array("type" => "checkbox", "default" => "false", "description" => $this->getLang('incoming'), "help" => $this->getLang('incoming_hint')),
		);
		$this->log = true;
		$this->admin_warning = $this->getLang("admin_warning");
		$this->payment_handler = true;
		$this->checkIncoming();
		$this->cashbox = true;
	}

	public function checkIncoming() {
		global $CFG, $db, $maq, $dfo, $cur, $nfo;

		if ($this->options['incoming'] === "true") {
			$lastDate = time() - 60 * 60 * 24 * 10; // 10 days
			$sql = $db->query("SELECT * FROM sofort_open_transactions WHERE date <= $lastDate AND last_reminder <= $lastDate LIMIT 10");
			while ($row = $sql->fetch_object()) {
				$userObj = new User($row->user, "ID");
				$userInfo = $userObj->get();

				if ($userInfo === null) {
					continue;
				}

				$maq->enqueue([], null, $CFG['PAGEMAIL'], "Offene Sofort-Überweisung", "Es gibt eine offene Sofort-Überweisung:

Datum: " . $dfo->format($row->date) . "
Betrag: " . $cur->infix($nfo->format($row->amount), $cur->getBaseCurrency()) . "
Benutzer: " . $userInfo->name . " (# " . $row->user . ")

Die Überweisung wurde nicht wie gewünscht durchgeführt und eventuell sogar absichtlich durch den Kunden storniert.", "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", 0, false);

				$db->query("UPDATE sofort_open_transactions SET last_reminder = " . time() . " WHERE ID = " . $row->ID . " LIMIT 1");
			}
		}
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

		$code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/sofort\">
				<div class=\"input-group\">";

		$myCur = $var['currencyObj'];

		if (!empty($myCur->getPrefix())) {
			$code .= "<span class=\"input-group-addon\">{$myCur->getPrefix()}</span>";
		}

		$code .= "<input type=\"text\" name=\"amount\" value=\"" . ($amount !== null ? $amount : '') . "\" placeholder=\"{$this->getLang('amount')}\" style=\"max-width:80px\" class=\"form-control\">";

		if (!empty($myCur->getSuffix())) {
			$code .= "<span class=\"input-group-addon\">{$myCur->getSuffix()}</span>";
		}

		$code .= "</div>
  				<input type=\"submit\" class=\"btn btn-primary\" value=\"{$this->getLang('submit')}" . $fees . "\">
    			</form></p>";

		return $code;
	}

	public function activate() {
		global $db, $CFG;
		parent::activate();

		$db->query("CREATE TABLE `sofort_open_transactions` (
					  `ID` int(11) NOT NULL,
					  `user` int(11) NOT NULL DEFAULT '0',
					  `amount` double(100,2) NOT NULL DEFAULT '0.00',
					  `date` int(11) NOT NULL DEFAULT '0',
					  `last_reminder` int(11) NOT NULL DEFAULT '0'
					);");
	}

	public function deactivate() {
		global $db, $CFG;
		parent::deactivate();

		$db->query("DROP TABLE `sofort_open_transactions`;");
	}

	public function getPaymentHandler() {
		global $var, $user, $CFG, $nfo, $lang, $title, $tpl;

		if (!$this->canPay($user)) {
			return;
		}

		// Get sofort library
		$key = $this->settings['api_key'];
		if (!include_once (__DIR__ . "/lib/payment/sofortLibSofortueberweisung.inc.php")) {
			die("Sofort library not found.");
		}

		// Init payment
		$sofort = new Sofortueberweisung($key);
		$sofort->setCurrencyCode($var['myCurrency']);
		$sofort->setSenderCountryCode($lang['LOCALCODE']);
		$sofort->setReason('Sofort', $this->settings['prefix'] . $user->get()['ID']);
		$sofort->setSuccessUrl($CFG['PAGEURL'] . 'credit?okay');
		$sofort->setAbortUrl($CFG['PAGEURL'] . 'credit?cancel');
		$sofort->setNotificationUrl($CFG['PAGEURL'] . 'ipn/sofort');
		$sofort->setCustomerprotection(false);
		$sofort->setAmount($this->addFees($nfo->phpize($_POST['amount']), true));

		// Send request to sofort
		$sofort->sendRequest();

		if ($sofort->isError()) {
			// If error occured, send back to credit page
			$title = $lang['ERROR']['TITLE'];
			$tpl = "error";

			$var['error'] = "";
			$errorFields = Array();
			foreach ($sofort->getErrors() as $error) {
				if (!in_array($error['field'], $errorFields)) {
					$var['error'] .= trim($error['message']) . "<br />";
					array_push($errorFields, $error['field']);
				}
			}

			$var['error'] = trim($var['error'], "<br />");
		} else {
			// All correct? Send the user to payment processing
			header('Location: ' . $sofort->getPaymentUrl());
			exit;
		}
	}

	public function makeCashboxPayment($hash) {
		global $var, $user, $CFG, $nfo, $lang, $title, $tpl;

		// Get sofort library
		$key = $this->settings['api_key'];
		if (!include_once (__DIR__ . "/lib/payment/sofortLibSofortueberweisung.inc.php")) {
			die("Sofort library not found.");
		}

		// Init payment
		$sofort = new Sofortueberweisung($key);
		$sofort->setCurrencyCode($var['myCurrency']);
		$sofort->setSenderCountryCode($lang['LOCALCODE']);
		$sofort->setReason('Sofort', $CFG['CASHBOX_PREFIX'] . $hash);
		$sofort->setSuccessUrl($CFG['PAGEURL'] . 'cashbox?okay&i=' . $hash . '&a=' . $nfo->phpize($_POST['amount']) . '&c=' . $var['myCurrency'] . '&g=' . self::$shortName . '&h=' . substr(hash("sha512", $hash . $nfo->phpize($_POST['amount']) . $var['myCurrency'] . self::$shortName . $CFG['HASH']), 0, 10));
		$sofort->setAbortUrl($CFG['PAGEURL'] . 'cashbox/' . $_REQUEST['user'] . '/' . $_REQUEST['hash'] . '?cancel&currency=' . $var['myCurrency'] . '&amount=' . urlencode($_POST['amount']) . '&payment_method=' . urlencode($_POST['payment_method']) . '&subject=' . urlencode($_POST['subject']));
		$sofort->setNotificationUrl($CFG['PAGEURL'] . 'ipn/sofort');
		$sofort->setCustomerprotection(false);
		$sofort->setAmount($this->addFees($nfo->phpize($_POST['amount']), true));

		// Send request to sofort
		$sofort->sendRequest();

		if ($sofort->isError()) {
			// If error occured, send to error page
			$title = $lang['ERROR']['TITLE'];
			$tpl = "error";

			$var['error'] = "";
			$errorFields = Array();
			foreach ($sofort->getErrors() as $error) {
				if (!in_array($error['field'], $errorFields)) {
					$var['error'] .= trim($error['message']) . "<br />";
					array_push($errorFields, $error['field']);
				}
			}

			$var['error'] = trim($var['error'], "<br />");
		} else {
			// All correct? Send the user to payment processing
			header('Location: ' . $sofort->getPaymentUrl());
			exit;
		}
	}

	public function getIpnHandler() {
		global $CFG, $db, $transactions, $cur, $maq, $raw_cfg, $nfo;

		$log = "System gestartet\n";

		try {

			// Get the transaction ID from the transmitted data
			$raw_post_data = file_get_contents('php://input');
			$xml = new SimpleXMLElement($raw_post_data);

			if (!isset($xml->transaction)) {
				throw new Exception($log . "Transaktions-ID unbekannt");
			}

			$tid = $xml->transaction;

			// Require the necessary Sofort classes
			require_once __DIR__ . '/lib/core/sofortLibNotification.inc.php';
			require_once __DIR__ . '/lib/core/sofortLibTransactionData.inc.php';

			$configkey = $this->settings['api_key'];

			// Build a SofortLibTransactionData instance, add the payment to the request and make the request
			$SofortLibTransactionData = new SofortLibTransactionData($configkey);
			$SofortLibTransactionData->addTransaction($tid);
			$SofortLibTransactionData->sendRequest();

			// Check if there are any errors with the request
			if ($SofortLibTransactionData->isError()) {
				throw new Exception($log . "Sofort kennt Zahlung nicht");
			}

			$log .= "Sofort hat die Zahlung best&auml;tigt\n";

			// Check if the status is correct and the payment is done
			if ($SofortLibTransactionData->getStatus() != "untraceable" && $SofortLibTransactionData->getStatus() != "pending" && $SofortLibTransactionData->getStatus() != "received") {
				throw new Exception($log . "Status '" . $SofortLibTransactionData->getStatus() . "' falsch");
			}

			// Check if the payment is not already inserted in the system
			if (count($transactions->get(Array("subject" => "sofort|$tid"))) > 0) {
				throw new Exception($log . "Transaktions-ID bereits eingebucht");
			}

			// Check if the currency code is correct
			try {
				$curObj = new Currency($SofortLibTransactionData->getCurrency());
			} catch (CurrencyException $ex) {
				throw new Exception($log . "Unbekannte W&auml;hrung '" . $SofortLibTransactionData->getCurrency() . "'");
			}

			// Get payment subject and try to extract the user ID
			$subject = $SofortLibTransactionData->getReason(0, 1);

			if (strlen($CFG['CASHBOX_PREFIX']) >= 2 && $CFG['CASHBOX_ACTIVE'] && stripos($subject, $CFG['CASHBOX_PREFIX'] . "C") !== false) {
				$log .= "Cashbox-Transaktion\n";
				$user = "";

				$cashboxHash = substr($subject, stripos($subject, $CFG['CASHBOX_PREFIX'] . "C") + strlen($CFG['CASHBOX_PREFIX']), 8);
				$sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string($cashboxHash) . "'");
				if ($sql->num_rows != 1) {
					throw new Exception($log . "Transaktion nicht gefunden");
				}

				$cashboxInfo = $sql->fetch_object();
				$user = $cashboxInfo->user;
			} else {
				$user = str_replace($this->settings['prefix'], "", $subject);
			}

			$log .= "Benutzer: $user\n";

			if (strpos($SofortLibTransactionData->getReason(0, 0), "Sofort") === false) {
				throw new Exception($log . "Betreff unvollst&auml;ndig");
			}

			// Check if the extracted user exists
			if (!is_numeric($user) || $user < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->num_rows != 1) {
				throw new Exception($log . "Benutzer unbekannt");
			}

			if (!$this->canPay(User::getInstance($user, "ID"))) {
				throw new Exception("Benutzer nicht berechtigt");
			}

			// Get the payment amount (deduct any refunded amount for security reasons)
			$payment_amount = $payment_amount_orig = $SofortLibTransactionData->getAmount() - $SofortLibTransactionData->getAmountRefunded();
			$payment_amount = $curObj->convertFrom($payment_amount);
			$fees = $this->getFees($payment_amount);

			// Update the user credit and insert the transaction into the database
			$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1");
			$transactions->insert("sofort", $tid, $payment_amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);

			$log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

			// Insert the Sofort transaction in the open transactions table if wished so
			if ($this->settings['incoming'] === "true") {
				$db->query("INSERT INTO sofort_open_transactions (`user`, `amount`, `date`) VALUES (" . intval($user) . ", '" . $db->real_escape_string($converted) . "', " . time() . ")");
			}

			// Gather user information and send a notification email
			$userInfo = $db->query("SELECT ID, firstname, lastname, mail FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->fetch_object();
			$uI = User::getInstance($userInfo->mail);
			$sendLang = $uI->getLanguage();

			$mtObj = new MailTemplate("Guthabenaufladung");
			$title = $mtObj->getTitle($sendLang);
			$mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
			
			$maq->enqueue([
				"amount" => $curObj->getPrefix() . $payment_amount_orig . $curObj->getSuffix(),
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

			// Throw an exception for log purposes
			throw new Exception($log . "E-Mail gesendet");
		} catch (Exception $ex) {
			// Write the log into the database
			$raw_post_data = file_get_contents('php://input');
			$raw_post_array = explode('&', $raw_post_data);
			$data = "";
			foreach ($raw_post_array as $k => $v) {
				$data .= $db->real_escape_string($v . "\n");
			}

			$log = $db->real_escape_string($ex->getMessage());

			$db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'sofort')");
		}
	}

}

?>