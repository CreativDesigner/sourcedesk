<?php
// Class for making stripe payments (Apple Pay)

class StripeApplePayPG extends PaymentGateway {
	public static $shortName = "stripe_applepay";

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

		if (isset($_GET['stripe_applepay'])) {
			$log = "System gestartet\n";
			try {
				require_once __DIR__ . "/../stripe/lib/init.php";
				\Stripe\Stripe::setApiKey($this->settings['private_key']);

				$charge = \Stripe\Charge::create(array(
					"amount" => $_POST['amount'],
					"currency" => $_POST['currency'],
					"token" => $_POST['token'],
				));

				if ($charge->status != "succeeded") {
					throw new Exception("Zahlung fehlgeschlagen");
				}

				try {
					$curObj = new Currency($_POST['currency']);
				} catch (CurrencyException $ex) {
					throw new Exception("Unbekannte W&auml;hrung: " . $currency);
				}

				$amount = $payment_amount = $curObj->convertBack($_POST['amount'] / 100);
				$fees = $this->getFees($amount);

				// Update the user credit and insert the transaction in the database
				$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($user->get()['ID']) . "' LIMIT 1");
				$transactions->insert("stripe_applepay", $_GET['source'], $amount, $user->get()['ID'], "", 1);
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
				$db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'stripe_applepay')");
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
	        document.write("<form method=\"POST\" class=\"form-inline\" onsubmit=\"return false;\" id=\"applepay_form\"><div class=\"alert alert-info\" id=\"apple-pay-hint\"><?=$this->getLang('hint');?></div><div class=\"input-group\" id=\"payment_amount_group\"><?php if (!empty($c->getPrefix())) {?><span class=\"input-group-addon\"><?=$c->getPrefix();?></span> <?php }?><input type=\"text\" id=\"applepay_amount\" name=\"applepay_amount\" value=\"<?=$amount !== null ? $amount : "";?>\" placeholder=\"<?=$this->getLang('amount');?>\" onkeydown=\"stripeFeesAdded = 0;\" style=\"max-width:80px\" class=\"form-control\"><?php if (!empty($c->getSuffix())) {?> <span class=\"input-group-addon\"><?=$c->getSuffix();?></span><?php }?><\/div>&nbsp;<input value=\"<?=$this->getLang('pay') . $fees;?>\" type=\"submit\" id=\"applepay_payment\" onclick=\"addStripeFeesApplePay()\" class=\"btn btn-primary\" \/><style>#apple-pay-button{display: none;background-color: black;background-image: -webkit-named-image(apple-pay-logo-white);background-size: 100% 100%;background-origin: content-box;background-repeat: no-repeat;width: 100%;height: 44px;padding: 10px 0;border-radius: 10px;}</style><button id=\"apple-pay-button\"></button><\/form>");
	        document.write("<\/p>");

	        var applepay_public = '<?=$this->settings['public_key'];?>';
	        var userId = '<?=$user->get()['ID'];?>';
	        var userMail = '<?=$user->get()['mail'];?>';
	        var applepay_currency = '<?=$var['myCurrency'];?>';
	        var prefix = '<?=$this->getLang('prefix');?>';
	        var pagename = '<?=$CFG['PAGENAME'];?>';

	        function stripeResponseHandlerApplePay(status, response) {
	        	window.location = response.redirect.url;
			}

	        stripeFeesAddedApplePay = 0;
	        function addStripeFeesApplePay() {
				Stripe.setPublishableKey(applepay_public);

				Stripe.applePay.checkAvailability(function(available) {
				  if (available) {
				    document.getElementById('apple-pay-button').style.display = 'block';
				    document.getElementById('apple-pay-hint').style.display = 'none';
				    document.getElementById('applepay_amount').style.display = 'none';
				    document.getElementById('applepay_payment').style.display = 'none';
				    document.getElementById('apple-pay-button').addEventListener('click', beginApplePay);
				  } else {
				  	alert("<?=$this->getLang('notavailable');?>");
				  }
				});

				function beginApplePay(){
					<?php if ($this->settings['excl'] == 1) {?>
		        	if(!stripeFeesAddedApplePay){
			        	var percent = <?=$this->settings['percent'];?>;
			        	var fix  	= Number(<?=$cur->convertAmount(null, $this->settings['fix']);?>);
			        	var value   = Number(document.getElementById("applepay_amount").value.replace(',', '.'));

						value += value * percent / 100;
						value += fix;
						value  = String(Number(Math.ceil(value * 100) / 100).toFixed(2));
						document.getElementById("applepay_amount").value = value<?=$CFG['NUMBER_FORMAT'] == "de" ? ".replace('.', ',')" : "";?>;
						stripeFeesAddedApplePay = 1;
					}
					<?php }?>

					var paymentRequest = {
					    countryCode: '<?=$user->get()['country'];?>',
					    currencyCode: '<?=$var['myCurrency'];?>',
					    total: {
					      label: '<?=$CFG['PAGENAME'];?>',
					      amount: value
					    }
					};

					var session = Stripe.applePay.buildSession(paymentRequest, function(result, completion) {
					    $.post('?stripe_applepay=1', {
					    	"token": result.token.id,
					    	"amount": value,
							"currency": '<?=$var['myCurrency'];?>',
							"csrf_token": "<?=CSRF::raw(); ?>",
					    }).done(function() {
						    completion(ApplePaySession.STATUS_SUCCESS);
						    window.location.href = '?okay';
					    }).fail(function() {
					      	completion(ApplePaySession.STATUS_FAILURE);
					      	window.location.href = '?cancel';
					    });
					}, function(error) {
				    	console.log(error.message);
				  	});

    				session.begin();
				}
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