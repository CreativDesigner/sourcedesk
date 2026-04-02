<?php
// Class for making Skrill Quick Checkout payments

class SkrillPG extends PaymentGateway
{
    public static $shortName = "skrill";

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
            "email" => array("type" => "text", "name" => $this->getLang('email')),
            "secret_word" => array("type" => "text", "name" => $this->getLang('secret_word')),
        );
        $this->log = true;
        $this->payment_handler = true;
        $this->cashbox = true;
    }

    public function getPaymentForm($amount = null)
    {
        global $cur, $CFG, $nfo, $lang, $var;

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

        $code = "<p><form method=\"POST\" class=\"form-inline\" action=\"" . $CFG['PAGEURL'] . "credit/pay/skrill\">
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

    public function getPaymentHandler()
    {
        global $var, $user, $CFG, $nfo, $lang, $title, $tpl;

        if (!$this->canPay($user)) {
            return;
        }

        $data = [
            "pay_to_email" => $this->settings["email"],
            "transaction_id" => $user->get()['ID'] . "-" . time(),
            "return_url" => $CFG['PAGEURL'] . 'credit?okay',
            "cancel_url" => $CFG['PAGEURL'] . 'credit?cancel',
            "status_url" => $CFG['PAGEURL'] . 'ipn/skrill',
            "prepare_only" => "1",
            "pay_from_email" => $user->get()['mail'],
            "firstname" => $user->get()['firstname'],
            "lastname" => $user->get()['lastname'],
            "address" => $user->get()['street'] . " " . $user->get()['street_nr'],
            "postal_code" => $user->get()['postcode'],
            "city" => $user->get()['city'],
            "amount" => $this->addFees($nfo->phpize($_POST['amount']), true),
            "currency" => $var['myCurrency'],
        ];

        $ch = curl_init("https://pay.skrill.com");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            $var['error'] = curl_error($ch);
            return;
        }

        curl_close($ch);

        if (@$arr = json_decode($res, true)) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            $var['error'] = $arr['message'];
            return;
        }

        if (strlen($res) != 32 || strpos($res, " ") !== false) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            $var['error'] = $res;
            return;
        }

        header('Location: https://pay.skrill.com/?sid=' . $res);
        exit;
    }

    public function makeCashboxPayment($hash)
    {
        global $var, $CFG, $nfo, $lang, $title, $tpl;

        $data = [
            "pay_to_email" => $this->settings["email"],
            "transaction_id" => $hash . "-" . time(),
            "return_url" => $CFG['PAGEURL'] . 'credit?okay',
            "cancel_url" => $CFG['PAGEURL'] . 'credit?cancel',
            "status_url" => $CFG['PAGEURL'] . 'ipn/skrill',
            "prepare_only" => "1",
            "amount" => $this->addFees($nfo->phpize($_POST['amount']), true),
            "currency" => $var['myCurrency'],
        ];

        $ch = curl_init("https://pay.skrill.com");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            $var['error'] = curl_error($ch);
            return;
        }

        curl_close($ch);

        if (@$arr = json_decode($res, true)) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            $var['error'] = $arr['message'];
            return;
        }

        if (strlen($res) != 32 || strpos($res, " ") !== false) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            $var['error'] = $res;
            return;
        }

        header('Location: https://pay.skrill.com/?sid=' . $res);
        exit;
    }

    public function getIpnHandler()
    {
        global $CFG, $db, $transactions, $cur, $maq, $raw_cfg, $nfo;

        $log = "System gestartet\n";

        try {
            $transactionPayEmail = $_POST['pay_to_email'];
            $transactionPayFromEmail = $_POST['pay_from_email'];
            $transactionMerchantId = $_POST['merchant_id'];
            $transactionMbTransactionId = $_POST['mb_transaction_id'];
            $transactionMAmount = $_POST['mb_amount'];
            $transactionMbCurrency = $_POST['mb_currency'];
            $transactionStatus = $_POST['status'];
            $transactionMd5sig = $_POST['md5sig'];
            $transactionAmount = $_POST['amount'];
            $transactionCurrency = $_POST['currency'];
            $transactionId = $tid = $_POST['transaction_id'] ?: "";

            $md5signature = $transactionMerchantId . $transactionId . strtoupper($this->settings["secret_word"]) . $transactionMAmount . $transactionMbCurrency . $transactionStatus;
            if ($md5signature != $transactionMd5sig) {
                throw new Exception($log . "Ungültige Signatur");
            }
            $log .= "Signatur geprüft\n";

            if ($transactionStatus == 2) {
                throw new Exception($log . "Ungültiger Status");
            }
            $log .= "Transaktion abgeschlossen\n";

            // Check if the payment is not already inserted in the system
            if (count($transactions->get(array("subject" => "skrill|$tid"))) > 0) {
                throw new Exception($log . "Transaktions-ID bereits eingebucht");
            }

            // Check if the currency code is correct
            try {
                $curObj = new Currency($transactionMbCurrency);
            } catch (CurrencyException $ex) {
                throw new Exception($log . "Unbekannte W&auml;hrung '" . $transactionMbCurrency . "'");
            }

            // Get payment subject and try to extract the user ID
            $ex = explode("-", $transactionId);
            $subject = trim(array_shift($ex));

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
                $user = $subject;
            }

            $log .= "Benutzer: $user\n";

            // Check if the extracted user exists
            if (!is_numeric($user) || $user < 0 || $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->num_rows != 1) {
                throw new Exception($log . "Benutzer unbekannt");
            }

            if (!$this->canPay(User::getInstance($user, "ID"))) {
                throw new Exception("Benutzer nicht berechtigt");
            }

            $payment_amount = $curObj->convertFrom($transactionAmount);
            $fees = $this->getFees($payment_amount);

            // Update the user credit and insert the transaction into the database
            $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($payment_amount) . "' WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1");
            $transactions->insert("skrill", $tid, $payment_amount, $user, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);

            $log .= "Guthaben aktualisiert\nTransaktion eingef&uuml;gt\n";

            // Gather user information and send a notification email
            $userInfo = $db->query("SELECT ID, firstname, lastname, mail FROM clients WHERE ID = '" . $db->real_escape_string($user) . "' LIMIT 1")->fetch_object();
            $uI = User::getInstance($userInfo->mail);
            $sendLang = $uI->getLanguage();

            $mtObj = new MailTemplate("Guthabenaufladung");
            $title = $mtObj->getTitle($sendLang);
            $mail = $mtObj->getMail($sendLang, $userInfo->firstname . " " . $userInfo->lastname);
            
            $maq->enqueue([
                "amount" => $curObj->getPrefix() . $payment_amount . $curObj->getSuffix(),
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
