<?php
// Class for making amazon payments

class AmazonPayPG extends PaymentGateway {
	public static $shortName = "amazonpay";

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
			"merchant_id" => Array("type" => "text", "name" => $this->getLang('mid')),
			"access_key" => Array("type" => "text", "name" => $this->getLang('acc')),
			"secret_key" => Array("type" => "text", "name" => $this->getLang('sec')),
			"client_id" => Array("type" => "text", "name" => $this->getLang('cid')),
		);
		$this->payment_handler = true;
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

		ob_start();
		?>
		<form class="form-inline" action="<?=$CFG['PAGEURL'];?>credit/pay/amazonpay" method="post">
		    <div class="input-group"><?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span> <?php }?><input type="text" name="amount" placeholder="<?=$nfo->placeholder();?>" value="<?=$amount !== null ? $amount : '';?>" style="max-width:80px" class="form-control"><?php if (!empty($cur->getSuffix())) {?> <span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?></div>
		    <input type="submit" value="<?=$this->getLang('pay');?><?=$fees;?>" class="btn btn-primary">
    </form>
		<?php
$code = ob_get_contents();
		ob_end_clean();
		return $code;
	}

	public function getPaymentHandler() {
		global $var, $user, $CFG, $nfo, $lang, $title, $tpl, $cur, $transactions, $maq, $db, $CFG;

		if (!isset($_POST['amount']) && isset($_GET['amount']) && doubleval($_GET['amount']) == $_GET['amount'] && $_GET['amount'] >= 1) {
			$_POST['amount'] = $nfo->format($_GET['amount']);
		}

		if (!$nfo->phpize($_POST['amount']) || is_double($nfo->phpize($_POST['amount'])) || $nfo->phpize($_POST['amount']) < 1) {
			$title = $lang['ERROR']['TITLE'];
			$tpl = "error";
			$var['error'] = str_replace("%m", $cur->infix($nfo->format(1)), $this->getLang("ERR"));
			return;
		}

		$var['gateLang'] = $this->getLang();
		$var['gateOpt'] = $this->settings;
		$var['amount'] = $nfo->phpize($_POST['amount']);
		$var['amountf'] = $cur->infix($nfo->format($this->addFees($nfo->phpize($_POST['amount']))));
		$var['logged'] = false;
		$amount = $this->addFees($var['amount']);

		if (isset($_GET['access_token'])) {
			$var['logged'] = true;
		}

		if (isset($_POST['orderid'])) {
			require __DIR__ . "/lib/Client.php";
			$config = Array(
				'merchant_id' => $this->settings['merchant_id'],
				'access_key' => $this->settings['access_key'],
				'secret_key' => $this->settings['secret_key'],
				'client_id' => $this->settings['client_id'],
				'currency_code' => $var['myCurrency'],
				'region' => 'de',
				'sandbox' => false,
			);

			if (!$this->canPay($user)) {
				return;
			}

			$client = new \PayWithAmazon\Client($config);
			$requestParameters = array();
			$requestParameters['amazon_order_reference_id'] = $_POST['orderid'];
			$requestParameters['amount'] = $amount;
			$requestParameters['currency_code'] = $var['myCurrency'];
			$requestParameters['seller_note'] = $this->getLang("PREFIX_DEFAULT") . $user->get()['ID'];
			$requestParameters['seller_order_id'] = $user->get()['ID'] . "-" . time() . "-1";
			$requestParameters['store_name'] = $CFG['PAGENAME'];
			$client->SetOrderReferenceDetails($requestParameters);

			if ($client->success) {
				$client->confirmOrderReference($requestParameters);
				if ($client->success) {
					$requestParameters['authorization_amount'] = $amount;
					$requestParameters['authorization_reference_id'] = $user->get()['ID'] . "-" . time() . "-2";
					$requestParameters['seller_authorization_note'] = 'Authorizing payment';
					$requestParameters['transaction_timeout'] = 0;

					$response = $client->authorize($requestParameters);
					$response = json_decode($response->toJson());

					if ($client->success) {
						$requestParameters['amazon_authorization_id'] = $response->AuthorizeResult->AuthorizationDetails->AmazonAuthorizationId;
						$requestParameters['capture_amount'] = $amount;
						$requestParameters['currency_code'] = $var['myCurrency'];
						$requestParameters['capture_reference_id'] = $user->get()['ID'] . "-" . time() . "-3";

						$response = $client->capture($requestParameters);
						$response = json_decode($response->toJson());

						if ($client->success && $response->CaptureResult->CaptureDetails->CaptureStatus->State == "Completed") {
							$user->set(["credit" => $user->get()['credit'] + $cur->convertAmount(null, $amount, null)]);
							$transactions->insert("amazonpay", $_POST['orderid'], $cur->convertAmount(null, $amount, null), $user->get()['ID']);
							$fees = $this->getFees($cur->convertAmount(null, $amount, null));

							$userInfo = $db->query("SELECT ID, firstname, lastname, mail, language FROM clients WHERE ID = " . intval($user->get()['ID']) . " LIMIT 1")->fetch_object();
							$uI = $user;
							$sendLang = $uI->getLanguage();

							$mtObj = new MailTemplate("Guthabenaufladung");
							$title = $mtObj->getTitle($sendLang);
							$mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
							
							$maq->enqueue([
								"amount" => $cur->infix($nfo->format($amount), $var['myCurrency']),
								"processor" => $this->getLang("name"),
							], $mtObj, $userInfo->mail, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userInfo->ID, true, 0, 0, $mtObj->getAttachments($sendLang));

							if (($ntf = AdminNotification::getInstance("IPN-Gutschrift")) !== false) {
								$ntf->set("amount", $cur->infix($nfo->format($cur->convertAmount(null, $amount, null)), $cur->getBaseCurrency()));
								$ntf->set("fees", $cur->infix($nfo->format($fees), $cur->getBaseCurrency()));
								$ntf->set("gateway", $this->getLang("name"));
								$ntf->set("customer", $userInfo->firstname . " " . $userInfo->lastname);
								$ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $userInfo->ID);
								$ntf->send();
							}

							if ($fees > 0) {
								$uI->invoiceFees($fees);
							}

							$uI->applyCredit();

							$var['suc'] = 1;
						} else {
							$var['err'] = 1;
						}
					} else {
						$var['err'] = 1;
					}
				} else {
					$var['err'] = 1;
				}
			} else {
				$var['err'] = 1;
			}
		}

		$title = $this->getLang('name');
		$tpl = __DIR__ . "/pay.tpl";
	}

	public function getIpnHandler() {}
}
