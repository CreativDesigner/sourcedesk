<?php
// Class for making barzahlen transfers

class BarzahlenPG extends PaymentGateway {
	public static $shortName = "barzahlen";

	public function __construct($language) {
		global $CFG;
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
			"shop_id" => Array("type" => "text", "name" => $this->getLang('shop_id')),
			"payment_key" => Array("type" => "text", "name" => $this->getLang('payment_key')),
			"notification_key" => Array("type" => "text", "name" => $this->getLang('notification_key')),
		);
		$this->log = true;
		$this->admin_warning = str_replace("%u", $CFG['PAGEURL'] . "ipn/barzahlen", $this->getLang("admin_hint"));
		$this->payment_handler = true;
	}

	public function getPaymentForm($amount = null) {
		global $cur, $CFG, $lang, $nfo, $lang, $var;

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

		$code = "<p><form method=\"POST\" target=\"_blank\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/barzahlen\">
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
		global $var, $user, $nfo, $lang, $title, $tpl, $raw_cfg;

		if (!$this->canPay($user)) {
			return;
		}

		// Get library
		if (!include_once (__DIR__ . "/lib/loader.php")) {
			die("Barzahlen library not found.");
		}

		// Init payment
		$api = new Barzahlen_Api($this->settings['shop_id'], $this->settings['payment_key'], false);

		// Get user data
		$u = (object) $user->get();

		// Build a payment request with user properties
		$payment = new Barzahlen_Request_Payment($u->mail, $u->street, $u->postcode, $u->city, $lang['LOCALCODE'], $this->addFees($nfo->phpize($_POST['amount']), true));

		try {
			// Try to build transaction and redirect user to PDF
			$api->handleRequest($payment);
			header('Location: ' . $payment->getPaymentSlipLink());
			exit;
		} catch (Barzahlen_Exception $e) {
			// If error occured, send back to credit page
			$title = $lang['ERROR']['TITLE'];
			$tpl = "error";
			$var['error'] = $this->getLang('error');
			$var['show_back'] = false;
		}
	}

	public function getIpnHandler() {
		global $CFG, $db, $transactions, $cur, $maq, $raw_cfg;

		$log = "System gestartet\n";

		try {
			require_once __DIR__ . "/lib/loader.php";

			// Try to build a notification object with GET parameters
			$notification = new Barzahlen_Notification($this->settings['shop_id'], $this->settings['notification_key'], $_GET);
			$notification->validate();

			// Check with notification API key if transaction details are valid
			if (!$notification->isValid()) {
				throw new Exception($log . "Transaktion nicht bekannt");
			}

			// Check if the transaction is paid
			if ($notification->getState() != "paid") {
				throw new Exception($log . "Zahlung nicht i.O.");
			}

			$log .= "Barzahlen hat die Zahlung best&auml;tigt\n";

			// Check if the transaction is already present in the system
			if (count($transactions->get(Array("subject" => "barzahlen|" . $_GET['transaction_id']))) > 0) {
				throw new Exception($log . "Transaktions-ID bereits eingebucht");
			}

			// The currency should be known
			try {
				$curObj = new Currency($_GET['currency']);
			} catch (CurrencyException $ex) {
				throw new Exception($log . "Unbekannte W&auml;hrung (" . $_GET['currency'] . ")");
			}

			// Try to select the user for the payment from the database (via email address; changes are not considered)
			if (!isset($_GET['customer_email']) || $db->query("SELECT ID FROM clients WHERE mail = '" . $db->real_escape_string($_GET['customer_email']) . "' LIMIT 1")->num_rows != 1) {
				throw new Exception($log . "Benutzer unbekannt (" . $db->real_escape_string($_GET['customer_email']) . ")");
			}

			if (!$this->canPay(User::getInstance($_GET['customer_email']))) {
				throw new Exception("Benutzer nicht berechtigt");
			}

			// Sanitize the payment amount
			$payment_amount = $curObj->convertFrom((double) $_GET['amount']);
			$fees = $this->getFees($payment_amount);

			// Insert the credit and the transaction details into the system
			$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE mail = '" . $db->real_escape_string($_GET['customer_email']) . "' LIMIT 1");
			$userInfo = $db->query("SELECT ID, firstname, lastname, mail FROM clients WHERE mail = '" . $db->real_escape_string($_GET['customer_email']) . "' LIMIT 1")->fetch_object();
			$transactions->insert("barzahlen", $_GET['transaction_id'], $payment_amount, $userInfo->ID, "", 1);

			$log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

			// Send notification email
			$uI = User::getInstance($db->real_escape_string($_GET['customer_email']));
			$sendLang = $uI->getLanguage();

			$mtObj = new MailTemplate("Guthabenaufladung");
			$title = $mtObj->getTitle($sendLang);
			$mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
			
			$maq->enqueue([
				"amount" => $cur->infix($nfo->format($payment_amount), $cur->getBaseCurrency()),
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

			// Save log at the end
			throw new Exception($log . "E-Mail gesendet");
		} catch (Exception $ex) {
			// An exception was thrown (at least at the end of the script), so we insert the log in the database
			$data = $db->real_escape_string(print_r($_GET, true));
			$log = $db->real_escape_string($ex->getMessage());

			$db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'barzahlen')");
		}
	}

}

?>