<?php
// Class for making paypal transfers

class PayPalPG extends PaymentGateway
{
    public static $shortName = "paypal";

    public function __construct($language)
    {
        parent::__construct(self::$shortName);
        $this->language = $language;

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;
        $this->options = array(
            "address" => array("type" => "text", "name" => $this->getLang('address')),
            "prefix" => array("type" => "text", "name" => $this->getLang('prefix'), "help" => $this->getLang('prefix_hint'), "default" => $this->getLang('prefix_default')),
            "apiusername" => array("type" => "text", "name" => $this->getLang('apiusername'), "help" => $this->getLang("only_refunds")),
            "apipassword" => array("type" => "text", "name" => $this->getLang('apipassword'), "help" => $this->getLang("only_refunds")),
            "apisignature" => array("type" => "text", "name" => $this->getLang('apisignature'), "help" => $this->getLang("only_refunds")),
        );
        $this->log = true;
        $this->cashbox = true;
        $this->refunds = true;
    }

    public function getPaymentForm($amount = null)
    {
        global $cur, $CFG, $user, $var, $lang, $nfo;

        $fees = $addon = "";
        if ($this->settings['excl'] == 1) {
            $addon = "_EXCL";
        }

        if ($this->settings['fix'] != 0 && $this->settings['percent'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_BOTH' . $addon]);
        } else if ($this->settings['fix'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_FIX' . $addon]);
        } else if ($this->settings['percent'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_PERCENT' . $addon]);
        }

        ob_start();
        ?>
		<form class="form-inline" action="https://www.paypal.com/cgi-bin/webscr" method="post">
		    <input type="hidden" name="cmd" value="_xclick">
		    <input type="hidden" name="business" value="<?=$this->settings['address'];?>">
		    <input type="hidden" name="item_name" value="<?=$this->settings['prefix'] . $user->get()['ID'];?>">
		    <input type="hidden" name="no_shipping" value="1">
		    <input type="hidden" name="no_note" value="1">
		    <input type="hidden" name="currency_code" value="<?=$var['myCurrency'];?>">
		    <input type="hidden" name="notify_url" value="<?=$CFG['PAGEURL'];?>ipn/paypal">
		    <input type="hidden" name="return" value="<?=$CFG['PAGEURL'];?>credit?okay">
		    <input type="hidden" name="cancel_return" value="<?=$CFG['PAGEURL'];?>credit?cancel">
		    <input type="hidden" name="lc" value="<?=$lang['LOCALCODE'];?>" />
		    <div class="input-group"><?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span> <?php }?><input type="text" name="amount" id="paypal_amount" value="<?=$amount !== null ? $amount : '';?>" onchange="paypalFeesAdded = 0;" placeholder="<?=$this->getLang('amount');?>" style="max-width:80px" class="form-control"><?php if (!empty($cur->getSuffix())) {?> <span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?></div>
		    <input type="submit" onclick="addPayPalFees();" value="<?=$this->getLang('pay');?><?=$fees;?>" class="btn btn-primary">
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
				<?php } else {?>
				document.getElementById("paypal_amount").value = document.getElementById("paypal_amount").value.replace(',', '.');
				<?php }?>
	        }
	    </script>
		<?php
$code = ob_get_contents();
        ob_end_clean();
        return $code;
    }

    public function makeCashboxPayment($hash)
    {
        global $cur, $CFG, $user, $var, $lang, $nfo;

        $params = array(
            "cmd" => "_xclick",
            "business" => $this->settings['address'],
            "item_name" => $CFG['CASHBOX_PREFIX'] . $hash,
            "no_shipping" => "1",
            "no_note" => "1",
            "currency_code" => $var['myCurrency'],
            "notify_url" => $CFG['PAGEURL'] . "ipn/paypal",
            "return" => $CFG['PAGEURL'] . 'cashbox/' . $_REQUEST['user'] . '/' . $_REQUEST['hash'] . '?okay&amount=' . urlencode($_POST['amount']) . '&payment_method=' . urlencode($_POST['payment_method']) . '&subject=' . urlencode($_POST['subject']),
            "cancel_return" => $CFG['PAGEURL'] . 'cashbox/' . $_REQUEST['user'] . '/' . $_REQUEST['hash'] . '?cancel&amount=' . urlencode($_POST['amount']) . '&payment_method=' . urlencode($_POST['payment_method']) . '&subject=' . urlencode($_POST['subject']),
            "amount" => $this->addFees($nfo->phpize($_POST['amount']), true),
            "lc" => $CFG['LOCALCODE'],
        );

        $url = "https://www.paypal.com/cgi-bin/webscr?";
        foreach ($params as $k => $v) {
            $url .= urlencode($k) . "=" . urlencode($v) . "&";
        }

        $url = rtrim($url, "&");

        header('Location: ' . $url);
        exit;
    }

    public function getPaymentHandler()
    {
        return false;
    }

    public function getIpnHandler()
    {
        global $CFG, $db, $transactions, $cur, $maq, $raw_cfg, $nfo, $dfo;

        $log = "System gestartet\n";

        try {
            // Get the transmitted data and build an Array
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            $myPost = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode('=', $keyval);
                if (count($keyval) == 2) {
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
                }

            }

            // Build the URI to validate the transaction and callback details at PayPal
            $req = 'cmd=_notify-validate';
            if (function_exists('get_magic_quotes_gpc')) {
                $get_magic_quotes_exists = true;
            }

            foreach ($myPost as $key => $value) {
                if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                    $value = urlencode(stripslashes($value));
                } else {
                    $value = urlencode($value);
                }

                $req .= "&$key=$value";
            }

            // Post IPN data back to PayPal to validate
            $ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
            $res = curl_exec($ch);

            // Try to perform cURL call
            if (curl_errno($ch)) {
                // There was an error with cURL, log it and exit the script
                $log .= "cURL-Fehler: " . curl_error($ch);
                curl_close($ch);

                throw new Exception($log);
            }

            curl_close($ch);

            // Check result
            if (strcmp($res, "VERIFIED") == 0) {
                // The IPN request was sent by PayPal
                $log .= "PayPal hat die Zahlung best&auml;tigt\n";

                // Make variables local for easy handling
                $item_name = $_POST['item_name'];
                $item_number = $_POST['item_number'];
                $payment_status = $_POST['payment_status'];
                $payment_amount = $_POST['mc_gross'];
                $payment_currency = $_POST['mc_currency'];
                $txn_id = $_POST['txn_id'];
                $receiver_email = $_POST['receiver_email'];
                $payer_email = $_POST['payer_email'];

                // Check if the payment is okay
                if ($payment_status != "Completed") {
                    throw new Exception($log . "Zahlung ist nicht abgeschlossen");
                }

                // Check if the payment was not already inserted
                if (count($transactions->get(array("subject" => "paypal|$txn_id"))) > 0) {
                    throw new Exception($log . "Transaktions-ID bereits eingebucht");
                }

                // Check if the email of the receiver is correct
                if ($receiver_email != $this->settings['address']) {
                    throw new Exception($log . "Empf&auml;nger-Email falsch");
                }

                // Check the currency code
                try {
                    $curObj = new Currency($payment_currency);
                    $payment_amount = $cur->convertAmount($payment_currency, $payment_amount, $cur->getBaseCurrency());
                } catch (CurrencyException $ex) {
                    throw new Exception($log . "Unbekannte W&auml;hrung");
                }

                // Try to get the user from the item name
                if (strlen($CFG['CASHBOX_PREFIX']) >= 2 && $CFG['CASHBOX_ACTIVE'] && stripos($item_name, $CFG['CASHBOX_PREFIX'] . "C") !== false) {
                    $log .= "Cashbox-Transaktion\n";
                    $user = "";

                    $cashboxHash = substr($item_name, stripos($item_name, $CFG['CASHBOX_PREFIX'] . "C") + strlen($CFG['CASHBOX_PREFIX']), 8);
                    $sql = $db->query("SELECT * FROM `cashbox` WHERE `hash` = '" . $db->real_escape_string($cashboxHash) . "'");
                    if ($sql->num_rows != 1) {
                        throw new Exception($log . "Transaktion nicht gefunden");
                    }

                    $cashboxInfo = $sql->fetch_object();
                    $user = $cashboxInfo->user;
                } else {
                    $user = str_replace($this->settings['prefix'], "", $item_name);
                }
                $log .= "Benutzer: $user\n";

                // Check if the user exists
                if (!is_numeric($user) || $user < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->num_rows != 1) {
                    throw new Exception($log . "Benutzer unbekannt");
                }

                if (!$this->canPay(User::getInstance($user, "ID"))) {
                    throw new Exception("Benutzer nicht berechtigt");
                }

                $fees = $this->getFees($payment_amount);

                // Update the user credit and insert the transaction
                $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1");
                $transactions->insert("paypal", $txn_id, $payment_amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);

                $log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

                // Gather user informationen and send a notification email
                $userInfo = $db->query("SELECT ID, firstname, lastname, mail, language FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->fetch_object();
                $uI = User::getInstance($userInfo->ID, "ID");
                $sendLang = $uI->getLanguage();

                $mtObj = new MailTemplate("Guthabenaufladung");
                $title = $mtObj->getTitle($sendLang);
                $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
                
                $maq->enqueue([
					"amount" => $cur->infix($nfo->format($_POST['mc_gross']), $payment_currency),
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

                throw new Exception($log . "E-Mail gesendet");
            } else if (strcmp($res, "INVALID") == 0) {
                // If the payment was not confirmed by PayPal, end processing and log this circumstate
                throw new Exception($log . "PayPal kennt Zahlung nicht");
            }

        } catch (Exception $ex) {
            // Insert the call into log
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            $data = $db->real_escape_string(print_r($raw_post_array, true));

            $log = $db->real_escape_string($ex->getMessage());

            $db->query("INSERT INTO gateway_logs (`data`, `log`, `time`, `gateway`) VALUES ('$data', '$log', " . time() . ", 'paypal')");
        }
    }

    public function refundPayment($tid)
    {
        $postfields = array();
        $postfields["VERSION"] = "3.0";
        $postfields["METHOD"] = "RefundTransaction";
        $postfields["BUTTONSOURCE"] = "SOURCEDESK_REFUND";
        $postfields["USER"] = $this->settings['apiusername'];
        $postfields["PWD"] = $this->settings["apipassword"];
        $postfields["SIGNATURE"] = $this->settings["apisignature"];
        $postfields["TRANSACTIONID"] = $tid;
        $postfields["REFUNDTYPE"] = "Full";

        $ch = curl_init("https://api-3t.paypal.com/nvp");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
        $result = curl_exec($ch);
        curl_close($ch);

        $resultsarray2 = explode("&", $result);
        foreach ($resultsarray2 as $line) {
            $line = explode("=", $line);
            $resultsarray[$line[0]] = urldecode($line[1]);
        }

        if (strtoupper($resultsarray["ACK"]) == "SUCCESS") {
            return true;
        }

        return false;
    }

    public function canRefund()
    {
        return !empty($this->settings['apiusername']) && !empty($this->settings['apipassword']) && !empty($this->settings['apisignature']);
    }
}

?>