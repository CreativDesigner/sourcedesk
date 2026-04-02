<?php
// Class for making paypal transfers

class TCOPayPalPG extends PaymentGateway {
	public static $shortName = "2co_paypal";

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
			"sid" => Array("type" => "text", "name" => $this->getLang('sid')),
			"secret" => Array("type" => "text", "name" => $this->getLang('secret_word')),
			"prefix" => Array("type" => "text", "name" => $this->getLang('prefix'), "help" => $this->getLang('prefix_hint'), "default" => $this->getLang('prefix_default')),
		);
		$this->log = true;
		$this->cashbox = true;
	}

	public function getPaymentForm($amount = null) {
		global $cur, $CFG, $user, $var, $lang, $nfo, $db;

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

		$col = strpos($var['lang_active'], "deutsch") !== false ? "gr" : "";

		$countrySql = $db->query("SELECT `name` FROM `client_countries` WHERE active = 1 AND ID = " . $user->get()['country']);
		$defaultSql = $db->query("SELECT `name` FROM `client_countries` WHERE active = 1 AND ID = " . intval($CFG['DEFAULT_COUNTRY']));
		require_once __DIR__ . "/country_codes.php";
		$country = $countrySql->num_rows == 1 ? find2co_cc($countrySql->fetch_object()->name) : ($defaultSql->num_rows == 1 ? find2co_cc($defaultSql->fetch_object()->name) : "");

		ob_start();
		?>
		<form class="form-inline" action="https://www.2checkout.com/checkout/purchase" method="post" id="paypal_form" onsubmit="$('#timeModal').modal('show'); return false;">
		    <input type="hidden" name="sid" value="<?=$this->settings['sid'];?>">
		    <input type="hidden" name="mode" value="2CO">
		    <input type="hidden" name="return_url" value="<?=$CFG['PAGEURL'];?>credit?cancel">
		    <input type="hidden" name="x_receipt_link_url" value="<?=$CFG['PAGEURL'];?>credit?okay">
		    <input type="hidden" name="li_0_type" value="product">
		    <input type="hidden" name="li_0_name" value="<?=$this->settings['prefix'];?><?=$user->get()['ID'];?>">
		    <input type="hidden" name="li_0_quantity" value="1">
		    <input type="hidden" name="li_0_tangible" value="N">
		    <input type="hidden" name="email" value="<?=$user->get()['mail'];?>">
		    <input type="hidden" name="currency_code" value="<?=$var['myCurrency'];?>">
		    <input type="hidden" name="lang" value="<?=$col;?>">
		    <input type="hidden" name="paypal_direct" value="Y">
		    <input type="hidden" name="card_holder_name" value="<?=$user->get()['name'];?>">
			<input type="hidden" name="street_address" value="<?=$user->get()['street'] . " " . $user->get()['street_number'];?>">
			<input type="hidden" name="city" value="<?=$user->get()['city'];?>">
			<input type="hidden" name="zip" value="<?=$user->get()['postcode'];?>">
			<input type="hidden" name="phone" value="<?=$user->get()['telephone'];?>">
			<input type="hidden" name="country" value="<?=$country;?>">
			<input type="hidden" name="purchase_step" value="payment-method">

		    <div class="input-group"><?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span> <?php }?><input type="text" name="li_0_price" id="paypal_amount" onchange="paypalFeesAdded = 0;" placeholder="<?=$this->getLang('amount');?>" value="<?=$amount !== null ? $amount : '';?>" style="max-width:80px" class="form-control"><?php if (!empty($cur->getSuffix())) {?> <span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?></div>
		    <input type="submit" style="display: none;" onsubmit="return false;" />
		    <input type="button" value="<?=$this->getLang('pay');?><?=$fees;?>" class="btn btn-primary" data-toggle="modal" data-target="#timeModal">

		    <div class="modal fade" id="timeModal" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <h4 class="modal-title"><?=$this->getLang('time_title');?></h4>
			      </div>
			      <div class="modal-body">
			        <p style="text-align: justify;"><?=$this->getLang('time_warning');?></p>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->getLang('time_cancel');?></button>
			        <button type="button" class="btn btn-primary" onclick="addPayPalFees(); submitPayPalForm();"><?=$this->getLang('time_do');?></button>
			      </div>
			    </div>
			  </div>
			</div>
	    </form>

	    <script type="text/javascript">
    		paypalFeesAdded = 0;
	        function addPayPalFees() {
	        	<?php if ($this->settings['excl'] == 1) {?>
        		if(!paypalFeesAdded){
		        	var percent = <?=$this->settings['percent'];?>;
		        	var fix  	= Number(<?=$cur->convertAmount(null, $this->settings['fix']);?>);
		        	var value   = Number(document.getElementById("paypal_amount").value.replace(',', '.'));

					value += value * percent / 100;
					value += fix;
					value  = String(Number(Math.ceil(value * 100) / 100).toFixed(2));
					document.getElementById("paypal_amount").value = value;
					paypalFeesAdded = 1;
				}
				<?php }?>
	        }

	        function submitPayPalForm() {
	        	document.getElementById("paypal_form").submit();
	        }
	    </script>
		<?php
$code = ob_get_contents();
		ob_end_clean();
		return $code;
	}

	public function makeCashboxPayment($hash) {
		global $CFG, $var, $lang, $nfo;

		$col = strpos($var['lang_active'], "deutsch") !== false ? "gr" : "";

		$params = Array(
			"sid" => $this->settings['sid'],
			"mode" => "2CO",
			"return_url" => $CFG['PAGEURL'] . "credit?cancel",
			"x_receipt_link_url" => $CFG['PAGEURL'] . "credit?okay",
			"li_0_type" => "product",
			"li_0_name" => $CFG['CASHBOX_PREFIX'] . $hash,
			"li_0_quantity" => "1",
			"li_0_tangible" => "N",
			"li_0_price" => $this->addFees($nfo->phpize($_POST['amount']), true),
			"currency_code" => $var['myCurrency'],
			"lang" => $col,
			"paypal_direct" => "Y",
		);

		$url = "https://www.2checkout.com/checkout/purchase?";
		foreach ($params as $k => $v) {
			$url .= urlencode($k) . "=" . urlencode($v) . "&";
		}

		$url = rtrim($url, "&");

		header('Location: ' . $url);
		exit;
	}

	public function getPaymentHandler() {
		return false;
	}

	public function getIpnHandler() {
		global $CFG, $db, $transactions, $cur, $maq, $nfo, $raw_cfg;

		$log = "System gestartet\n";

		try {
			$params = Array("sale_id", "vendor_id", "invoice_id", "invoice_status", "fraud_status", "cust_currency", "invoice_cust_amount", "item_name_1", "md5_hash");
			foreach ($params as $p) {
				if (!isset($_REQUEST[$p])) {
					throw new Exception($p . "fehlt");
				}
			}

			// Check for the hash
			if ($_REQUEST['md5_hash'] != strtoupper(md5($_REQUEST['sale_id'] . $_REQUEST['vendor_id'] . $_REQUEST['invoice_id'] . $this->settings['secret']))) {
				throw new Exception("Hash falsch");
			}

			$t = $transactions->get(Array("subject" => "2co_paypal|" . $_REQUEST['sale_id']));
			if (count($t) > 0) {
				$tInfo = (object) array_values($t)[0];
			}

			if (isset($tInfo) && $tInfo->waiting == 0) {
				throw new Exception("Transaktions-ID bereits eingebucht");
			}

			// Check the currency code
			try {
				$curObj = new Currency($_REQUEST['cust_currency']);
				$payment_amount = $curObj->convertBack($_REQUEST['invoice_cust_amount']);
			} catch (CurrencyException $ex) {
				throw new Exception("Unbekannte W&auml;hrung");
			}

			// Try to get the user from the item name
			$item_name = $_REQUEST['item_name_1'];
			if (strlen($CFG['CASHBOX_PREFIX']) >= 2 && $CFG['CASHBOX_ACTIVE'] && stripos($item_name, $CFG['CASHBOX_PREFIX'] . "C") !== false) {
				$log .= "Cashbox-Transaktion\n";
				$user = "";

				$cashboxHash = substr($item_name, stripos($item_name, $CFG['CASHBOX_PREFIX'] . "C") + strlen($CFG['CASHBOX_PREFIX']), 8);
				$sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string($cashboxHash) . "'");
				if ($sql->num_rows != 1) {
					throw new Exception("Transaktion nicht gefunden");
				}

				$cashboxInfo = $sql->fetch_object();
				$user = $cashboxInfo->user;
			} else {
				$user = str_replace($this->settings['prefix'], "", $item_name);
			}
			$log .= "Benutzer: $user\n";

			// Check if the user exists
			if (!is_numeric($user) || $user < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->num_rows != 1) {
				throw new Exception("Benutzer unbekannt");
			}

			if (!$this->canPay(User::getInstance($user, "ID"))) {
				throw new Exception("Benutzer nicht berechtigt");
			}

			// Check for payment status
			if ($_REQUEST['invoice_status'] == "declined" || $_REQUEST['fraud_status'] == "fail") {
				if (isset($tInfo) && $tInfo->waiting) {
					$transactions->delete($tInfo->ID);
				}

				throw new Exception("Zahlung fehlgeschlagen");
			} else if ($_REQUEST['invoice_status'] != "deposited" || $_REQUEST['fraud_status'] != "pass") {
				if (!isset($tInfo)) {
					$transactions->insert("2co_paypal", $_REQUEST['sale_id'], $payment_amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1, 1);
				}

				throw new Exception("Zahlung ausstehend");
			}
			$log .= "Zahlung erfolgreich\n";

			// Update the user credit and insert the transaction
			$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1");
			if (!isset($tInfo)) {
				$transactions->insert("2co_paypal", $_REQUEST['sale_id'], $payment_amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);
			} else {
				$db->query("UPDATE client_transactions SET waiting = 0 WHERE ID = " . $tInfo->ID);
			}

			$log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

			$fees = $this->getFees($payment_amount);

			// Gather user informationen and send a notification email
			$userInfo = $db->query("SELECT ID, firstname, lastname, mail, language FROM clients WHERE ID = " . intval($user) . " LIMIT 1")->fetch_object();
			$uI = User::getInstance(intval($user), "ID");
			$sendLang = $uI->getLanguage();

			$mtObj = new MailTemplate("Guthabenaufladung");
			$title = $mtObj->getTitle($sendLang);
			$mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
			
			$maq->enqueue([
				"amount" => $cur->infix($nfo->format($cur->convertAmount(null, $payment_amount, $_POST['cust_currency'])), $_POST['cust_currency']),
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

			throw new Exception("E-Mail gesendet");
		} catch (Exception $ex) {
			$data = $db->real_escape_string(print_r(array_merge($_POST, $_GET), true));

			$log = $db->real_escape_string($log . $ex->getMessage());

			$db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", '2co_paypal')");
		}
	}

}

?>