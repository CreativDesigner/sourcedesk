<?php
// Class for making stripe payments (IDeal)

class StripeGIDealPG extends PaymentGateway {
	public static $shortName = "ideal_ideal";

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
			"public_key" => Array("type" => "text", "name" => $this->getLang('public_key')),
			"private_key" => Array("type" => "text", "name" => $this->getLang('private_key')),
		);
		$this->log = true;
		$this->cashbox = false;
	}

	public function makePayment() {
		global $db, $user, $CFG, $transactions, $var, $nfo, $cur, $raw_cfg;

		if (!$this->canPay($user)) {
			return;
		}

		if (isset($_GET['ideal_ideal'])) {
			$log = "System gestartet\n";
			try {
				require_once __DIR__ . "/../stripe/lib/init.php";
				\Stripe\Stripe::setApiKey($this->settings['private_key']);

				$charge = \Stripe\Charge::create(array(
					"amount" => $_GET['amount'],
					"currency" => "eur",
					"source" => $_GET['source'],
				));

				if ($charge->status != "succeeded") {
					throw new Exception("Zahlung fehlgeschlagen");
				}

				try {
					$curObj = new Currency("eur");
				} catch (CurrencyException $ex) {
					throw new Exception("Unbekannte W&auml;hrung: " . $currency);
				}

				$amount = $payment_amount = $curObj->convertBack($_GET['amount'] / 100);
				$fees = $this->getFees($amount);

				// Update the user credit and insert the transaction in the database
				$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($user->get()['ID']) . "' LIMIT 1");
				$transactions->insert("ideal_ideal", $_GET['source'], $amount, $user->get()['ID'], "", 1);
				$log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt";

				$userInfo = $db->query("SELECT * FROM clients WHERE ID = " . $user->get()['ID'])->fetch_object();

				// Send admin notification
				if (($ntf = AdminNotification::getInstance("IPN-Gutschrift")) !== false) {
					$ntf->set("amount", $cur->infix($nfo->format($payment_amount), $cur->getBaseCurrency()));
					$ntf->set("fees", $cur->infix($nfo->format($fees), $cur->getBaseCurrency()));
					$ntf->set("gateway", $this->getLang("name"));
					$ntf->set("customer", $userInfo->firstname . " " . $userInfo->lastname);
					$ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $userInfo->ID);
					$ntf->send();
				}

				$uI = User::getInstance($userInfo->ID, "ID");

				// Invoice fees
				if ($fees > 0) {
					$uI->invoiceFees($fees);
					$log .= "\nGeb&uuml;hren gebucht";
				}

				$uI->applyCredit();

				throw new Exception("", 1);
			} catch (Exception $ex) {
				$log .= $ex->getMessage();
				$log = $db->real_escape_string($log);
				$_POST['currency'] = $currency;
				$_POST['userid'] = $user->get()['ID'];
				$data = $db->real_escape_string(print_r($_POST, true));
				$db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'ideal_ideal')");
				if ($ex->getCode() == 1) {
					$this->global = "<div class='alert alert-success'>" . $this->getLang('done') . "</div>";
				} else {
					$this->global = "<div class='alert alert-danger'>" . $this->getLang('failed') . "</div>";
				}

			}
		}
	}

	public function getPaymentForm($amount = null) {
		global $user, $CFG, $var, $nfo, $cur, $lang;
		$c = $var['currencyObj'];

		$append = "";
		if ($this->settings['excl'] == "1") {
			$append = "_EXCL";
		}

		$fees = "";
		if ($this->settings['fix'] != 0 && $this->settings['percent'] != 0) {
			$fees = " " . str_replace(Array("%p", "%a"), Array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_BOTH' . $append]);
		} else if ($this->settings['fix'] != 0) {
			$fees = " " . str_replace(Array("%p", "%a"), Array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_FIX' . $append]);
		} else if ($this->settings['percent'] != 0) {
			$fees = " " . str_replace(Array("%p", "%a"), Array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_PERCENT' . $append]);
		}

		ob_start();?>
		<script type="text/javascript">
	        document.write("<p>");
	        document.write("<form method=\"POST\" class=\"form-inline\" onsubmit=\"return false;\" id=\"ideal_form\"><div class=\"input-group\" id=\"payment_amount_group\"><?php if (!empty($c->getPrefix())) {?><span class=\"input-group-addon\"><?=$c->getPrefix();?></span> <?php }?><input type=\"text\" id=\"ideal_amount\" name=\"ideal_amount\" value=\"<?=$amount !== null ? $amount : "";?>\" placeholder=\"<?=$this->getLang('amount');?>\" onkeydown=\"stripeFeesAdded = 0;\" style=\"max-width:80px\" class=\"form-control\"><?php if (!empty($c->getSuffix())) {?> <span class=\"input-group-addon\"><?=$c->getSuffix();?></span><?php }?><\/div>&nbsp;<input value=\"<?=$this->getLang('pay') . $fees;?>\" type=\"submit\" id=\"ideal_payment\" onclick=\"addStripeFeesIDEAL()\" class=\"btn btn-primary\" \/><\/form>");
	        document.write("<\/p>");

	        var ideal_public = '<?=$this->settings['public_key'];?>';
	        var userId = '<?=$user->get()['ID'];?>';
	        var userMail = '<?=$user->get()['mail'];?>';
	        var ideal_currency = '<?=$var['myCurrency'];?>';
	        var prefix = '<?=$this->getLang('prefix');?>';
	        var pagename = '<?=$CFG['PAGENAME'];?>';

	        function stripeResponseHandlerIDEAL(status, response) {
	        	window.location = response.redirect.url;
			}

	        stripeFeesAddedIDEAL = 0;
	        function addStripeFeesIDEAL() {
	        	<?php if ($this->settings['excl'] == 1) {?>
	        	if(!stripeFeesAddedIDEAL){
		        	var percent = <?=$this->settings['percent'];?>;
		        	var fix  	= Number(<?=$cur->convertAmount(null, $this->settings['fix']);?>);
		        	var value   = Number(document.getElementById("ideal_amount").value.replace(',', '.'));

					value += value * percent / 100;
					value += fix;
					value  = String(Number(Math.ceil(value * 100) / 100).toFixed(2));
					document.getElementById("ideal_amount").value = value<?=$CFG['NUMBER_FORMAT'] == "de" ? ".replace('.', ',')" : "";?>;
					stripeFeesAddedIDEAL = 1;
				}
				<?php }?>

				Stripe.setPublishableKey(ideal_public);
				Stripe.source.create({
				  type: 'ideal',
				  amount: $("#ideal_amount").val().replace(",", ".") * 100,
				  currency: 'eur',
				  owner: {
				    name: '<?=$user->get()['name'];?>',
				  },
				  redirect: {
				    return_url: '<?=$CFG['PAGEURL'];?>credit?ideal_ideal=1&amount=' + ($("#ideal_amount").val().replace(",", ".") * 100),
				  },
				}, stripeResponseHandlerIDEAL);
	        }
		</script>

		<noscript><div class="alert alert-info"><?=$this->getLang('no_js');?></div></noscript>
		<?php
$res = ob_get_contents();
		ob_end_clean();
		return $res;
	}

	public function getPaymentHandler() {
		return false;
	}

	public function getIpnHandler() {
		return false;
	}

	public function getJavaScript() {
		return ["https://checkout.stripe.com/checkout.js", "https://js.stripe.com/v2/"];
	}

}

?>